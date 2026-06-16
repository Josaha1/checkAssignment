<?php

namespace App\Livewire\Admin;

use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use Livewire\Component;

class Grading extends Component
{
    public ?int $subjectId = null;
    public ?string $group = null;
    public array $scores = []; // submissionId => score

    public function updatedSubjectId(): void
    {
        $this->scores = [];
    }

    public function saveScores(): void
    {
        foreach ($this->scores as $submissionId => $value) {
            $submission = Submission::find($submissionId);
            if (! $submission) {
                continue;
            }
            // ว่าง = ล้างคะแนนกลับเป็นยังไม่ตรวจ
            if ($value === '' || $value === null) {
                $submission->update(['score' => null, 'graded_at' => null]);
                continue;
            }
            $submission->update(['score' => (float) $value, 'graded_at' => now()]);
        }
        session()->flash('ok', 'บันทึกคะแนนเรียบร้อย');
    }

    public function render()
    {
        $subjects = Subject::orderBy('code')->get();
        $groups = Student::whereNotNull('study_group')->distinct()->orderBy('study_group')->pluck('study_group');

        $assignments = collect();
        $students = collect();
        $matrix = []; // studentId => [assignmentId => submission]

        if ($this->subjectId) {
            $subject = Subject::with('assignments')->find($this->subjectId);
            $assignments = $subject?->assignments->sortBy('id')->values() ?? collect();

            $students = Student::query()
                ->whereHas('subjects', fn ($q) => $q->whereKey($this->subjectId))
                ->when($this->group, fn ($q) => $q->where('study_group', $this->group))
                ->orderBy('student_code')->get();

            $subs = Submission::with('files')
                ->whereIn('assignment_id', $assignments->pluck('id'))
                ->whereIn('student_id', $students->pluck('id'))->get();

            foreach ($subs as $sub) {
                $matrix[$sub->student_id][$sub->assignment_id] = $sub;
                if (! array_key_exists($sub->id, $this->scores)) {
                    $this->scores[$sub->id] = $sub->score !== null ? rtrim(rtrim($sub->score, '0'), '.') : '';
                }
            }
        }

        return view('livewire.admin.grading', compact('subjects', 'groups', 'assignments', 'students', 'matrix'));
    }
}
