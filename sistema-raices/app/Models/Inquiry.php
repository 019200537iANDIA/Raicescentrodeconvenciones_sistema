<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inquiry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'event_type_id',
        'source_channel',
        'event_date_requested',
        'budget_reference',
        'summary',
        'status',
        'assigned_user_id',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'event_date_requested' => 'date',
            'budget_reference' => 'decimal:2',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
