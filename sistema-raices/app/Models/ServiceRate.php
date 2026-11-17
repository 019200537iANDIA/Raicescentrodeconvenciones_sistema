<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRate extends Model
{
    protected $fillable = ['service_id', 'package_id', 'event_type_id', 'currency_code', 'amount', 'valid_from', 'valid_until', 'approved_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'valid_from' => 'date', 'valid_until' => 'date'];
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }
}
