@extends('layouts.app')

@section('content')
<div class="row">
	<div class="col-md-12">
		<div class="card">
			<div class="card-header">
				<h4 class="header-title">{{ _lang('Add New Expense') }}</h4>
			</div>
			<div class="card-body">
				<form method="post" autocomplete="off" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
					@csrf
					<div class="row">
						
						

						<div class="col-md-6">
							<div class="form-group">
								<label class="control-label">{{ _lang('Expense Date') }}</label>
								<input type="text" name="expense_date" class="form-control datepicker" value="{{ old('expense_date', date('Y-m-d')) }}" required>
							</div>
						</div>
                        <div class="col-md-6">
							<div class="form-group">
								<label class="control-label">{{ _lang('Total Amount') }}</label>
								<input type="number" step="0.01" name="total_amount" class="form-control" value="{{ old('total_amount') }}" required>
							</div>
						</div>

						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label">{{ _lang('Description') }}</label>
								<textarea name="description" class="form-control" rows="3" required>{{ old('description') }}</textarea>
							</div>
						</div>
                        <div class="col-md-6">
							<div class="form-group">
								<label class="control-label">{{ _lang('Ingredient') }}</label>
								<select class="form-control select2" name="ingredient_id" data-selected="{{ old('ingredient_id') }}">
									<option value="">{{ _lang('Select One (Optional)') }}</option>
									<?php create_option('ingredients', 'id', 'name', old('ingredient_id')); ?>
								</select>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label class="control-label">{{ _lang('Quantity') }}</label>
								<input type="number" step="0.01" name="quantity" class="form-control" value="{{ old('quantity') }}" placeholder="{{ _lang('For ingredient purchases') }}">
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label class="control-label">{{ _lang('Unit Price') }}</label>
								<input type="number" step="0.01" name="unit_price" class="form-control" value="{{ old('unit_price') }}" placeholder="{{ _lang('For ingredient purchases') }}">
							</div>
						</div>

						

						<div class="col-md-6">
							<div class="form-group">
								<label class="control-label">{{ _lang('Supplier Name') }}</label>
								<input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name') }}">
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label class="control-label">{{ _lang('Supplier Contact') }}</label>
								<input type="text" name="supplier_contact" class="form-control" value="{{ old('supplier_contact') }}">
							</div>
						</div>

						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label">{{ _lang('Note') }}</label>
								<textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
							</div>
						</div>

						<div class="col-md-12">
							<div class="form-group">
								<a href="{{ route('expenses.index') }}" class="btn btn-danger btn-sm">{{ _lang('Cancel') }}</a>
								<button type="submit" class="btn btn-primary btn-sm">{{ _lang('Save Expense') }}</button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	$('.datepicker').flatpickr({
		dateFormat: 'Y-m-d',
	});
});
</script>
@endsection