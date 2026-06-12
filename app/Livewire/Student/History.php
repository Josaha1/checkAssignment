<?php

namespace App\Livewire\Student;

use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class History extends Component
{
    public function render()
    {
        // ประวัติการส่งของตัวเอง — ไม่ดึง/ไม่แสดงคะแนน
        $submissions = Submission::with('assignment.subject', 'files')
            ->where('student_id', Auth::guard('student')->id())
            ->latest('submitted_at')
            ->get();

        return view('livewire.student.history', compact('submissions'));
    }
}
