<?php

namespace App\Livewire\Admin;

use App\Models\Assignment;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $stats = [
            'students' => Student::count(),
            'subjects' => Subject::count(),
            'rooms' => Room::count(),
            'assignments' => Assignment::count(),
            'submissions' => Submission::count(),
            'ungraded' => Submission::whereNull('score')->count(),
        ];

        // งานล่าสุดที่ส่งเข้ามา
        $recent = Submission::with(['student', 'assignment.subject'])
            ->latest('submitted_at')->limit(8)->get();

        return view('livewire.admin.dashboard', compact('stats', 'recent'));
    }
}
