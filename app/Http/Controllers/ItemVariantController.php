<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemVariant;
use App\Models\Item;
use DataTables;
use Validator;

class ItemVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $item_id = null)
    {
        $query = ItemVariant::with('item')->orderBy('id', 'DESC');

        if ($item_id) {
            $query->where('item_id', $item_id);
        }

        if ($request->ajax()) {
            return DataTables::of($query)
                    ->addColumn('item_name', function ($variant) {
                        return $variant->item->name;
                    })
                    ->editColumn('price', function($variant){
                        return number_format($variant->price, 2);
                    })
                    ->editColumn('cost', function($variant){
                        return number_format($variant->cost, 2);
                    })
                    ->editColumn('is_default', function($variant){
                        return $variant->is_default ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>';
                    })
                    ->addColumn('action', function($variant){
                        $action = '<div class="dropdown">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            ' . _lang('Action') . '
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';
                        $action .= '<a href="' . route('item-variants.edit', $variant->id) . '" class="dropdown-item ajax-modal" data-title="' . _lang('Edit') . '">
                                        <i class="fas fa-edit"></i>
                                        ' . _lang('Edit') . '
                                    </a>';
                        $action .= '<form action="' . route('item-variants.destroy', $variant->id) . '" method="post" class="ajax-delete">'
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
                    ->rawColumns(['action', 'is_default'])
                    ->make(true);
        }

        $items = Item::where('had_variants', true)->get();
        return view('backend.item_variants.index', compact('items', 'item_id'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $items = Item::where('had_variants', true)->get();

        if (! $request->ajax()) {
            return view('backend.item_variants.create', compact('items'));
        } else {
            return view('backend.item_variants.modal.create', compact('items'));
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
            'item_id' => 'required|exists:items,id',
            'name' => 'required|string|max:191',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'ingredient_quantity' => 'nullable|numeric|min:0',
            'is_default' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) { 
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }			
        }

        // If this variant is set as default, unset other defaults for the same item
        if ($request->is_default) {
            ItemVariant::where('item_id', $request->item_id)->update(['is_default' => false]);
        }

        $variant = new ItemVariant();
        $variant->item_id = $request->item_id;
        $variant->name = $request->name;
        $variant->price = $request->price;
        $variant->cost = $request->cost;
        $variant->ingredient_quantity = $request->ingredient_quantity;
        $variant->is_default = $request->is_default;
        $variant->save();

        cache()->flush();

        if (! $request->ajax()) {
            return back()->with('success', _lang('Variant has been added successfully.'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Variant has been added successfully.')]);
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
        $variant = ItemVariant::with('item')->findOrFail($id);
        if (! $request->ajax()) {
            return view('backend.item_variants.show', compact('variant'));
        } else {
            return view('backend.item_variants.modal.show', compact('variant'));
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
        $variant = ItemVariant::findOrFail($id);
        $items = Item::where('had_variants', true)->get();

        if (! $request->ajax()) {
            return view('backend.item_variants.edit', compact('variant', 'items'));
        } else {
            return view('backend.item_variants.modal.edit', compact('variant', 'items'));
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
            'item_id' => 'required|exists:items,id',
            'name' => 'required|string|max:191',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'ingredient_quantity' => 'nullable|numeric|min:0',
            'is_default' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) { 
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }			
        }

        $variant = ItemVariant::findOrFail($id);

        // If this variant is set as default, unset other defaults for the same item
        if ($request->is_default) {
            ItemVariant::where('item_id', $request->item_id)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $variant->item_id = $request->item_id;
        $variant->name = $request->name;
        $variant->price = $request->price;
        $variant->cost = $request->cost;
        $variant->ingredient_quantity = $request->ingredient_quantity;
        $variant->is_default = $request->is_default;
        $variant->save();

        cache()->flush();

        if (! $request->ajax()) {
            return redirect('item-variants')->with('success', _lang('Variant has been updated successfully.'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Variant has been updated successfully.')]);
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
        $variant = ItemVariant::findOrFail($id);
        $variant->delete();

        cache()->flush();

        if (! $request->ajax()) {
            return redirect('item-variants')->with('success', _lang('Variant has been deleted successfully.'));
        } else {
            return response()->json(['result' => 'success', 'message' => _lang('Variant has been deleted successfully.')]);
        }
    }
}
