<form method="post" class="ajax-submit" autocomplete="off" action="{{ route('item-variants.update', $variant->id) }}" enctype="multipart/form-data">
	@csrf
	@method('PUT')
	<div class="row">
		
		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Item') }}</label>
				<select class="form-control select2" name="item_id" data-selected="{{ $variant->item_id }}" required>
					<option value="">{{ _lang('Select One') }}</option>
					<?php create_option('items', 'id', 'name', $variant->item_id); ?>
				</select>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Variant Name') }}</label>
				<input type="text" name="name" class="form-control" value="{{ $variant->name }}" required>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Price') }}</label>
				<input type="number" step="0.01" name="price" class="form-control" value="{{ $variant->price }}" placeholder="{{ _lang('Leave empty to use item price') }}">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Cost') }}</label>
				<input type="number" step="0.01" name="cost" class="form-control" value="{{ $variant->cost }}" placeholder="{{ _lang('Leave empty to use item cost') }}">
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Ingredient Quantity') }}</label>
				<input type="number" step="0.01" name="ingredient_quantity" class="form-control" value="{{ $variant->ingredient_quantity }}" placeholder="{{ _lang('Leave empty to use item quantity') }}">
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Is Default') }}</label>
				<select class="form-control select2" name="is_default" data-selected="{{ $variant->is_default }}" required>
					<option value="0">{{ _lang('No') }}</option>
					<option value="1">{{ _lang('Yes') }}</option>
				</select>
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
