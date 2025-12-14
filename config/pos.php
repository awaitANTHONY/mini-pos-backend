<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allow Negative Stock
    |--------------------------------------------------------------------------
    |
    | This option controls whether the system allows negative stock values
    | when processing sales. If set to false, sales will be rejected if
    | there is insufficient stock. If true, stock can go negative.
    |
    */

    'allow_negative_stock' => env('POS_ALLOW_NEGATIVE_STOCK', false),

    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | Default threshold for low stock alerts. When ingredient stock falls
    | below this value, it will be flagged as low stock.
    |
    */

    'low_stock_threshold' => env('POS_LOW_STOCK_THRESHOLD', 10),

    /*
    |--------------------------------------------------------------------------
    | Invoice Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for automatically generated invoice numbers.
    |
    */

    'invoice_prefix' => env('POS_INVOICE_PREFIX', 'INV-'),

    /*
    |--------------------------------------------------------------------------
    | Stock Lock Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in seconds to wait for stock lock during sales processing.
    |
    */

    'stock_lock_timeout' => env('POS_STOCK_LOCK_TIMEOUT', 5),

];
