<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'template_id',
        'user_id',
        'content',
        'message',
        'status',
        'sent_at',
        'error_message',
        'twilio_sid',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // Relations
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function template()
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
