@extends('layouts.app')

@section('content')

<div class="row">
	<div class="col-md-6 breadcrumb-box"></div>
	<div class="col-md-6 mb-2 text-right">
		<h4 class="card-title d-none">{{ _lang('Stock Management') }}</h4>
		<a class="btn btn-warning btn-sm" href="{{ route('stocks.low-stock') }}">
			<i class="fas fa-exclamation-triangle mr-1"></i>
			{{ _lang('Low Stock Items') }}
		</a>
	</div>
	<div class="col-md-12">
		<div class="card">
			<div class="card-body">
				<table class="table table-bordered" id="data-table">
					<thead>
						<tr>
							<th>{{ _lang('Ingredient') }}</th>
        					<th>{{ _lang('Unit') }}</th>
        					<th>{{ _lang('Quantity') }}</th>
        					<th>{{ _lang('Last Updated') }}</th>
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
		ajax: _url + "/stocks",
		"columns" : [
			{ data : "ingredient_name", name : "ingredient_name" },
        	{ data : "unit", name : "unit" },
        	{ data : "quantity", name : "quantity" },
        	{ data : "updated_at", name : "updated_at" },
			{ data : "action", name : "action", orderable : false, searchable : false, className : "text-center" }
		],
		responsive: true,
		"bStateSave": true,
		"bAutoWidth":false,	
		"ordering": false
	});
</script>
@endsection
