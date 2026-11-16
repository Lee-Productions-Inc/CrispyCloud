<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $table = 'ingredients';

    protected $primaryKey = 'ingredient_id';

    protected $fillable = [
        'ingredient_name',
        'current_stock',
        'unit_of_measure',
        'reorder_level',
    ];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'reorder_level' => 'decimal:3',
    ];

    public function recipes(): HasMany
    {
        return $this->hasMany(
            IngredientRecipe::class,
            'ingredient_id',
            'ingredient_id'
        );
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(
            InventoryLog::class,
            'ingredient_id',
            'ingredient_id'
        );
    }
}