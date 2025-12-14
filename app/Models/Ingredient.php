<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ingredients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'unit',
        'note',
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
     * Get the stock record for this ingredient.
     */
    public function stock()
    {
        return $this->hasOne(Stock::class);
    }

    /**
     * Get the items that use this ingredient.
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get the expenses for this ingredient.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
