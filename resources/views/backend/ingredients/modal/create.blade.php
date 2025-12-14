<form method="post" class="ajax-submit" autocomplete="off" action="{{ route('ingredients.store') }}" enctype="multipart/form-data">
	@csrf
	<div class="row">
		
		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Name') }}</label>
				<input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Unit') }}</label>
				<select name="unit" class="form-control select2" data-selected="{{ old('unit') }}" required>
					<option value="">{{ _lang('Select Unit') }}</option>
					<option value="kg">{{ _lang('Kilogram (kg)') }}</option>
					<option value="g">{{ _lang('Gram (g)') }}</option>
					<option value="l">{{ _lang('Liter (l)') }}</option>
					<option value="ml">{{ _lang('Milliliter (ml)') }}</option>
					<option value="pcs">{{ _lang('Pieces (pcs)') }}</option>
					<option value="box">{{ _lang('Box') }}</option>
					<option value="pack">{{ _lang('Pack') }}</option>
				</select>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Note') }}</label>
				<textarea name="note" class="form-control" rows="4">{{ old('note') }}</textarea>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<button type="reset" class="btn btn-danger btn-sm">{{ _lang('Reset') }}</button>
				<button type="submit" class="btn btn-primary btn-sm">{{ _lang('Save') }}</button>
			</div>
		</div>
    </div>
</form>
