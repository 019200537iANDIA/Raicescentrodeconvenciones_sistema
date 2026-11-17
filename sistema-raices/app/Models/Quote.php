<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Quote extends Model
{
    protected $fillable = [
        'inquiry_id', 'parent_quote_id', 'quote_number', 'event_type_id', 'event_date', 'total_attendees',
        'venue_configuration_id', 'adult_count', 'child_count', 'quote_status', 'created_by', 'deleted_by',
    ];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote): void {
            $quote->quote_number ??= 'COT-TMP-' . strtoupper(Str::random(12));
            $quote->created_by ??= auth()->id();
        });

        static::created(function (Quote $quote): void {
            $quote->quote_number = 'COT-' . $quote->created_at->format('Y') . '-L' . strtoupper(base_convert((string) $quote->getKey(), 10, 36));
            $quote->saveQuietly();
        });
    }

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function venueConfiguration()
    {
        return $this->belongsTo(VenueConfiguration::class);
    }

    public function versions()
    {
        return $this->hasMany(QuoteVersion::class);
    }
}
