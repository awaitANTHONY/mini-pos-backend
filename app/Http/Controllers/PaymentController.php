<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SaleService;
use App\Models\Payment;
use App\Models\Sale;
use DataTables;
use Validator;

class PaymentController extends Controller
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
        $payments = Payment::with(['sale', 'creator'])->orderBy('paid_at', 'DESC');

        if ($request->ajax()) {
            return DataTables::of($payments)
                    ->addColumn('invoice_no', function ($payment) {
                        return $payment->sale->invoice_no;
                    })
                    ->editColumn('amount', function ($payment) {
                        return number_format($payment->amount, 2);
                    })
                    ->editColumn('paid_at', function ($payment) {
                        return $payment->paid_at->format('Y-m-d H:i');
                    })
                    ->addColumn('action', function($payment){
                        $action = '<a href="' . route('payments.show', $payment->id) . '" class="btn btn-sm btn-info ajax-modal" data-title="' . _lang('View Payment') . '">
                                        <i class="fas fa-eye"></i>
                                    </a>';
                        return $action;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }

        return view('backend.payments.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $saleId = $request->input('sale_id');
        $sale = null;

        if ($saleId) {
            $sale = Sale::findOrFail($saleId);
        }

        $sales = Sale::where('payment_status', '!=', 'paid')->get();

        if (! $request->ajax()) {
            return view('backend.payments.create', compact('sales', 'sale'));
        } else {
            return view('backend.payments.modal.create', compact('sales', 'sale'));
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
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|max:100',
            'paid_at' => 'nullable|date',
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
            $payment = $this->saleService->addPayment($request->sale_id, $request->all());

            cache()->flush();

            if (! $request->ajax()) {
                return back()->with('success', _lang('Payment has been added successfully.'));
            } else {
                return response()->json([
                    'result' => 'success', 
                    'action' => 'store', 
                    'message' => _lang('Payment has been added successfully.'),
                    'data' => ['id' => $payment->id]
                ]);
            }
        } catch (\Exception $e) {
            if (! $request->ajax()) {
                return back()->with('error', $e->getMessage());
            } else {
                return response()->json(['result' => 'error', 'message' => $e->getMessage()]);
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
        $payment = Payment::with(['sale', 'creator'])->findOrFail($id);
        if (! $request->ajax()) {
            return view('backend.payments.show', compact('payment'));
        } else {
            return view('backend.payments.modal.show', compact('payment'));
        } 
    }
}
