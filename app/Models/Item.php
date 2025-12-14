<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'image_url',
        'category_id',
        'ingredient_id',
        'ingredient_quantity',
        'had_variants',
        'price',
        'cost',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'had_variants' => 'boolean',
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
     * Get the category that owns the item.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the ingredient that this item consumes.
     */
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Get the variants for the item.
     */
    public function variants()
    {
        return $this->hasMany(ItemVariant::class);
    }

    /**
     * Get the sale items for the item.
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the image URL attribute.
     */
    public function getImageUrlAttribute($image_url)
    {
        if ($image_url != '') {
            return asset($image_url);
        }
        return asset('public/default/item.png');
    }
}
