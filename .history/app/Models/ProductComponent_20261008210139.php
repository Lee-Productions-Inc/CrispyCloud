<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductComponent extends Model
{
    protected $table = 'product_components';

    protected $primaryKey = 'component_id';

    protected $fillable = [
        'product_id',
        'component_name',
        'is_removable',
    ];

    protected $casts = [
        'is_removable' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id',
            'product_id'
        );
    }

    public function removals(): HasMany
    {
        return $this->hasMany(
            OrderItemRemoval::class,
            'component_id',
            'component_id'
        );
    }
}