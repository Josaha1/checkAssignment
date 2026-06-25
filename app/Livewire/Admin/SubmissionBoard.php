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
    public string $sortBy = 'student_code';
    public string $sortDir = 'asc';

    public function updatedSubjectId(): void
    {
        $this->assignmentId = null;
    }

    // เรียงได้เฉพาะคอลัมน์นักศึกษา (สถานะ/เวลา/ไฟล์ มาจาก submission คนละ query)
    public function sort(string $column): void
    {
        if (! in_array($column, ['student_code', 'full_name', 'study_group'], true)) {
            return;
        }
        $this->sortDir = $this->sortBy === $column && $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
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
                ->orderBy($this->sortBy, $this->sortDir)->get();

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
