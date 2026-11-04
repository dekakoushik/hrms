<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ItTicketReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'it_ticket_id',
        'user_id',
        'message',
        'attachment_path',
    ];

    protected $appends = ['attachment_url'];

    public function ticket()
    {
        return $this->belongsTo(ItTicket::class, 'it_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAttachmentUrlAttribute()
    {
        if (!$this->attachment_path) {
            return null;
        }
        return url(Storage::url($this->attachment_path));
    }
}

