<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ItTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'employee_id',
        'created_by_user_id',
        'category',
        'priority',
        'status',
        'subject',
        'description',
        'attachment_path',
        'assigned_to_user_id',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    protected $appends = ['attachment_url'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function replies()
    {
        return $this->hasMany(ItTicketReply::class, 'it_ticket_id')->orderBy('created_at', 'asc');
    }

    public function getAttachmentUrlAttribute()
    {
        if (!$this->attachment_path) {
            return null;
        }
        return url(Storage::url($this->attachment_path));
    }
}

