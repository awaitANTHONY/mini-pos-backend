<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Category;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Item;
use App\Models\Payment;
use App\Services\SaleService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PosController extends Controller
{
    protected $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    // ==================== EXPENSES ====================

    /**
     * Get expenses list
     */
    public function expensesList(Request $request)
    {
        $query = Expense::with(['ingredient', 'creator'])
            ->orderBy('expense_date', 'desc');

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
        }

        $expenses = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $expenses
        ]);
    }

    /**
     * Get expense details
     */
    public function expenseDetails($id)
    {
        $expense = Expense::with(['ingredient', 'creator'])->find($id);

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $expense
        ]);
    }

    /**
     * Create expense
     */
    public function expenseCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expense_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'ingredient_id' => 'nullable|exists:ingredients,id',
            'quantity' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'supplier_name' => 'nullable|string|max:191',
            'supplier_contact' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $expense = Expense::create([
                'expense_date' => $request->expense_date,
                'total_amount' => $request->total_amount,
                'due_amount' => $request->get('due_amount', 0),
                'ingredient_id' => $request->ingredient_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'supplier_name' => $request->supplier_name,
                'supplier_contact' => $request->supplier_contact,
                'description' => $request->description,
                'note' => $request->note,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully',
                'data' => $expense->load(['ingredient', 'creator'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update expense (within 24 hours only)
     */
    public function expenseEdit(Request $request, $id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found'
            ], 404);
        }

        // Check if expense is within allowed hours
        $updateTime = (int) get_option('update_time', 24);
        $createdAt = Carbon::parse($expense->created_at);
        $now = Carbon::now();
        $hoursDifference = $createdAt->diffInHours($now);

        if ($hoursDifference > $updateTime) {
            return response()->json([
                'success' => false,
                'message' => "Cannot edit expense after {$updateTime} hours"
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'expense_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'ingredient_id' => 'nullable|exists:ingredients,id',
            'quantity' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'supplier_name' => 'nullable|string|max:191',
            'supplier_contact' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $expense->update([
                'expense_date' => $request->expense_date,
                'total_amount' => $request->total_amount,
                'due_amount' => $request->get('due_amount', $expense->due_amount),
                'ingredient_id' => $request->ingredient_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'supplier_name' => $request->supplier_name,
                'supplier_contact' => $request->supplier_contact,
                'description' => $request->description,
                'note' => $request->note,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully',
                'data' => $expense->load(['ingredient', 'creator'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== CATEGORIES ====================

    /**
     * Get categories list
     */
    public function categoriesList()
    {
        $categories = Category::where('status', 1)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    // ==================== SALES ====================

    /**
     * Get sales list
     */
    public function salesList(Request $request)
    {
        $query = Sale::with(['items.item', 'items.variant', 'payments'])
            ->orderBy('created_at', 'desc');

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        // Filter by payment status if provided
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $sales = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'sales' => $sales
        ]);
    }

    /**
     * Create sale
     */
    public function saleCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.variant_id' => 'nullable|exists:item_variants,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,online',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Convert payment_amount and payment_method to payments array format
            $payments = [];
            if ($request->has('payment_amount') && $request->payment_amount > 0) {
                $payments[] = [
                    'amount' => $request->payment_amount,
                    'method' => $request->get('payment_method', 'cash'),
                    'paid_at' => now(),
                ];
            }

            $saleData = [
                'items' => $request->items,
                'payments' => $payments,
                'note' => $request->note,
            ];

            $sale = $this->saleService->createSale($saleData, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Sale created successfully',
                'data' => $sale->load(['user', 'items.item', 'items.variant', 'payments'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update sale (within 24 hours only)
     */
    public function saleEdit(Request $request, $id)
    {
        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        // Check if sale is within allowed hours
        $updateTime = (int) get_option('update_time', 24);
        $createdAt = Carbon::parse($sale->created_at);
        $now = Carbon::now();
        $hoursDifference = $createdAt->diffInHours($now);

        if ($hoursDifference > $updateTime) {
            return response()->json([
                'success' => false,
                'message' => "Cannot edit sale after {$updateTime} hours"
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.variant_id' => 'nullable|exists:item_variants,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,online',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Convert payment_amount and payment_method to payments array format if provided
            $payments = [];
            if ($request->has('payment_amount') && $request->payment_amount > 0) {
                $payments[] = [
                    'amount' => $request->payment_amount,
                    'method' => $request->get('payment_method', 'cash'),
                    'paid_at' => now(),
                ];
            }

            $saleData = [
                'items' => $request->items,
                'payments' => $payments,
                'note' => $request->note,
            ];

            $sale = $this->saleService->updateSale($id, $saleData);

            return response()->json([
                'success' => true,
                'message' => 'Sale updated successfully',
                'data' => $sale->load(['user', 'items.item', 'items.variant', 'payments'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sale',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== ITEMS ====================

    /**
     * Get items list with variants
     */
    public function itemsList(Request $request)
    {
        $query = Item::with(['category', 'ingredient', 'variants'])
            ->where('status', 1);

        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by name if provided
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $items = $query->orderBy('category_id', 'asc')->orderBy('id', 'asc')->get();

        // Format the response to include all variant data
        $items = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'image_url' => $item->image_url,
                'category_id' => $item->category_id,
                'category' => $item->category ? [
                    'id' => $item->category->id,
                    'title' => $item->category->title,
                ] : null,
                'ingredient_id' => $item->ingredient_id,
                'ingredient' => $item->ingredient ? [
                    'id' => $item->ingredient->id,
                    'name' => $item->ingredient->name,
                    'unit' => $item->ingredient->unit,
                ] : null,
                'ingredient_quantity' => $item->ingredient_quantity,
                'had_variants' => $item->had_variants,
                'price' => $item->price,
                'cost' => $item->cost,
                'status' => $item->status,
                'variants' => $item->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'price' => $variant->price,
                        'cost' => $variant->cost,
                        'ingredient_quantity' => $variant->ingredient_quantity,
                        'is_default' => $variant->is_default,
                    ];
                }),
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
}
