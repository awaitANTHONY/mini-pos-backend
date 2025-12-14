<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Ingredient;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display reports dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('backend.reports.index');
    }

    /**
     * Ingredients consumed report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ingredientsConsumed(Request $request)
    {
        $dateFrom = $request->input('start_date', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('end_date', now()->toDateString());

        // Get all sale items within the date range with their ingredient usage
        $saleItems = SaleItem::with(['item.ingredient', 'variant', 'sale'])
            ->whereHas('sale', function($query) use ($dateFrom, $dateTo) {
                $query->whereDate('created_at', '>=', $dateFrom)
                      ->whereDate('created_at', '<=', $dateTo);
            })
            ->get();

        // Calculate ingredient consumption
        $ingredientConsumption = [];

        foreach ($saleItems as $saleItem) {
            $item = $saleItem->item;
            $variant = $saleItem->variant;

            if (!$item->ingredient_id) {
                continue;
            }

            $ingredientQuantity = $variant && $variant->ingredient_quantity 
                ? $variant->ingredient_quantity 
                : $item->ingredient_quantity;

            if (!$ingredientQuantity || $ingredientQuantity <= 0) {
                continue;
            }

            $consumed = $ingredientQuantity * $saleItem->quantity;
            $ingredientId = $item->ingredient_id;

            if (!isset($ingredientConsumption[$ingredientId])) {
                $ingredientConsumption[$ingredientId] = [
                    'ingredient' => $item->ingredient,
                    'total_consumed' => 0,
                    'sales_count' => 0,
                ];
            }

            $ingredientConsumption[$ingredientId]['total_consumed'] += $consumed;
            $ingredientConsumption[$ingredientId]['sales_count']++;
        }

        $result = [];
        foreach ($ingredientConsumption as $item) {
            $result[] = [
                'name' => $item['ingredient']->name,
                'unit' => $item['ingredient']->unit,
                'consumed' => number_format($item['total_consumed'], 2),
                'current_stock' => Stock::where('ingredient_id', $item['ingredient']->id)->value('quantity') ?? 0,
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        return view('backend.reports.ingredients_consumed', compact('ingredientConsumption', 'dateFrom', 'dateTo'));
    }

    /**
     * Sales summary report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function salesSummary(Request $request)
    {
        $dateFrom = $request->input('start_date', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('end_date', now()->toDateString());

        $summary = Sale::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->selectRaw('
                COUNT(*) as total_sales,
                SUM(total_amount) as total_revenue,
                SUM(total_cost) as total_cost,
                SUM(total_amount - total_cost) as total_profit,
                SUM(paid_amount) as total_paid,
                SUM(due_amount) as total_due
            ')
            ->first();

        // Sales by payment status
        $salesByStatus = Sale::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select('payment_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_status')
            ->get();

        // Top selling items
        $topItems = SaleItem::with('item')
            ->whereHas('sale', function($query) use ($dateFrom, $dateTo) {
                $query->whereDate('created_at', '>=', $dateFrom)
                      ->whereDate('created_at', '<=', $dateTo);
            })
            ->select('item_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_price) as total_sales'))
            ->groupBy('item_id')
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'total_sales' => $summary->total_sales ?? 0,
                'total_revenue' => $summary->total_revenue ?? 0,
                'total_cost' => $summary->total_cost ?? 0,
                'total_profit' => $summary->total_profit ?? 0,
                'average_sale' => $summary->total_sales > 0 ? ($summary->total_revenue / $summary->total_sales) : 0,
                'top_items' => $topItems->map(function($item) {
                    return [
                        'name' => $item->item->name ?? 'N/A',
                        'quantity' => $item->total_quantity,
                        'revenue' => $item->total_sales,
                    ];
                }),
            ]);
        }

        return view('backend.reports.sales_summary', compact('summary', 'salesByStatus', 'topItems', 'dateFrom', 'dateTo'));
    }

    /**
     * Stock status report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function stockStatus(Request $request)
    {
        $threshold = $request->input('threshold', config('pos.low_stock_threshold', 10));

        $stocks = Stock::with('ingredient')
            ->orderBy('quantity', 'asc')
            ->get();

        $lowStocks = $stocks->filter(function($stock) use ($threshold) {
            return $stock->quantity <= $threshold;
        });

        $outOfStock = $stocks->filter(function($stock) {
            return $stock->quantity <= 0;
        });

        $summary = [
            'total_ingredients' => $stocks->count(),
            'low_stock_count' => $lowStocks->count(),
            'out_of_stock_count' => $outOfStock->count(),
            'total_stock_value' => 0, // Can be calculated if we add cost per unit
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($stocks->map(function($stock) use ($threshold) {
                return [
                    'name' => $stock->ingredient->name ?? 'N/A',
                    'unit' => $stock->ingredient->unit ?? '',
                    'quantity' => number_format($stock->quantity, 2),
                    'is_low_stock' => $stock->quantity <= $threshold,
                ];
            }));
        }

        return view('backend.reports.stock_status', compact('stocks', 'lowStocks', 'outOfStock', 'summary', 'threshold'));
    }

    /**
     * Expense report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function expenses(Request $request)
    {
        $dateFrom = $request->input('start_date', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('end_date', now()->toDateString());

        $summary = Expense::whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->selectRaw('
                COUNT(*) as total_expenses,
                SUM(total_amount) as total_amount,
                SUM(due_amount) as total_due
            ')
            ->first();

        $expenses = Expense::with(['ingredient'])
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->orderBy('expense_date', 'desc')
            ->get();

        // Expenses by ingredient
        $expensesByIngredient = Expense::with('ingredient')
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->whereNotNull('ingredient_id')
            ->select('ingredient_id', DB::raw('SUM(total_amount) as total_amount'), DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('ingredient_id')
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'total_expenses' => $summary->total_amount ?? 0,
                'breakdown' => $expensesByIngredient->map(function($expense) {
                    return [
                        'ingredient_name' => $expense->ingredient->name ?? 'Other',
                        'total' => $expense->total_amount,
                        'quantity' => number_format($expense->total_quantity, 2),
                    ];
                }),
            ]);
        }

        return view('backend.reports.expenses', compact('summary', 'expenses', 'expensesByIngredient', 'dateFrom', 'dateTo'));
    }
}
