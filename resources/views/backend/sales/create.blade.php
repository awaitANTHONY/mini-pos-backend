@extends('layouts.app')

@section('content')
<h2 class="card-title d-none">{{ _lang('Create Sale') }}</h2>
<div class="row">
    <div class="col-md-10">
        <div class="card">
            <div class="card-body">
                <form method="post" autocomplete="off" action="{{ route('sales.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Sale Date') }}</label>
                                <input type="text" name="sale_date" class="form-control datepicker" value="{{ old('sale_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Note') }}</label>
                                <textarea name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <hr>
                            <h5 class="mb-3">{{ _lang('Sale Items') }}</h5>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered" id="sale-items-table">
                                    <thead>
                                        <tr>
                                            <th width="30%">{{ _lang('Item') }}</th>
                                            <th width="20%">{{ _lang('Variant') }}</th>
                                            <th width="15%">{{ _lang('Quantity') }}</th>
                                            <th width="15%">{{ _lang('Price') }}</th>
                                            <th width="15%">{{ _lang('Subtotal') }}</th>
                                            <th width="5%">
                                                <button type="button" class="btn btn-primary btn-xs add-item-row">+</button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="item-row">
                                            <td>
                                                <select class="form-control  item-select" name="items[0][item_id]" required>
                                                    <option value="">{{ _lang('Select Item') }}</option>
                                                    <?php create_option('items', 'id', 'name'); ?>
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-control  variant-select" name="items[0][variant_id]">
                                                    <option value="">{{ _lang('No Variant') }}</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][quantity]" class="form-control quantity-input" value="1" min="1" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="items[0][price]" class="form-control price-input" readonly>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control subtotal-input" readonly>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-xs remove-item-row">-</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-right"><strong>{{ _lang('Total Amount') }}:</strong></td>
                                            <td colspan="2">
                                                <input type="number" step="0.01" name="total_amount" id="total-amount" class="form-control" readonly required>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-12 mt-3">
                            <hr>
                            <h5 class="mb-3">{{ _lang('Payment Information') }}</h5>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Payment Amount') }}</label>
                                <input type="number" step="0.01" name="payment_amount" id="payment-amount" class="form-control" value="0" min="0">
                                <small class="form-text text-muted">{{ _lang('Enter 0 for unpaid, partial amount for partial payment, or full amount for paid') }}</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Payment Method') }}</label>
                                <select class="form-control" name="payment_method" id="payment-method">
                                    <option value="cash">{{ _lang('Cash') }}</option>
                                    <option value="card">{{ _lang('Card') }}</option>
                                    <option value="bank_transfer">{{ _lang('Bank Transfer') }}</option>
                                    <option value="mobile_payment">{{ _lang('Mobile Payment') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">{{ _lang('Payment Status') }}</label>
                                <input type="text" id="payment-status-display" class="form-control" readonly value="Due">
                            </div>
                        </div>

                        <div class="col-md-12 mt-3">
                            <div class="form-group">
                                <button type="reset" class="btn btn-danger btn-sm">{{ _lang('Reset') }}</button>
                                <button type="submit" class="btn btn-primary btn-sm">{{ _lang('Create Sale') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js-script')
<script type="text/javascript">
let itemCounter = 1;

$(document).ready(function() {
    // Initialize datepicker
    $('.datepicker').flatpickr({
        dateFormat: 'Y-m-d'
    });

    // Add new item row
    $(document).on('click', '.add-item-row', function() {
        const newRow = $('.item-row:first').clone();
        newRow.find('select, input').each(function() {
            const name = $(this).attr('name');
            if(name) {
                $(this).attr('name', name.replace('[0]', '[' + itemCounter + ']'));
            }
            $(this).val('');
        });
        newRow.find('.variant-select').html('<option value="">{{ _lang("No Variant") }}</option>');
        newRow.find('.quantity-input').val('1');
        $('#sale-items-table tbody').append(newRow);
        
        // Re-initialize select2 for new row
        newRow.find('.select2').select2();
        
        itemCounter++;
    });

    // Remove item row
    $(document).on('click', '.remove-item-row', function() {
        if($('.item-row').length > 1) {
            $(this).closest('.item-row').remove();
            calculateTotal();
        }
    });

    // Load variants when item is selected
    $(document).on('change', '.item-select', function() {
        const row = $(this).closest('.item-row');
        const itemId = $(this).val();
        const variantSelect = row.find('.variant-select');
        const priceInput = row.find('.price-input');

        if(itemId) {
            $.ajax({
                url: '{{ url("sales/get-item-variants") }}/' + itemId,
                method: 'GET',
                success: function(response) {
                    variantSelect.html('<option value="">{{ _lang("No Variant") }}</option>');
                    
                    if(response.had_variants && response.variants.length > 0) {
                        response.variants.forEach(function(variant) {
                            variantSelect.append('<option value="' + variant.id + '" data-price="' + variant.price + '">' + variant.name + ' - {{ get_option("currency") }}' + variant.price + '</option>');
                        });
                    }
                    
                    // Set default price
                    priceInput.val(response.price || '');
                    calculateRowSubtotal(row);
                }
            });
        } else {
            variantSelect.html('<option value="">{{ _lang("No Variant") }}</option>');
            priceInput.val('');
            calculateRowSubtotal(row);
        }
    });

    // Update price when variant is selected
    $(document).on('change', '.variant-select', function() {
        const row = $(this).closest('.item-row');
        const variantId = $(this).val();
        const priceInput = row.find('.price-input');
        
        if(variantId) {
            const selectedOption = $(this).find('option:selected');
            const variantPrice = selectedOption.data('price');
            priceInput.val(variantPrice);
        } else {
            // Get item base price
            const itemId = row.find('.item-select').val();
            if(itemId) {
                $.ajax({
                    url: '{{ url("sales/get-item-variants") }}/' + itemId,
                    method: 'GET',
                    success: function(response) {
                        priceInput.val(response.price || '');
                        calculateRowSubtotal(row);
                    }
                });
            }
        }
        calculateRowSubtotal(row);
    });

    // Calculate subtotal when quantity changes
    $(document).on('input', '.quantity-input, .price-input', function() {
        const row = $(this).closest('.item-row');
        calculateRowSubtotal(row);
    });

    function calculateRowSubtotal(row) {
        const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
        const price = parseFloat(row.find('.price-input').val()) || 0;
        const subtotal = quantity * price;
        row.find('.subtotal-input').val(subtotal.toFixed(2));
        calculateTotal();
    }

    function calculateTotal() {
        let total = 0;
        $('.subtotal-input').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#total-amount').val(total.toFixed(2));
        updatePaymentStatus();
    }

    // Update payment status based on amount
    $(document).on('input', '#payment-amount', function() {
        updatePaymentStatus();
    });

    function updatePaymentStatus() {
        const totalAmount = parseFloat($('#total-amount').val()) || 0;
        const paymentAmount = parseFloat($('#payment-amount').val()) || 0;
        const statusDisplay = $('#payment-status-display');

        if (paymentAmount === 0) {
            statusDisplay.val('Due').removeClass('text-success text-warning').addClass('text-danger');
        } else if (paymentAmount >= totalAmount && totalAmount > 0) {
            statusDisplay.val('Paid').removeClass('text-danger text-warning').addClass('text-success');
        } else if (paymentAmount > 0 && paymentAmount < totalAmount) {
            statusDisplay.val('Partial Payment').removeClass('text-danger text-success').addClass('text-warning');
        } else {
            statusDisplay.val('Due').removeClass('text-success text-warning').addClass('text-danger');
        }
    }
});
</script>
@endsection
