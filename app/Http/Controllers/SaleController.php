<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SaleService;
use App\Models\Sale;
use App\Models\Item;
use App\Models\ItemVariant;
use DataTables;
use Validator;

class SaleController extends Controller
{
    protected $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sales = Sale::with(['user'])->orderBy('created_at', 'DESC');
        $currency = get_option('currency');
        // Apply filters
        if ($request->has('date_from') && $request->date_from) {
            $sales->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $sales->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('payment_status') && $request->payment_status) {
            $sales->where('payment_status', $request->payment_status);
        }

        if ($request->ajax()) {
            return DataTables::of($sales)
                    ->editColumn('invoice_no', function ($sale) {
                        return '<strong>' . $sale->invoice_no . '</strong>';
                    })
                    ->editColumn('total_amount', function ($sale) use ($currency) {
                        return $currency . number_format($sale->total_amount, 2);
                    })
                    ->editColumn('paid_amount', function ($sale) use ($currency) {
                        return $currency . number_format($sale->paid_amount, 2);
                    })
                    ->editColumn('due_amount', function ($sale) use ($currency) {
                        return $currency . number_format($sale->due_amount, 2);
                    })
                    ->editColumn('payment_status', function ($sale) {
                        $badges = [
                            'paid' => 'success',
                            'partial' => 'warning',
                            'due' => 'danger',
                        ];
                        $badge = $badges[$sale->payment_status] ?? 'secondary';
                        return '<span class="badge badge-' . $badge . '">' . ucfirst($sale->payment_status) . '</span>';
                    })
                    ->editColumn('created_at', function ($sale) {
                        return $sale->created_at->format('Y-m-d H:i');
                    })
                    ->addColumn('action', function($sale){
                        $action = '<div class="dropdown">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            ' . _lang('Action') . '
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';
                        $action .= '<a href="' . route('sales.show', $sale->id) . '" class="dropdown-item">
                                        <i class="fas fa-eye"></i>
                                        ' . _lang('View') . '
                                    </a>';
                        $action .= '<a href="' . route('sales.edit', $sale->id) . '" class="dropdown-item">
                                        <i class="fas fa-edit"></i>
                                        ' . _lang('Edit') . '
                                    </a>';
                        if ($sale->payment_status !== 'paid') {
                            $action .= '<a href="' . route('payments.create', ['sale_id' => $sale->id]) . '" class="dropdown-item ajax-modal" data-title="' . _lang('Add Payment') . '">
                                            <i class="fas fa-money-bill"></i>
                                            ' . _lang('Add Payment') . '
                                        </a>';
                        }
                        $action .= '</div>
                                </div>';
                        return $action;
                    })
                    ->rawColumns(['action', 'payment_status', 'invoice_no'])
                    ->make(true);
        }

        return view('backend.sales.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $items = Item::where('status', 'active')->get();

        if (! $request->ajax()) {
            return view('backend.sales.create', compact('items'));
        } else {
            return view('backend.sales.modal.create', compact('items'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.variant_id' => 'nullable|exists:item_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'required_with:payments|numeric|min:0',
            'payments.*.method' => 'required_with:payments|string',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) { 
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }			
        }

        try {
            // Prepare data with payment if amount is provided
            $data = $request->all();
            if ($request->has('payment_amount') && $request->payment_amount > 0) {
                $data['payments'] = [[
                    'amount' => $request->payment_amount,
                    'method' => $request->payment_method ?? 'cash',
                    'paid_at' => now(),
                ]];
            }
            
            $sale = $this->saleService->createSale($data);

            cache()->flush();

            if (! $request->ajax()) {
                return redirect()->route('sales.show', $sale->id)->with('success', _lang('Sale has been created successfully.'));
            } else {
                return response()->json([
                    'result' => 'success', 
                    'action' => 'store', 
                    'message' => _lang('Sale has been created successfully.'),
                    'data' => ['id' => $sale->id, 'invoice_no' => $sale->invoice_no]
                ]);
            }
        } catch (\Exception $e) {
            $statusCode = $e->getCode() == 409 ? 409 : 500;
            
            if (! $request->ajax()) {
                return back()->with('error', $e->getMessage());
            } else {
                return response()->json(['result' => 'error', 'message' => $e->getMessage()], $statusCode);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $sale = $this->saleService->getSale($id);
        if (! $request->ajax()) {
            return view('backend.sales.show', compact('sale'));
        } else {
            return view('backend.sales.modal.show', compact('sale'));
        } 
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $sale = Sale::with(['saleItems.item', 'saleItems.variant'])->findOrFail($id);
        
        if (! $request->ajax()) {
            return view('backend.sales.edit', compact('sale'));
        } else {
            return view('backend.sales.modal.edit', compact('sale'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.variant_id' => 'nullable|exists:item_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) { 
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }			
        }

        try {
            $sale = $this->saleService->updateSale($id, $request->all());

            // Add additional payment if amount is provided
            if ($request->has('payment_amount') && $request->payment_amount > 0) {
                $this->saleService->addPayment($sale->id, [
                    'amount' => $request->payment_amount,
                    'method' => $request->payment_method ?? 'cash',
                    'paid_at' => now(),
                ]);
            }

            cache()->flush();

            if (! $request->ajax()) {
                return redirect()->route('sales.show', $sale->id)->with('success', _lang('Sale has been updated successfully.'));
            } else {
                return response()->json([
                    'result' => 'success', 
                    'action' => 'update', 
                    'message' => _lang('Sale has been updated successfully.'),
                    'data' => ['id' => $sale->id, 'invoice_no' => $sale->invoice_no]
                ]);
            }
        } catch (\Exception $e) {
            $statusCode = $e->getCode() == 409 ? 409 : 500;
            
            if (! $request->ajax()) {
                return back()->with('error', $e->getMessage());
            } else {
                return response()->json(['result' => 'error', 'message' => $e->getMessage()], $statusCode);
            }
        }
    }

    /**
     * Get item variants for an item (AJAX).
     *
     * @param  int  $item_id
     * @return \Illuminate\Http\Response
     */
    public function getItemVariants($item_id)
    {
        $item = Item::findOrFail($item_id);
        $variants = ItemVariant::where('item_id', $item_id)->get();
        
        return response()->json([
            'had_variants' => $item->had_variants,
            'price' => $item->price,
            'cost' => $item->cost,
            'variants' => $variants
        ]);
    }
}
