<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteVersion extends Model
{
    protected $fillable = [
        'quote_id', 'version_number', 'package_id', 'created_by', 'reason', 'currency_code',
        'subtotal', 'discount_total', 'total', 'price_per_adult', 'price_per_child',
        'advance_percent', 'advance_amount', 'sent_at', 'valid_until', 'customer_accepted_at', 'pdf_path', 'is_current',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'total' => 'decimal:2',
            'advance_percent' => 'decimal:2', 'advance_amount' => 'decimal:2',
            'sent_at' => 'datetime', 'valid_until' => 'datetime', 'customer_accepted_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuoteVersion $version): void {
            $version->version_number ??= ((int) static::query()->where('quote_id', $version->quote_id)->max('version_number')) + 1;
            $version->created_by ??= auth()->id();
            $version->sent_at ??= now();
            $version->valid_until ??= $version->sent_at->copy()->addDays(15);
            $version->is_current = true;
        });

        static::created(function (QuoteVersion $version): void {
            static::query()->where('quote_id', $version->quote_id)
                ->whereKeyNot($version->getKey())->update(['is_current' => false]);
        });

        static::saving(function (QuoteVersion $version): void {
            $version->advance_amount = round((float) $version->total * (float) $version->advance_percent / 100, 2);
        });
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function lines()
    {
        return $this->hasMany(QuoteLine::class);
    }

    public function recalculateTotals(): void
    {
        $lines = $this->lines()->get();
        $subtotal = round($lines->sum(fn (QuoteLine $line): float => (float) $line->quantity * (float) $line->internal_unit_price), 2);
        $discount = round($lines->sum(fn (QuoteLine $line): float => (float) $line->line_discount), 2);
        $total = max(0, round($subtotal - $discount, 2));

        $this->forceFill([
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'total' => $total,
            'advance_amount' => round($total * (float) $this->advance_percent / 100, 2),
        ])->saveQuietly();
    }
}
