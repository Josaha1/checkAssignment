<?php

namespace App\Livewire\Admin;

use App\Models\Assignment;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use Livewire\Component;

class SubmissionBoard extends Component
{
    public ?int $subjectId = null;
    public ?int $assignmentId = null;
    public ?int $roomId = null;

    public function updatedSubjectId(): void
    {
        $this->assignmentId = null;
    }

    public function render()
    {
        $subjects = Subject::orderBy('code')->get();
        $rooms = Room::orderBy('name')->get();
        $assignments = $this->subjectId
            ? Assignment::where('subject_id', $this->subjectId)->orderBy('id')->get()
            : collect();

        $rows = collect();
        $submittedCount = 0;
        $missingCount = 0;

        if ($this->assignmentId) {
            $students = Student::with('room')
                ->whereHas('subjects', fn ($q) => $q->whereKey($this->subjectId))
                ->when($this->roomId, fn ($q) => $q->where('room_id', $this->roomId))
                ->orderBy('student_code')->get();

            $subs = Submission::where('assignment_id', $this->assignmentId)
                ->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');

            $rows = $students->map(fn ($st) => [
                'student' => $st,
                'submission' => $subs[$st->id] ?? null,
            ]);

            $submittedCount = $subs->count();
            $missingCount = $students->count() - $submittedCount;
        }

        return view('livewire.admin.submission-board', compact(
            'subjects', 'rooms', 'assignments', 'rows', 'submittedCount', 'missingCount'
        ));
    }
}
