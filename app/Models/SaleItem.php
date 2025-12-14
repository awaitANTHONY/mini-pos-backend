<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sale_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sale_id',
        'item_id',
        'variant_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'total_price',
        'total_cost',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the sale that owns the sale item.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the variant.
     */
    public function variant()
    {
        return $this->belongsTo(ItemVariant::class, 'variant_id');
    }

    /**
     * Accessor for price (alias for unit_price).
     */
    public function getPriceAttribute()
    {
        return $this->unit_price;
    }

    /**
     * Accessor for subtotal (alias for total_price).
     */
    public function getSubtotalAttribute()
    {
        return $this->total_price;
    }
}
