<?php

namespace App\Livewire\Student;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Subjects extends Component
{
    public function render()
    {
        $student = Auth::guard('student')->user()->load(['subjects.assignments', 'submissions']);
        // เฉพาะที่ยังส่งอยู่ — ลบไฟล์ครบ submitted_at=null → ค้าง (ตรงกับหน้าอื่น)
        $subs = $student->submissions->whereNotNull('submitted_at')->keyBy('assignment_id');
        $submittedIds = $subs->keys()->all();

        $subjects = $student->subjects->map(function ($subject) use ($submittedIds) {
            $total = $subject->assignments->count();
            $done = $subject->assignments->whereIn('id', $submittedIds)->count();
            return [
                'subject' => $subject,
                'assignments' => $subject->assignments,
                'total' => $total,
                'done' => $done,
                'percent' => $total > 0 ? round($done / $total * 100) : 0,
            ];
        });

        return view('livewire.student.subjects', compact('subjects', 'subs'));
    }
}
