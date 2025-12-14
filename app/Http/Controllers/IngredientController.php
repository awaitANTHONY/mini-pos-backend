<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Ingredient;
use App\Models\Stock;
use DataTables;
use Validator;

class IngredientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ingredients = Ingredient::with('stock')->orderBy('id', 'DESC');

        if ($request->ajax()) {
            return DataTables::of($ingredients)
                    ->addColumn('current_stock', function ($ingredient) {
                        $stock = $ingredient->stock;
                        return $stock ? number_format($stock->quantity, 2) . ' ' . $ingredient->unit : '0 ' . $ingredient->unit;
                    })
                    ->addColumn('action', function($ingredient){
                        $action = '<div class="dropdown">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            ' . _lang('Action') . '
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';
                        $action .= '<a href="' . route('ingredients.edit', $ingredient->id) . '" class="dropdown-item ajax-modal" data-title="' . _lang('Edit') . '">
                                        <i class="fas fa-edit"></i>
                                        ' . _lang('Edit') . '
                                    </a>';
                        $action .= '<form action="' . route('ingredients.destroy', $ingredient->id) . '" method="post" class="ajax-delete">'
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
                    ->rawColumns(['action', 'current_stock'])
                    ->make(true);
        }

        return view('backend.ingredients.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (! $request->ajax()) {
            return view('backend.ingredients.create');
        } else {
            return view('backend.ingredients.modal.create');
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
            'name' => 'required|string|max:191',
            'unit' => 'required|string|max:50',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) { 
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }			
        }

        $ingredient = new Ingredient();
        $ingredient->name = $request->name;
        $ingredient->unit = $request->unit;
        $ingredient->note = $request->note;
        $ingredient->save();

        // Create stock record
        Stock::create([
            'ingredient_id' => $ingredient->id,
            'quantity' => 0,
            'unit' => $ingredient->unit,
        ]);

        cache()->flush();

        if (! $request->ajax()) {
            return back()->with('success', _lang('Ingredient has been added successfully.'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Ingredient has been added successfully.')]);
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
        $ingredient = Ingredient::with('stock')->findOrFail($id);
        if (! $request->ajax()) {
            return view('backend.ingredients.show', compact('ingredient'));
        } else {
            return view('backend.ingredients.modal.show', compact('ingredient'));
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
        $ingredient = Ingredient::findOrFail($id);
        if (! $request->ajax()) {
            return view('backend.ingredients.edit', compact('ingredient'));
        } else {
            return view('backend.ingredients.modal.edit', compact('ingredient'));
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
            'name' => 'required|string|max:191',
            'unit' => 'required|string|max:50',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) { 
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }			
        }

        $ingredient = Ingredient::findOrFail($id);
        $ingredient->name = $request->name;
        $ingredient->unit = $request->unit;
        $ingredient->note = $request->note;
        $ingredient->save();

        // Update stock unit if exists
        $stock = Stock::where('ingredient_id', $ingredient->id)->first();
        if ($stock) {
            $stock->unit = $ingredient->unit;
            $stock->save();
        }

        cache()->flush();

        if (! $request->ajax()) {
            return redirect('ingredients')->with('success', _lang('Ingredient has been updated successfully.'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Ingredient has been updated successfully.')]);
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
        $ingredient = Ingredient::findOrFail($id);
        $ingredient->delete();

        cache()->flush();

        if (! $request->ajax()) {
            return redirect('ingredients')->with('success', _lang('Ingredient has been deleted successfully.'));
        } else {
            return response()->json(['result' => 'success', 'message' => _lang('Ingredient has been deleted successfully.')]);
        }
    }
}
