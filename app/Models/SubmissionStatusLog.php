<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionStatusLog extends Model
{
    protected $table = 'submission_status_logs';
    public $timestamps = false;

    protected $fillable = [
        'submission_id',
        'previous_status',
        'new_status',
        'changed_by',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function submission()
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
