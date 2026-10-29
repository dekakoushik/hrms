<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resignation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'resignation_date',
        'reason',
        'requested_last_working_day',
        'approved_last_working_day',
        'notice_period_days',
        'status',
        'actioned_by',
        'actioned_at',
        'action_remarks',
    ];

    protected $casts = [
        'resignation_date' => 'date',
        'requested_last_working_day' => 'date',
        'approved_last_working_day' => 'date',
        'notice_period_days' => 'integer',
        'actioned_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function actionedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }
}
