<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemCustomization extends Model
{
    protected $table = 'order_item_customizations';

    protected $primaryKey = 'item_customization_id';

    protected $fillable = [
        'order_item_id',
        'addon_id',
        'addon_quantity',
        'addon_price',
    ];

    protected $casts = [
        'addon_price' => 'decimal:2',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(
            OrderItem::class,
            'order_item_id',
            'order_item_id'
        );
    }

    public function addOn(): BelongsTo
    {
        return $this->belongsTo(
            AddOn::class,
            'addon_id',
            'addon_id'
        );
    }
}