<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Category;
use App\Models\Ingredient;
use DataTables;
use Validator;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $items = Item::with(['category', 'ingredient'])->orderBy('id', 'DESC');

        if ($request->ajax()) {
            return DataTables::of($items)
                    ->addColumn('image', function ($item) {
                        return '<img class="img-sm img-thumbnail" src="' . $item->image_url . '">';
                    })
                    ->addColumn('category_name', function ($item) {
                        return $item->category->title ?? '-';
                    })
                    ->addColumn('ingredient_name', function ($item) {
                        return $item->ingredient->name ?? '-';
                    })
                    ->editColumn('price', function($item){
                        return $item->price ? number_format($item->price, 2) : '-';
                    })
                    ->editColumn('status', function($item){
                        return ($item->status == 1) ? status(_lang('Active'), 'success') : status(_lang('Inactive'), 'danger');
                    })
                    ->addColumn('action', function($item){
                        $action = '<div class="dropdown">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            ' . _lang('Action') . '
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';
                        $action .= '<a href="' . route('items.edit', $item->id) . '" class="dropdown-item">
                                        <i class="fas fa-edit"></i>
                                        ' . _lang('Edit') . '
                                    </a>';
                        $action .= '<form action="' . route('items.destroy', $item->id) . '" method="post">'
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
                    ->rawColumns(['action', 'status', 'image'])
                    ->make(true);
        }

        return view('backend.items.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $categories = Category::where('status', 1)->get();
        $ingredients = Ingredient::all();

        if (! $request->ajax()) {
            return view('backend.items.create', compact('categories', 'ingredients'));
        } else {
            return view('backend.items.modal.create', compact('categories', 'ingredients'));
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
        $rules = [
            'name' => 'required|string|max:191',
            'category_id' => 'nullable|exists:categories,id',
            'ingredient_id' => 'nullable|exists:ingredients,id',
            'ingredient_quantity' => 'nullable|numeric|min:0',
            'had_variants' => 'required|boolean',
            'status' => 'required|in:0,1',
            'image' => 'nullable|image',
        ];

        // If has_variants is false, price and cost are required
        if (!$request->had_variants) {
            $rules['price'] = 'required|numeric|min:0';
            $rules['cost'] = 'required|numeric|min:0';
        } else {
            $rules['price'] = 'nullable|numeric|min:0';
            $rules['cost'] = 'nullable|numeric|min:0';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->ajax()) { 
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }			
        }

        $item = new Item();
        $item->name = $request->name;
        $item->category_id = $request->category_id;
        $item->ingredient_id = $request->ingredient_id;
        $item->ingredient_quantity = $request->ingredient_quantity;
        $item->had_variants = $request->had_variants;
        $item->price = $request->price;
        $item->cost = $request->cost;
        $item->status = $request->status == 1 ? 'active' : 'inactive';

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $file_name = time() . '.' . $file->getClientOriginalExtension();
            $file_path = 'public/uploads/images/items/';
            $file->move(base_path($file_path), $file_name);
            $item->image_url = $file_path . $file_name;
        }

        $item->save();

        // Save variants if has_variants is true
        if ($request->had_variants && $request->has('variant_name')) {
            $variantNames = $request->variant_name;
            $variantPrices = $request->variant_price;
            $variantCosts = $request->variant_cost;
            $variantIngredientQuantities = $request->variant_ingredient_quantity;
            $variantIsDefaults = $request->variant_is_default;

            foreach ($variantNames as $key => $name) {
                if (!empty($name)) {
                    \App\Models\ItemVariant::create([
                        'item_id' => $item->id,
                        'name' => $name,
                        'price' => $variantPrices[$key] ?? 0,
                        'cost' => $variantCosts[$key] ?? 0,
                        'ingredient_quantity' => $variantIngredientQuantities[$key] ?? null,
                        'is_default' => $variantIsDefaults[$key] ?? 0,
                    ]);
                }
            }
        }

        cache()->flush();

        if (! $request->ajax()) {
            return redirect('items')->with('success', _lang('Item has been added successfully.'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Item has been added successfully.')]);
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
        $item = Item::with(['category', 'ingredient', 'variants'])->findOrFail($id);
        if (! $request->ajax()) {
            return view('backend.items.show', compact('item'));
        } else {
            return view('backend.items.modal.show', compact('item'));
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
        $item = Item::findOrFail($id);
        $categories = Category::where('status', 1)->get();
        $ingredients = Ingredient::all();

        if (! $request->ajax()) {
            return view('backend.items.edit', compact('item', 'categories', 'ingredients'));
        } else {
            return view('backend.items.modal.edit', compact('item', 'categories', 'ingredients'));
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
        $rules = [
            'name' => 'required|string|max:191',
            'category_id' => 'nullable|exists:categories,id',
            'ingredient_id' => 'nullable|exists:ingredients,id',
            'ingredient_quantity' => 'nullable|numeric|min:0',
            'had_variants' => 'required|boolean',
            'status' => 'required|in:0,1',
            'image' => 'nullable|image',
        ];

        // If has_variants is false, price and cost are required
        if (!$request->had_variants) {
            $rules['price'] = 'required|numeric|min:0';
            $rules['cost'] = 'required|numeric|min:0';
        } else {
            $rules['price'] = 'nullable|numeric|min:0';
            $rules['cost'] = 'nullable|numeric|min:0';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->ajax()) { 
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }			
        }

        $item = Item::findOrFail($id);
        $item->name = $request->name;
        $item->category_id = $request->category_id;
        $item->ingredient_id = $request->ingredient_id;
        $item->ingredient_quantity = $request->ingredient_quantity;
        $item->had_variants = $request->had_variants;
        $item->price = $request->price;
        $item->cost = $request->cost;
        $item->status = $request->status == 1 ? 'active' : 'inactive';

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $file_name = time() . '.' . $file->getClientOriginalExtension();
            $file_path = 'public/uploads/images/items/';
            $file->move(base_path($file_path), $file_name);
            $item->image_url = $file_path . $file_name;
        }

        $item->save();

        // Update variants if has_variants is true
        if ($request->had_variants && $request->has('variant_name')) {
            $variantIds = $request->variant_id ?? [];
            $variantNames = $request->variant_name;
            $variantPrices = $request->variant_price;
            $variantCosts = $request->variant_cost;
            $variantIngredientQuantities = $request->variant_ingredient_quantity;
            $variantIsDefaults = $request->variant_is_default;

            // Track existing variant IDs to keep
            $existingVariantIds = [];

            foreach ($variantNames as $key => $name) {
                if (!empty($name)) {
                    $variantId = $variantIds[$key] ?? null;
                    
                    if ($variantId) {
                        // Update existing variant
                        $variant = \App\Models\ItemVariant::find($variantId);
                        if ($variant) {
                            $variant->update([
                                'name' => $name,
                                'price' => $variantPrices[$key] ?? 0,
                                'cost' => $variantCosts[$key] ?? 0,
                                'ingredient_quantity' => $variantIngredientQuantities[$key] ?? null,
                                'is_default' => $variantIsDefaults[$key] ?? 0,
                            ]);
                            $existingVariantIds[] = $variantId;
                        }
                    } else {
                        // Create new variant
                        $variant = \App\Models\ItemVariant::create([
                            'item_id' => $item->id,
                            'name' => $name,
                            'price' => $variantPrices[$key] ?? 0,
                            'cost' => $variantCosts[$key] ?? 0,
                            'ingredient_quantity' => $variantIngredientQuantities[$key] ?? null,
                            'is_default' => $variantIsDefaults[$key] ?? 0,
                        ]);
                        $existingVariantIds[] = $variant->id;
                    }
                }
            }

            // Delete variants that were removed
            \App\Models\ItemVariant::where('item_id', $item->id)
                ->whereNotIn('id', $existingVariantIds)
                ->delete();
        } else {
            // If has_variants is false, delete all variants
            \App\Models\ItemVariant::where('item_id', $item->id)->delete();
        }

        cache()->flush();

        if (! $request->ajax()) {
            return redirect('items')->with('success', _lang('Item has been updated successfully.'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Item has been updated successfully.')]);
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
        $item = Item::findOrFail($id);
        $item->delete();

        cache()->flush();

        if (! $request->ajax()) {
            return redirect('items')->with('success', _lang('Item has been deleted successfully.'));
        } else {
            return response()->json(['result' => 'success', 'message' => _lang('Item has been deleted successfully.')]);
        }
    }
}
