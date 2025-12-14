@extends('layouts.app')

@section('content')

<div class="row">
	<div class="col-md-12 breadcrumb-box"></div>
	
	<div class="col-md-12">
		<div class="card">
			<div class="card-header">
				<h4 class="card-title">{{ _lang('POS Reports') }}</h4>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-12 mb-3">
						<ul class="nav nav-tabs" role="tablist">
							<li class="nav-item">
								<a class="nav-link active" data-toggle="tab" href="#sales-summary">{{ _lang('Sales Summary') }}</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-toggle="tab" href="#ingredients-consumed">{{ _lang('Ingredients Consumed') }}</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-toggle="tab" href="#stock-status">{{ _lang('Stock Status') }}</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-toggle="tab" href="#expenses-report">{{ _lang('Expenses Report') }}</a>
							</li>
						</ul>
					</div>

					<div class="col-md-12">
						<div class="tab-content">
							<!-- Sales Summary Tab -->
							<div id="sales-summary" class="tab-pane fade show active">
								<form id="sales-summary-form" class="mb-3">
									<div class="row">
										<div class="col-md-4">
											<div class="form-group">
												<label>{{ _lang('Start Date') }}</label>
												<input type="text" name="start_date" class="form-control datepicker" value="{{ date('Y-m-d', strtotime('-30 days')) }}">
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>{{ _lang('End Date') }}</label>
												<input type="text" name="end_date" class="form-control datepicker" value="{{ date('Y-m-d') }}">
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>&nbsp;</label>
												<button type="button" class="btn btn-primary btn-block" onclick="loadSalesSummary()">{{ _lang('Generate Report') }}</button>
											</div>
										</div>
									</div>
								</form>
								<div id="sales-summary-result"></div>
							</div>

							<!-- Ingredients Consumed Tab -->
							<div id="ingredients-consumed" class="tab-pane fade">
								<form id="ingredients-consumed-form" class="mb-3">
									<div class="row">
										<div class="col-md-4">
											<div class="form-group">
												<label>{{ _lang('Start Date') }}</label>
												<input type="text" name="start_date" class="form-control datepicker" value="{{ date('Y-m-d', strtotime('-30 days')) }}">
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>{{ _lang('End Date') }}</label>
												<input type="text" name="end_date" class="form-control datepicker" value="{{ date('Y-m-d') }}">
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>&nbsp;</label>
												<button type="button" class="btn btn-primary btn-block" onclick="loadIngredientsConsumed()">{{ _lang('Generate Report') }}</button>
											</div>
										</div>
									</div>
								</form>
								<div id="ingredients-consumed-result"></div>
							</div>

							<!-- Stock Status Tab -->
							<div id="stock-status" class="tab-pane fade">
								<form id="stock-status-form" class="mb-3">
									<div class="row">
										<div class="col-md-4">
											<div class="form-group">
												<label>{{ _lang('Low Stock Threshold') }}</label>
												<input type="number" name="threshold" class="form-control" value="{{ config('pos.low_stock_threshold', 10) }}">
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>&nbsp;</label>
												<button type="button" class="btn btn-primary btn-block" onclick="loadStockStatus()">{{ _lang('Generate Report') }}</button>
											</div>
										</div>
									</div>
								</form>
								<div id="stock-status-result"></div>
							</div>

							<!-- Expenses Report Tab -->
							<div id="expenses-report" class="tab-pane fade">
								<form id="expenses-report-form" class="mb-3">
									<div class="row">
										<div class="col-md-4">
											<div class="form-group">
												<label>{{ _lang('Start Date') }}</label>
												<input type="text" name="start_date" class="form-control datepicker" value="{{ date('Y-m-d', strtotime('-30 days')) }}">
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>{{ _lang('End Date') }}</label>
												<input type="text" name="end_date" class="form-control datepicker" value="{{ date('Y-m-d') }}">
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>&nbsp;</label>
												<button type="button" class="btn btn-primary btn-block" onclick="loadExpensesReport()">{{ _lang('Generate Report') }}</button>
											</div>
										</div>
									</div>
								</form>
								<div id="expenses-report-result"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection

@section('js-script')
<script type="text/javascript">

