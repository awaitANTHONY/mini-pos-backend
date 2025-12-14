<div class="row">
	<div class="col-md-6">
		<table class="table table-bordered">
			<tr>
				<td><strong>{{ _lang('Invoice No') }}:</strong></td>
				<td>{{ $payment->sale->invoice_no }}</td>
			</tr>
			<tr>
				<td><strong>{{ _lang('Customer') }}:</strong></td>
				<td>{{ $payment->sale->customer_name ?? 'N/A' }}</td>
			</tr>
			<tr>
				<td><strong>{{ _lang('Amount') }}:</strong></td>
				<td>{{ get_option('currency') }}{{ number_format($payment->amount, 2) }}</td>
			</tr>
			<tr>
				<td><strong>{{ _lang('Payment Method') }}:</strong></td>
				<td>{{ ucfirst($payment->payment_method) }}</td>
			</tr>
		</table>
	</div>
	<div class="col-md-6">
		<table class="table table-bordered">
			<tr>
				<td><strong>{{ _lang('Payment Date') }}:</strong></td>
				<td>{{ $payment->payment_date }}</td>
			</tr>
			<tr>
				<td><strong>{{ _lang('Reference') }}:</strong></td>
				<td>{{ $payment->reference ?? 'N/A' }}</td>
			</tr>
			<tr>
				<td><strong>{{ _lang('Received By') }}:</strong></td>
				<td>{{ $payment->creator->name ?? 'N/A' }}</td>
			</tr>
			<tr>
				<td><strong>{{ _lang('Created At') }}:</strong></td>
				<td>{{ $payment->created_at->format('Y-m-d H:i:s') }}</td>
			</tr>
		</table>
	</div>

	@if($payment->note)
	<div class="col-md-12">
		<strong>{{ _lang('Note') }}:</strong>
		<p>{{ $payment->note }}</p>
	</div>
	@endif

	<div class="col-md-12">
		<h5>{{ _lang('Sale Summary') }}</h5>
		<table class="table table-bordered">
			<tr>
				<td><strong>{{ _lang('Total Amount') }}:</strong></td>
				<td>{{ get_option('currency') }}{{ number_format($payment->sale->total_amount, 2) }}</td>
			</tr>
			<tr>
				<td><strong>{{ _lang('Paid Amount') }}:</strong></td>
				<td>{{ get_option('currency') }}{{ number_format($payment->sale->paid_amount, 2) }}</td>
			</tr>
			<tr>
				<td><strong>{{ _lang('Due Amount') }}:</strong></td>
				<td>{{ get_option('currency') }}{{ number_format($payment->sale->total_amount - $payment->sale->paid_amount, 2) }}</td>
			</tr>
			<tr>
				<td><strong>{{ _lang('Payment Status') }}:</strong></td>
				<td>
					@if($payment->sale->payment_status == 'paid')
						<span class="badge badge-success">{{ _lang('Paid') }}</span>
					@elseif($payment->sale->payment_status == 'partial')
						<span class="badge badge-warning">{{ _lang('Partial') }}</span>
					@else
						<span class="badge badge-danger">{{ _lang('Unpaid') }}</span>
					@endif
				</td>
			</tr>
		</table>
	</div>

	<div class="col-md-12">
		<a href="{{ route('sales.show', $payment->sale_id) }}" class="btn btn-primary btn-sm ajax-modal" data-title="{{ _lang('View Sale Details') }}">
			<i class="fas fa-eye mr-1"></i>
			{{ _lang('View Sale Details') }}
		</a>
	</div>
</div>
