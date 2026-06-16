<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    protected $fillable = [
        'student_code', 'full_name', 'email', 'password',
        'faculty', 'major', 'program', 'study_group',
    ];

    protected $hidden = ['password', 'remember_token'];

    // cast 'hashed' → set password เป็น plaintext แล้ว hash อัตโนมัติ (import/seed ใช้รหัสนักศึกษา)
    protected $casts = ['password' => 'hashed'];

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'enrollments')->withTimestamps();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