function loadSalesSummary() {
	const formData = $('#sales-summary-form').serialize();
	$('#sales-summary-result').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>');
	
	$.ajax({
		url: _url + '/reports/sales-summary?' + formData,
		method: 'GET',
		success: function(response) {
			let html = '<table class="table table-bordered">';
			html += '<thead><tr><th>Metric</th><th>Value</th></tr></thead>';
			html += '<tbody>';
			html += '<tr><td>Total Sales</td><td>' + response.total_sales + '</td></tr>';
			html += '<tr><td>Total Revenue</td><td>{{ get_option("currency") }}' + parseFloat(response.total_revenue || 0).toFixed(2) + '</td></tr>';
			html += '<tr><td>Total Cost</td><td>{{ get_option("currency") }}' + parseFloat(response.total_cost || 0).toFixed(2) + '</td></tr>';
			html += '<tr><td>Total Profit</td><td>{{ get_option("currency") }}' + parseFloat(response.total_profit || 0).toFixed(2) + '</td></tr>';
			html += '<tr><td>Average Sale</td><td>{{ get_option("currency") }}' + parseFloat(response.average_sale || 0).toFixed(2) + '</td></tr>';
			html += '</tbody></table>';
			
			html += '<h5 class="mt-4">Top Selling Items</h5>';
			html += '<table class="table table-bordered">';
			html += '<thead><tr><th>Item</th><th>Quantity Sold</th><th>Revenue</th></tr></thead>';
			html += '<tbody>';
			response.top_items.forEach(function(item) {
				html += '<tr><td>' + item.name + '</td><td>' + item.quantity + '</td><td>{{ get_option("currency") }}' + parseFloat(item.revenue || 0).toFixed(2) + '</td></tr>';
			});
			html += '</tbody></table>';
			
			$('#sales-summary-result').html(html);
		},
		error: function(xhr, status, error) {
			console.error('Sales Summary Error:', xhr.responseText);
			$('#sales-summary-result').html('<p class="text-danger">Error loading report: ' + error + '</p>');
		}
	});
}

function loadIngredientsConsumed() {
	const formData = $('#ingredients-consumed-form').serialize();
	$('#ingredients-consumed-result').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>');
	
	$.ajax({
		url: _url + '/reports/ingredients-consumed?' + formData,
		method: 'GET',
		success: function(response) {
			let html = '<table class="table table-bordered">';
			html += '<thead><tr><th>Ingredient</th><th>Unit</th><th>Quantity Consumed</th><th>Current Stock</th></tr></thead>';
			html += '<tbody>';
			response.forEach(function(item) {
				html += '<tr>';
				html += '<td>' + item.name + '</td>';
				html += '<td>' + item.unit + '</td>';
				html += '<td>' + item.consumed + '</td>';
				html += '<td>' + item.current_stock + '</td>';
				html += '</tr>';
			});
			html += '</tbody></table>';
			$('#ingredients-consumed-result').html(html);
		},
		error: function(xhr, status, error) {
			console.error('Ingredients Consumed Error:', xhr.responseText);
			$('#ingredients-consumed-result').html('<p class="text-danger">Error loading report: ' + error + '</p>');
		}
	});
}

function loadStockStatus() {
	const formData = $('#stock-status-form').serialize();
	$('#stock-status-result').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>');
	
	$.ajax({
		url: _url + '/reports/stock-status?' + formData,
		method: 'GET',
		success: function(response) {
			let html = '<table class="table table-bordered">';
			html += '<thead><tr><th>Ingredient</th><th>Unit</th><th>Current Stock</th><th>Status</th></tr></thead>';
			html += '<tbody>';
			response.forEach(function(item) {
				let statusClass = item.is_low_stock ? 'text-danger' : 'text-success';
				let statusText = item.is_low_stock ? 'Low Stock' : 'Normal';
				html += '<tr>';
				html += '<td>' + item.name + '</td>';
				html += '<td>' + item.unit + '</td>';
				html += '<td>' + item.quantity + '</td>';
				html += '<td class="' + statusClass + '">' + statusText + '</td>';
				html += '</tr>';
			});
			html += '</tbody></table>';
			$('#stock-status-result').html(html);
		},
		error: function(xhr, status, error) {
			console.error('Stock Status Error:', xhr.responseText);
			$('#stock-status-result').html('<p class="text-danger">Error loading report: ' + error + '</p>');
		}
	});
}

function loadExpensesReport() {
	const formData = $('#expenses-report-form').serialize();
	$('#expenses-report-result').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>');
	
	$.ajax({
		url: _url + '/reports/expenses?' + formData,
		method: 'GET',
		success: function(response) {
			let html = '<table class="table table-bordered">';
			html += '<thead><tr><th>Total Expenses</th><th>{{ get_option("currency") }}' + parseFloat(response.total_expenses || 0).toFixed(2) + '</th></tr></thead>';
			html += '</table>';
			
			html += '<h5 class="mt-4">Expense Breakdown</h5>';
			html += '<table class="table table-bordered">';
			html += '<thead><tr><th>Ingredient</th><th>Total Amount</th><th>Quantity Purchased</th></tr></thead>';
			html += '<tbody>';
			response.breakdown.forEach(function(item) {
				html += '<tr>';
				html += '<td>' + (item.ingredient_name || 'Other Expenses') + '</td>';
				html += '<td>{{ get_option("currency") }}' + parseFloat(item.total || 0).toFixed(2) + '</td>';
				html += '<td>' + (item.quantity || 'N/A') + '</td>';
				html += '</tr>';
			});
			html += '</tbody></table>';
			$('#expenses-report-result').html(html);
		},
		error: function(xhr, status, error) {
			console.error('Expenses Report Error:', xhr.responseText);
			$('#expenses-report-result').html('<p class="text-danger">Error loading report: ' + error + '</p>');
		}
	});
}

// Load first report on page load
$(document).ready(function() {
	loadSalesSummary();
});

</script>
@endsection
