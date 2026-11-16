<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemRemoval extends Model
{
    protected $table = 'order_item_removals';

    protected $primaryKey = 'removal_id';

    protected $fillable = [
        'order_item_id',
        'component_id',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(
            OrderItem::class,
            'order_item_id',
            'order_item_id'
        );
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(
            ProductComponent::class,
            'component_id',
            'component_id'
        );
    }
}