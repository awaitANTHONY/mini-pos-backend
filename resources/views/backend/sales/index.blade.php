@extends('layouts.app')

@section('content')

<div class="row">
	<div class="col-md-6 breadcrumb-box"></div>
	<div class="col-md-6 mb-2 text-right">
		<h4 class="card-title d-none">{{ _lang('Sales List') }}</h4>
		<a class="btn btn-primary btn-sm" href="{{ route('sales.create') }}">
			<i class="fas fa-plus mr-1"></i>
			{{ _lang('New Sale') }}
		</a>
	</div>
	<div class="col-md-12">
		<div class="card">
			<div class="card-body">
				<table class="table table-bordered" id="data-table">
					<thead>
						<tr>
							<th>{{ _lang('Invoice No') }}</th>
        					<th>{{ _lang('Total Amount') }}</th>
        					<th>{{ _lang('Paid Amount') }}</th>
        					<th>{{ _lang('Payment Status') }}</th>
        					<th>{{ _lang('Sale Date') }}</th>
							<th class="text-center">{{ _lang('Action') }}</th>
						</tr>
					</thead>
				</table>
			</div>
		</div>
	</div>
</div>

@endsection

@section('js-script')
<script type="text/javascript">
	$('#data-table').DataTable({
		processing: true,
		serverSide: true,
		ajax: _url + "/sales",
		"columns" : [
			{ data : "invoice_no", name : "invoice_no" },
        	{ data : "total_amount", name : "total_amount" },
        	{ data : "paid_amount", name : "paid_amount" },
        	{ data : "payment_status", name : "payment_status" },
        	{ data : "created_at", name : "created_at" },
			{ data : "action", name : "action", orderable : false, searchable : false, className : "text-center" }
		],
		responsive: true,
		"bStateSave": true,
		"bAutoWidth":false,	
		"ordering": false
	});
</script>
@endsection
