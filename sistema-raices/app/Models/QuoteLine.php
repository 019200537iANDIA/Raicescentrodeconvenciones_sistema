<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteLine extends Model
{
    protected $fillable = [
        'quote_version_id', 'service_id', 'description_snapshot', 'quantity', 'internal_unit_price',
        'line_discount', 'line_total', 'display_quantity_to_customer', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2', 'internal_unit_price' => 'decimal:2', 'line_discount' => 'decimal:2',
            'line_total' => 'decimal:2', 'display_quantity_to_customer' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (QuoteLine $line): void {
            $gross = round((float) $line->quantity * (float) $line->internal_unit_price, 2);
            $line->line_discount = min($gross, max(0, (float) $line->line_discount));
            $line->line_total = max(0, round($gross - (float) $line->line_discount, 2));
        });

        static::saved(function (QuoteLine $line): void {
            $line->version?->recalculateTotals();
        });

        static::deleted(function (QuoteLine $line): void {
            $line->version?->recalculateTotals();
        });
    }

    public function version()
    {
        return $this->belongsTo(QuoteVersion::class, 'quote_version_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
