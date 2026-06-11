<?php

namespace App\Livewire\Student;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Subjects extends Component
{
    public function render()
    {
        $student = Auth::guard('student')->user()->load(['subjects.assignments', 'submissions']);
        $submittedIds = $student->submissions->pluck('assignment_id')->all();

        $subjects = $student->subjects->map(function ($subject) use ($submittedIds) {
            $total = $subject->assignments->count();
            $done = $subject->assignments->whereIn('id', $submittedIds)->count();
            return [
                'subject' => $subject,
                'assignments' => $subject->assignments,
                'submittedIds' => $submittedIds,
                'total' => $total,
                'done' => $done,
                'percent' => $total > 0 ? round($done / $total * 100) : 0,
            ];
        });

        return view('livewire.student.subjects', compact('subjects'));
    }
}
