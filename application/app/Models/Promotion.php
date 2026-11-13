<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'discount_value',
        'min_spend_minor',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'min_spend_minor' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isValidForAmount(int $amountMinor): bool
    {
        if (!$this->is_active) return false;
        if ($this->valid_from && $this->valid_from->isFuture()) return false;
        if ($this->valid_until && $this->valid_until->isPast()) return false;
        if ($amountMinor < $this->min_spend_minor) return false;
        return true;
    }

    public function calculateDiscount(int $amountMinor): int
    {
        if (!$this->isValidForAmount($amountMinor)) return 0;

        if ($this->type === 'PERCENTAGE') {
            return (int) round(($amountMinor * $this->discount_value) / 100);
        }

        return min($amountMinor, $this->discount_value);
    }
}
