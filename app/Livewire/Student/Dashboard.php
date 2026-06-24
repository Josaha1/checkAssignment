<?php

namespace App\Livewire\Student;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $student = Auth::guard('student')->user()
            ->load(['subjects.assignments', 'submissions']);

        // เฉพาะที่ยังส่งอยู่ (มีไฟล์) — ลบไฟล์ครบ submitted_at=null → กลับเป็นค้าง ให้ตรงทุกหน้า
        $subs = $student->submissions->whereNotNull('submitted_at')->keyBy('assignment_id');
        $submittedIds = $subs->keys()->all();

        // จัดกลุ่มตามวิชา — แยกงานที่ยังไม่ส่ง / ส่งแล้ว (ไม่โชว์คะแนน)
        $subjects = $student->subjects->map(function ($subject) use ($submittedIds) {
            $pending = $subject->assignments->whereNotIn('id', $submittedIds)->values();
            $done = $subject->assignments->whereIn('id', $submittedIds)->values();
            return [
                'subject' => $subject,
                'pending' => $pending,
                'done' => $done,
            ];
        });

        return view('livewire.student.dashboard', [
            'student' => $student,
            'subjects' => $subjects,
            'subs' => $subs,
            'pendingTotal' => $subjects->sum(fn ($s) => $s['pending']->count()),
        ]);
    }
}
