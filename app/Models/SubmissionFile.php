<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFile extends Model
{
    protected $fillable = ['submission_id', 'drive_file_id', 'name', 'url', 'mime', 'size'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
