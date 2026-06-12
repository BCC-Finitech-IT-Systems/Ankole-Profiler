<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMSDeliveryReport extends Model
{
    use HasFactory;

    // Eloquent would otherwise derive "s_m_s_delivery_reports" from the
    // class name; the migration creates "sms_delivery_reports".
    protected $table = 'sms_delivery_reports';

    protected $fillable = [
        'message_id',
        'phone_number',
        'status',
        'network_code',
        'failure_reason',
        'retry_count',
        'delivered_at',
        'webhook_payload'
    ];

    protected $casts = [
        'webhook_payload' => 'array',
        'delivered_at' => 'datetime',
        'retry_count' => 'integer'
    ];

    public function isDelivered(): bool
    {
        return in_array($this->status, ['Success', 'Delivered']);
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['Failed', 'DeliveryFailure', 'Rejected']);
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['Sent', 'Queued', 'Buffered']);
    }
}
