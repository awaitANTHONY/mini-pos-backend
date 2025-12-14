<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVariant extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'item_variants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_id',
        'name',
        'price',
        'cost',
        'ingredient_quantity',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_default' => 'boolean',
        'ingredient_quantity' => 'decimal:4',
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
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
     * Get the item that owns the variant.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the sale items for the variant.
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'variant_id');
    }
}
