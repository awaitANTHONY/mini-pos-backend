@extends('layouts.app')

@section('content')
<h2 class="card-title d-none">{{ _lang('Edit Item') }}</h2>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="post" autocomplete="off" action="{{ route('items.update', $item->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Name') }}</label>
                                <input type="text" class="form-control" name="name" value="{{ $item->name }}" required>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Category') }}</label>
                                <select class="form-control select2" name="category_id" data-selected="{{ $item->category_id }}" required>
                                    <option value="">{{ _lang('Select One') }}</option>
                                    <?php create_option('categories', 'id', 'title', $item->category_id); ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Ingredient') }}</label>
                                <select class="form-control select2" name="ingredient_id" data-selected="{{ $item->ingredient_id }}">
                                    <option value="">{{ _lang('Select One (Optional)') }}</option>
                                    <?php create_option('ingredients', 'id', 'name', $item->ingredient_id); ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Description') }}</label>
                                <textarea name="description" class="form-control" rows="4">{{ $item->description }}</textarea>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Price') }}</label>
                                <input type="number" step="0.01" class="form-control" name="price" value="{{ $item->price }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Cost') }}</label>
                                <input type="number" step="0.01" class="form-control" name="cost" value="{{ $item->cost }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Ingredient Quantity') }}</label>
                                <input type="number" step="0.01" class="form-control" name="ingredient_quantity" value="{{ $item->ingredient_quantity }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Has Variants') }}</label>
                                <select class="form-control select2" name="had_variants" data-selected="{{ $item->had_variants }}" required>
                                    <option value="0">{{ _lang('No') }}</option>
                                    <option value="1">{{ _lang('Yes') }}</option>
                                </select>
                            </div>
                        </div>
                        <!-- Variants Section -->
                        <div class="col-md-12 variants-section {{ $item->had_variants ? '' : 'd-none' }}">
                            <hr>
                            <h5 class="mb-3">{{ _lang('Item Variants') }}</h5>
                            
                            @if($item->had_variants && $item->variants->count() > 0)
                                @foreach($item->variants as $variant)
                                <div class="variant-group params-card mb-3 p-3 border rounded">
                                    <input type="hidden" name="variant_id[]" value="{{ $variant->id }}">
                                    <div class="row">
                                        <div class="col-md-12 text-right">
                                            <button type="button" class="btn btn-danger btn-xs remove-variant">-</button>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">{{ _lang('Variant Name') }}</label>
                                                <input type="text" class="form-control" name="variant_name[]" value="{{ $variant->name }}" placeholder="e.g. Small, Medium, Large">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">{{ _lang('Price') }}</label>
                                                <input type="number" step="0.01" class="form-control" name="variant_price[]" value="{{ $variant->price }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">{{ _lang('Cost') }}</label>
                                                <input type="number" step="0.01" class="form-control" name="variant_cost[]" value="{{ $variant->cost }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">{{ _lang('Ingredient Quantity') }}</label>
                                                <input type="number" step="0.01" class="form-control" name="variant_ingredient_quantity[]" value="{{ $variant->ingredient_quantity }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">{{ _lang('Is Default') }}</label>
                                                <select class="form-control" name="variant_is_default[]">
                                                    <option value="0" {{ $variant->is_default ? '' : 'selected' }}>{{ _lang('No') }}</option>
                                                    <option value="1" {{ $variant->is_default ? 'selected' : '' }}>{{ _lang('Yes') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="variant-group params-card mb-3 p-3 border rounded">
                                    <input type="hidden" name="variant_id[]" value="">
                                    <div class="row">
                                        <div class="col-md-12 text-right">
                                            <button type="button" class="btn btn-danger btn-xs remove-variant">-</button>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">{{ _lang('Variant Name') }}</label>
                                                <input type="text" class="form-control" name="variant_name[]" placeholder="e.g. Small, Medium, Large">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">{{ _lang('Price') }}</label>
                                                <input type="number" step="0.01" class="form-control" name="variant_price[]" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">{{ _lang('Cost') }}</label>
                                                <input type="number" step="0.01" class="form-control" name="variant_cost[]" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">{{ _lang('Ingredient Quantity') }}</label>
                                                <input type="number" step="0.01" class="form-control" name="variant_ingredient_quantity[]">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">{{ _lang('Is Default') }}</label>
                                                <select class="form-control" name="variant_is_default[]">
                                                    <option value="0">{{ _lang('No') }}</option>
                                                    <option value="1">{{ _lang('Yes') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="col-md-12 text-right">
                                <button type="button" class="btn btn-success btn-sm add-more-variant">{{ _lang('Add More Variant') }}</button>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Status') }}</label>
                                <select class="form-control select2" name="status" data-selected="{{ $item->status }}" required>
                                    <option value="1">{{ _lang('Active') }}</option>
                                    <option value="0">{{ _lang('In-Active') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Image') }}</label>
                                <input type="file" class="form-control dropify" name="image" data-allowed-file-extensions="png jpg jpeg PNG JPG JPEG" data-default-file="{{ asset($item->image_url) }}">
                            </div>
                        </div>

                        

                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="reset" class="btn btn-danger btn-sm">{{ _lang('Reset') }}</button>
                                <button type="submit" class="btn btn-primary btn-sm">{{ _lang('Update') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js-script')
<script type="text/javascript">
    // Toggle variants section and price/cost fields based on has_variants
    $('[name=had_variants]').on('change', function() {
        if($(this).val() == '1') {
            $('.variants-section').removeClass('d-none');
            // Make price and cost optional when has variants
            $('[name=price]').removeAttr('required');
            $('[name=cost]').removeAttr('required');
        } else {
            $('.variants-section').addClass('d-none');
            // Make price and cost required when no variants
            $('[name=price]').attr('required', 'required');
            $('[name=cost]').attr('required', 'required');
        }
    });

    // Add more variant
    $(document).on('click', '.add-more-variant', function(){
        var template = `
            <div class="variant-group params-card mb-3 p-3 border rounded">
                <input type="hidden" name="variant_id[]" value="">
                <div class="row">
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-danger btn-xs remove-variant">-</button>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">{{ _lang('Variant Name') }}</label>
                            <input type="text" class="form-control" name="variant_name[]" placeholder="e.g. Small, Medium, Large">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">{{ _lang('Price') }}<span class="required"> *</span></label>
                            <input type="number" step="0.01" class="form-control" name="variant_price[]" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">{{ _lang('Cost') }}<span class="required"> *</span></label>
                            <input type="number" step="0.01" class="form-control" name="variant_cost[]" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">{{ _lang('Ingredient Quantity') }}</label>
                            <input type="number" step="0.01" class="form-control" name="variant_ingredient_quantity[]">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">{{ _lang('Is Default') }}</label>
                            <select class="form-control" name="variant_is_default[]">
                                <option value="0">{{ _lang('No') }}</option>
                                <option value="1">{{ _lang('Yes') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $(this).closest('.col-md-12').before(template);
    });
    
    // Remove variant
    $(document).on('click', '.remove-variant', function(){
        $(this).closest('.variant-group').remove();
    });
</script>
@endsection
