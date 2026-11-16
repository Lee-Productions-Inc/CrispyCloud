<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    protected $table = 'inventory_logs';

    protected $primaryKey = 'log_id';

    protected $fillable = [
        'ingredient_id',
        'updated_by_user_id',
        'log_type',
        'quantity_changed',
        'log_date',
        'reason',
    ];

    protected $casts = [
        'quantity_changed' => 'decimal:3',
        'log_date' => 'datetime',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(
            Ingredient::class,
            'ingredient_id',
            'ingredient_id'
        );
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(
            AdminUser::class,
            'updated_by_user_id',
            'user_id'
        );
    }
}