<form method="post" class="ajax-submit" autocomplete="off" action="{{ route('payments.store') }}" enctype="multipart/form-data">
	@csrf
	<div class="row">
		
		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Sale') }}</label>
				<select class="form-control select2" name="sale_id" data-selected="{{ old('sale_id', $sale_id ?? '') }}" required>
					<option value="">{{ _lang('Select Sale') }}</option>
					<?php 
						$where = ['payment_status' => ['!=', 'paid']];
						create_option('sales', 'id', ['invoice_no'], old('sale_id', $sale_id ?? ''), $where); 
					?>
				</select>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Amount') }}</label>
				<input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Payment Method') }}</label>
				<select class="form-control select2" name="payment_method" data-selected="{{ old('payment_method', 'cash') }}" required>
					<option value="cash">{{ _lang('Cash') }}</option>
					<option value="card">{{ _lang('Card') }}</option>
					<option value="mobile">{{ _lang('Mobile Payment') }}</option>
					<option value="bank">{{ _lang('Bank Transfer') }}</option>
				</select>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Payment Date') }}</label>
				<input type="text" name="payment_date" class="form-control datepicker" value="{{ old('payment_date', date('Y-m-d')) }}" required>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-control-label">{{ _lang('Reference') }}</label>
				<input type="text" name="reference" class="form-control" value="{{ old('reference') }}">
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
				<button type="submit" class="btn btn-primary btn-sm">{{ _lang('Save Payment') }}</button>
			</div>
		</div>
    </div>
</form>
