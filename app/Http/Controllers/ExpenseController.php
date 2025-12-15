<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ExpenseService;
use App\Models\Expense;
use App\Models\Ingredient;
use DataTables;
use Validator;

class ExpenseController extends Controller
{
    protected $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $expenses = Expense::with(['ingredient', 'creator'])->orderBy('id', 'DESC');

        if ($request->ajax()) {
            return DataTables::of($expenses)
                    ->editColumn('amount', function ($expense) {
                        return number_format($expense->amount, 2);
                    })
                    ->editColumn('due_amount', function ($expense) {
                        return number_format($expense->due_amount, 2);
                    })
                    ->addColumn('ingredient_name', function ($expense) {
                        return $expense->ingredient ? $expense->ingredient->name : '-';
                    })
                    ->editColumn('quantity', function ($expense) {
                        return $expense->quantity > 0 ? number_format($expense->quantity, 2) : '-';
                    })
                    ->editColumn('expense_date', function ($expense) {
                        return $expense->expense_date->format('Y-m-d');
                    })
                    ->addColumn('action', function($expense){
                        $action = '<div class="dropdown">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            ' . _lang('Action') . '
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';
                        $action .= '<a href="' . route('expenses.show', $expense->id) . '" class="dropdown-item ajax-modal" data-title="' . _lang('View') . '">
                                        <i class="fas fa-eye"></i>
                                        ' . _lang('View') . '
                                    </a>';
                        $action .= '<a href="' . route('expenses.edit', $expense->id) . '" class="dropdown-item">
                                        <i class="fas fa-edit"></i>
                                        ' . _lang('Edit') . '
                                    </a>';
                        $action .= '<form action="' . route('expenses.destroy', $expense->id) . '" method="post" class="ajax-delete">'
                                    . csrf_field() 
                                    . method_field('DELETE') 
                                    . '<button type="button" class="btn-remove dropdown-item">
                                            <i class="fas fa-trash-alt"></i>
                                            ' . _lang('Delete') . '
                                        </button>
                                    </form>';
                        $action .= '</div>
                                </div>';
                        return $action;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }

        return view('backend.expenses.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $ingredients = Ingredient::all();

        if (! $request->ajax()) {
            return view('backend.expenses.create', compact('ingredients'));
        } else {
            return view('backend.expenses.modal.create', compact('ingredients'));
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
            'total_amount' => 'required|numeric|min:0',
            'due_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'nullable|date',
            'description' => 'nullable|string',
            'ingredient_id' => 'nullable|exists:ingredients,id',
            'quantity' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) { 
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }			
        }

        try {
            $expense = $this->expenseService->createExpense($request->all());

            cache()->flush();

            if (! $request->ajax()) {
                return back()->with('success', _lang('Expense has been added successfully.'));
            } else {
                return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Expense has been added successfully.')]);
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
        $expense = $this->expenseService->getExpense($id);
        if (! $request->ajax()) {
            return view('backend.expenses.show', compact('expense'));
        } else {
            return view('backend.expenses.modal.show', compact('expense'));
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
        $expense = Expense::findOrFail($id);
        $ingredients = Ingredient::all();

        if (! $request->ajax()) {
            return view('backend.expenses.edit', compact('expense', 'ingredients'));
        } else {
            return view('backend.expenses.modal.edit', compact('expense', 'ingredients'));
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
            'total_amount' => 'required|numeric|min:0',
            'due_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'nullable|date',
            'description' => 'nullable|string',
            'ingredient_id' => 'nullable|exists:ingredients,id',
            'quantity' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) { 
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }			
        }

        try {
            $expense = $this->expenseService->updateExpense($id, $request->all());

            cache()->flush();

            if (! $request->ajax()) {
                return redirect('expenses')->with('success', _lang('Expense has been updated successfully.'));
            } else {
                return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Expense has been updated successfully.')]);
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->expenseService->deleteExpense($id);

            cache()->flush();

            if (! $request->ajax()) {
                return redirect('expenses')->with('success', _lang('Expense has been deleted successfully.'));
            } else {
                return response()->json(['result' => 'success', 'message' => _lang('Expense has been deleted successfully.')]);
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
