<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'version',
        'uploaded_by',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(PolicyFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
