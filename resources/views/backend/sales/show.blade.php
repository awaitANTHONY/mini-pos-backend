@extends('layouts.app')

@section('content')
<h2 class="card-title d-none">{{ _lang('Sale Details') }}</h2>
<div class="row">
	<div class="col-md-12 mb-2">
		<a href="{{ route('sales.index') }}" class="btn btn-secondary btn-sm">
			<i class="fas fa-arrow-left mr-1"></i>
			{{ _lang('Back to Sales') }}
		</a>
		<a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-info btn-sm">
			<i class="fas fa-edit mr-1"></i>
			{{ _lang('Edit Sale') }}
		</a>
		@if($sale->payment_status != 'paid')
		<a href="{{ route('payments.create', ['sale_id' => $sale->id]) }}" class="btn btn-primary btn-sm ajax-modal" data-title="{{ _lang('Add Payment') }}">
			<i class="fas fa-plus mr-1"></i>
			{{ _lang('Add Payment') }}
		</a>
		@endif
	</div>

	<div class="col-md-12">
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col-md-6">
						<table class="table table-bordered">
							<tr>
								<td width="40%"><strong>{{ _lang('Invoice No') }}:</strong></td>
								<td>{{ $sale->invoice_no }}</td>
							</tr>
							<tr>
								<td><strong>{{ _lang('Customer Name') }}:</strong></td>
								<td>{{ $sale->customer_name ?? 'N/A' }}</td>
							</tr>
							<tr>
								<td><strong>{{ _lang('Customer Phone') }}:</strong></td>
								<td>{{ $sale->customer_phone ?? 'N/A' }}</td>
							</tr>
							<tr>
								<td><strong>{{ _lang('Sale Date') }}:</strong></td>
								<td>{{ $sale->sale_date }}</td>
							</tr>
						</table>
					</div>
					<div class="col-md-6">
						<table class="table table-bordered">
							<tr>
								<td width="40%"><strong>{{ _lang('Total Amount') }}:</strong></td>
								<td>{{ get_option('currency') }}{{ number_format($sale->total_amount, 2) }}</td>
							</tr>
							<tr>
								<td><strong>{{ _lang('Paid Amount') }}:</strong></td>
								<td>{{ get_option('currency') }}{{ number_format($sale->paid_amount, 2) }}</td>
							</tr>
							<tr>
								<td><strong>{{ _lang('Due Amount') }}:</strong></td>
								<td>{{ get_option('currency') }}{{ number_format($sale->total_amount - $sale->paid_amount, 2) }}</td>
							</tr>
							<tr>
								<td><strong>{{ _lang('Payment Status') }}:</strong></td>
								<td>
									@if($sale->payment_status == 'paid')
										<span class="badge badge-success">{{ _lang('Paid') }}</span>
									@elseif($sale->payment_status == 'partial')
										<span class="badge badge-warning">{{ _lang('Partial') }}</span>
									@else
										<span class="badge badge-danger">{{ _lang('Unpaid') }}</span>
									@endif
								</td>
							</tr>
						</table>
					</div>

					<div class="col-md-12 mt-3">
						<h5>{{ _lang('Sale Items') }}</h5>
						<div class="table-responsive">
							<table class="table table-bordered">
								<thead>
									<tr>
										<th>{{ _lang('Item') }}</th>
										<th>{{ _lang('Variant') }}</th>
										<th>{{ _lang('Quantity') }}</th>
										<th>{{ _lang('Price') }}</th>
										<th>{{ _lang('Subtotal') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach($sale->saleItems as $item)
									<tr>
										<td>{{ $item->item->name }}</td>
										<td>{{ $item->variant ? $item->variant->name : 'N/A' }}</td>
										<td>{{ $item->quantity }}</td>
										<td>{{ get_option('currency') }}{{ number_format($item->price, 2) }}</td>
										<td>{{ get_option('currency') }}{{ number_format($item->subtotal, 2) }}</td>
									</tr>
									@endforeach
								</tbody>
								<tfoot>
									<tr>
										<td colspan="4" class="text-right"><strong>{{ _lang('Total') }}:</strong></td>
										<td><strong>{{ get_option('currency') }}{{ number_format($sale->total_amount, 2) }}</strong></td>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>

					@if($sale->note)
					<div class="col-md-12 mt-3">
						<strong>{{ _lang('Note') }}:</strong>
						<p>{{ $sale->note }}</p>
					</div>
					@endif

					<div class="col-md-12 mt-3">
						<h5>{{ _lang('Payment History') }}</h5>
						@if($sale->payments->count() > 0)
						<div class="table-responsive">
							<table class="table table-bordered">
								<thead>
									<tr>
										<th>{{ _lang('Date') }}</th>
										<th>{{ _lang('Amount') }}</th>
										<th>{{ _lang('Method') }}</th>
										<th>{{ _lang('Note') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach($sale->payments as $payment)
									<tr>
										<td>{{ $payment->paid_at }}</td>
										<td>{{ get_option('currency') }}{{ number_format($payment->amount, 2) }}</td>
										<td>{{ ucfirst($payment->method) }}</td>
										<td>{{ $payment->note ?? 'N/A' }}</td>
									</tr>
									@endforeach
								</tbody>
							</table>
						</div>
						@else
						<p class="text-muted">{{ _lang('No payments recorded') }}</p>
						@endif
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
