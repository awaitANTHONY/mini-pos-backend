@extends('layouts.app')

@section('content')

<div class="row">
	<div class="col-md-6 breadcrumb-box"></div>
	<div class="col-md-6 mb-2 text-right">
		<h4 class="card-title d-none">{{ _lang('Expenses List') }}</h4>
		<a class="btn btn-primary btn-sm" href="{{ route('expenses.create') }}">
			<i class="fas fa-plus mr-1"></i>
			{{ _lang('Add New') }}
		</a>
	</div>
	<div class="col-md-12">
		<div class="card">
			<div class="card-body">
				<table class="table table-bordered" id="data-table">
					<thead>
						<tr>
							<th>{{ _lang('Ingredient') }}</th>
        					<th>{{ _lang('Quantity') }}</th>
        					<th>{{ _lang('Unit Price') }}</th>
        					<th>{{ _lang('Total Amount') }}</th>
        					<th>{{ _lang('Supplier') }}</th>
        					<th>{{ _lang('Date') }}</th>
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
		ajax: _url + "/expenses",
		"columns" : [
			{ data : "ingredient_name", name : "ingredient_name" },
        	{ data : "quantity", name : "quantity" },
        	{ data : "unit_price", name : "unit_price" },
        	{ data : "total_amount", name : "total_amount" },
        	{ data : "supplier_name", name : "supplier_name" },
        	{ data : "expense_date", name : "expense_date" },
			{ data : "action", name : "action", orderable : false, searchable : false, className : "text-center" }
		],
		responsive: true,
		"bStateSave": true,
		"bAutoWidth":false,	
		"ordering": false
	});
</script>
@endsection
