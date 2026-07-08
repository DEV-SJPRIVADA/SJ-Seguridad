<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_request_id',
        'supply_product_id',
        'custom_product_name',
        'is_not_in_catalog',
        'current_inventory',
        'requested_quantity',
        'approved_quantity',
        'unit_cost',
    ];

    protected $casts = [
        'is_not_in_catalog' => 'boolean',
        'unit_cost' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SupplyRequest::class, 'supply_request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }

    public function displayName(): string
    {
        if ($this->is_not_in_catalog || $this->custom_product_name) {
            return (string) $this->custom_product_name;
        }

        return (string) ($this->product?->name ?? 'Producto');
    }
}
