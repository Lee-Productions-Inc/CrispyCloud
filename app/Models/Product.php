<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'products';

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'base_price',
        'is_available',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id', 'product_id');
    }

    public function ingredientRecipes(): HasMany
    {
        return $this->hasMany(
            IngredientRecipe::class,
            'product_id',
            'product_id'
        );
    }

    public function components()
    {
        return $this->hasMany(
            ProductComponent::class,
            'product_id',
            'product_id'
        );
    }

    public function addOns()
    {
        return $this->belongsToMany(
            AddOn::class,
            'product_add_ons',
            'product_id',
            'addon_id',
            'product_id',
            'addon_id'
        );
    }
}
