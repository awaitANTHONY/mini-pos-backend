<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StockService;
use App\Models\Stock;
use DataTables;
use Validator;

class StockController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $stocks = Stock::with('ingredient')->orderBy('quantity', 'ASC');

        if ($request->ajax()) {
            return DataTables::of($stocks)
                    ->addColumn('ingredient_name', function ($stock) {
                        return $stock->ingredient->name;
                    })
                    ->editColumn('quantity', function($stock){
                        $lowThreshold = config('pos.low_stock_threshold', 10);
                        $class = $stock->quantity <= $lowThreshold ? 'text-danger font-weight-bold' : '';
                        return '<span class="' . $class . '">' . number_format($stock->quantity, 2) . ' ' . $stock->unit . '</span>';
                    })
                    ->editColumn('updated_at', function($stock){
                        return $stock->updated_at ? $stock->updated_at->format('Y-m-d H:i:s') : '-';
                    })
                    ->addColumn('action', function($stock){
                        $action = '<a href="' . route('stocks.show', $stock->id) . '" class="btn btn-sm btn-info ajax-modal" data-title="' . _lang('View Stock') . '">
                                        <i class="fas fa-eye"></i>
                                    </a>';
                        return $action;
                    })
                    ->rawColumns(['action', 'quantity'])
                    ->make(true);
        }

        return view('backend.stocks.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $stock = Stock::with('ingredient')->findOrFail($id);
        if (! $request->ajax()) {
            return view('backend.stocks.show', compact('stock'));
        } else {
            return view('backend.stocks.modal.show', compact('stock'));
        } 
    }

    /**
     * Get low stock items.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function lowStock(Request $request)
    {
        $threshold = $request->input('threshold', config('pos.low_stock_threshold', 10));
        $lowStocks = $this->stockService->getLowStockItems($threshold);

        if ($request->ajax()) {
            return response()->json([
                'result' => 'success',
                'data' => $lowStocks,
            ]);
        }

        return view('backend.stocks.low_stock', compact('lowStocks', 'threshold'));
    }

    /**
     * Manual stock adjustment (for admin purposes).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function adjust(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'adjustment_type' => 'required|in:add,reduce',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) { 
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }			
        }

        try {
            $stock = Stock::findOrFail($id);

            if ($request->adjustment_type === 'add') {
                $this->stockService->addStock($stock->ingredient_id, $request->quantity);
            } else {
                $this->stockService->reduceStock($stock->ingredient_id, $request->quantity, true);
            }

            cache()->flush();

            if (! $request->ajax()) {
                return back()->with('success', _lang('Stock has been adjusted successfully.'));
            } else {
                return response()->json(['result' => 'success', 'message' => _lang('Stock has been adjusted successfully.')]);
            }
        } catch (\Exception $e) {
            if (! $request->ajax()) {
                return back()->with('error', $e->getMessage());
            } else {
                return response()->json(['result' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }
}
