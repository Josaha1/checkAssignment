<?php

namespace App\Livewire\Admin;

use App\Models\Assignment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use Livewire\Component;

class SubmissionBoard extends Component
{
    public ?int $subjectId = null;
    public ?int $assignmentId = null;
    public ?string $group = null;

    public function updatedSubjectId(): void
    {
        $this->assignmentId = null;
    }

    public function render()
    {
        $subjects = Subject::orderBy('code')->get();
        $groups = Student::whereNotNull('study_group')->distinct()->orderBy('study_group')->pluck('study_group');
        $assignments = $this->subjectId
            ? Assignment::where('subject_id', $this->subjectId)->orderBy('id')->get()
            : collect();

        $rows = collect();
        $submittedCount = 0;
        $missingCount = 0;

        if ($this->assignmentId) {
            $students = Student::query()
                ->whereHas('subjects', fn ($q) => $q->whereKey($this->subjectId))
                ->when($this->group, fn ($q) => $q->where('study_group', $this->group))
                ->orderBy('student_code')->get();

            $subs = Submission::with('files', 'histories')
                ->where('assignment_id', $this->assignmentId)
                ->whereNotNull('submitted_at') // ลบไฟล์ครบ → ไม่นับว่าส่ง (สถานะตรงกับหน้าอื่น)
                ->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');

            $rows = $students->map(fn ($st) => [
                'student' => $st,
                'submission' => $subs[$st->id] ?? null,
            ]);

            $submittedCount = $subs->count();
            $missingCount = $students->count() - $submittedCount;
        }

        return view('livewire.admin.submission-board', compact(
            'subjects', 'groups', 'assignments', 'rows', 'submittedCount', 'missingCount'
        ));
    }
}
