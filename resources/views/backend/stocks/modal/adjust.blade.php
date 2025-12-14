<form method="post" class="ajax-submit" autocomplete="off" action="{{ route('stocks.adjust') }}" enctype="multipart/form-data">
	@csrf
	<div class="row">
		
		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Ingredient') }}</label>
				<input type="text" class="form-control" value="{{ $stock->ingredient->name }}" readonly>
				<input type="hidden" name="ingredient_id" value="{{ $stock->ingredient_id }}">
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Current Quantity') }}</label>
				<input type="text" class="form-control" value="{{ $stock->quantity }} {{ $stock->ingredient->unit }}" readonly>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Adjustment Type') }}</label>
				<select class="form-control select2" name="type" data-selected="{{ old('type', 'add') }}" required>
					<option value="add">{{ _lang('Add Stock') }}</option>
					<option value="reduce">{{ _lang('Reduce Stock') }}</option>
				</select>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Quantity') }}</label>
				<input type="number" step="0.01" name="quantity" class="form-control" value="{{ old('quantity') }}" required>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Reason') }}</label>
				<textarea name="reason" class="form-control" rows="4" required>{{ old('reason') }}</textarea>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<button type="reset" class="btn btn-danger btn-sm">{{ _lang('Reset') }}</button>
				<button type="submit" class="btn btn-primary btn-sm">{{ _lang('Adjust Stock') }}</button>
			</div>
		</div>
    </div>
</form>
