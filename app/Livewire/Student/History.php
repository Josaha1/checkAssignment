<?php

namespace App\Livewire\Student;

use App\Models\Submission;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class History extends Component
{
    public ?int $subjectId = null;  // กรองเฉพาะวิชา
    public string $sortBy = 'submitted_at';
    public string $sortDir = 'desc';

    // วิชา/สถานะ เป็น relation/derived → เรียงใน collection
    public function sort(string $column): void
    {
        if (! in_array($column, ['subject', 'submitted_at', 'status'], true)) {
            return;
        }
        $this->sortDir = $this->sortBy === $column && $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
    }

    public function render()
    {
        $studentId = Auth::guard('student')->id();

        // ประวัติการส่งของตัวเอง — ไม่ดึง/ไม่แสดงคะแนน
        $submissions = Submission::with('assignment.subject', 'files', 'histories')
            ->where('student_id', $studentId)
            ->when($this->subjectId, fn ($q) => $q
                ->whereHas('assignment', fn ($a) => $a->where('subject_id', $this->subjectId)))
            ->get()
            ->sortBy(fn ($s) => match ($this->sortBy) {
                'subject' => $s->assignment?->subject?->code,
                'status' => $s->submitted_at === null ? 0 : ($s->score !== null ? 2 : 1),
                default => $s->submitted_at,
            }, SORT_REGULAR, $this->sortDir === 'desc')->values();

        return view('livewire.student.history', [
            'submissions' => $submissions,
            'subjects' => Subject::whereHas('students', fn ($q) => $q->whereKey($studentId))->orderBy('code')->get(),
        ]);
    }
}
