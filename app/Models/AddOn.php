<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AddOn extends Model
{
    protected $table = 'add_ons';

    protected $primaryKey = 'addon_id';

    protected $fillable = [
        'name',
        'price',
        'is_available',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function customizations(): HasMany
    {
        return $this->hasMany(
            OrderItemCustomization::class,
            'addon_id',
            'addon_id'
        );
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_add_ons',
            'addon_id',
            'product_id',
            'addon_id',
            'product_id'
        );
    }
}
