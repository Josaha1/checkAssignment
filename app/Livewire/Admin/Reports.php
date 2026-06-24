<?php

namespace App\Livewire\Admin;

use App\Models\Assignment;
use App\Models\Subject;
use App\Models\Submission;
use Livewire\Component;

class Reports extends Component
{
    public ?int $subjectId = null;

    public function render()
    {
        $subjects = Subject::withCount(['assignments', 'students'])->orderBy('code')->get()
            ->map(function ($s) {
                $assignmentIds = $s->assignments()->pluck('id');
                $s->submission_total = Submission::whereIn('assignment_id', $assignmentIds)->whereNotNull('submitted_at')->count();
                $s->graded_total = Submission::whereIn('assignment_id', $assignmentIds)->whereNotNull('score')->count();
                return $s;
            });

        // รายงานต่อชิ้นงาน เมื่อเลือกวิชา (เฉลี่ย/สูงสุด/ต่ำสุด)
        $assignmentStats = collect();
        if ($this->subjectId) {
            $assignmentStats = Assignment::where('subject_id', $this->subjectId)->orderBy('id')->get()
                ->map(function ($a) {
                    $scores = Submission::where('assignment_id', $a->id)->whereNotNull('score');
                    return [
                        'assignment' => $a,
                        'submitted' => Submission::where('assignment_id', $a->id)->whereNotNull('submitted_at')->count(),
                        'graded' => (clone $scores)->count(),
                        'avg' => round((clone $scores)->avg('score') ?? 0, 2),
                        'max' => (clone $scores)->max('score'),
                        'min' => (clone $scores)->min('score'),
                    ];
                });
        }

        return view('livewire.admin.reports', compact('subjects', 'assignmentStats'));
    }
}
