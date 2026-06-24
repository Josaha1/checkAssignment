<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionHistory extends Model
{
    public const UPDATED_AT = null; // append-only — มีแค่ created_at

    protected $fillable = ['submission_id', 'action', 'actor', 'detail'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
