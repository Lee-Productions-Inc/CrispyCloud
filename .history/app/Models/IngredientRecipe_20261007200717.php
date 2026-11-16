<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientRecipe extends Model
{
    protected $table = 'ingredient_recipes';

    protected $primaryKey = 'recipe_id';

    protected $fillable = [
        'product_id',
        'ingredient_id',
        'quantity_required',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id',
            'product_id'
        );
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(
            Ingredient::class,
            'ingredient_id',
            'ingredient_id'
        );
    }
}