@extends('layouts.app')

@section('content')

<div class="row">
	<div class="col-md-6 breadcrumb-box"></div>
	<div class="col-md-6 mb-2 text-right">
		<h4 class="card-title d-none">{{ _lang('Item Variants List') }}</h4>
		<a class="btn btn-primary btn-sm ajax-modal" href="{{ route('item-variants.create') }}" data-title="{{ _lang('Add New') }}">
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
							<th>{{ _lang('Item') }}</th>
        					<th>{{ _lang('Name') }}</th>
        					<th>{{ _lang('Price') }}</th>
        					<th>{{ _lang('Cost') }}</th>
        					<th>{{ _lang('Ingredient Quantity') }}</th>
        					<th>{{ _lang('Is Default') }}</th>
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
		ajax: _url + "/item-variants",
		"columns" : [
			{ data : "item", name : "item" },
        	{ data : "name", name : "name" },
        	{ data : "price", name : "price" },
        	{ data : "cost", name : "cost" },
        	{ data : "ingredient_quantity", name : "ingredient_quantity" },
        	{ data : "is_default", name : "is_default" },
			{ data : "action", name : "action", orderable : false, searchable : false, className : "text-center" }
		],
		responsive: true,
		"bStateSave": true,
		"bAutoWidth":false,	
		"ordering": false
	});
</script>
@endsection
