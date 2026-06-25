<?php

namespace App\Livewire\Admin;

use App\Models\Assignment;
use App\Models\Subject;
use App\Models\Submission;
use Livewire\Component;

class Reports extends Component
{
    public ?int $subjectId = null;
    public string $search = '';     // กรองรหัส/ชื่อวิชา (ตารางสรุป)
    public string $sortBy = 'code';
    public string $sortDir = 'asc';
    public string $statSort = 'id'; // ตารางสถิติต่อชิ้นงาน (แยก state)
    public string $statDir = 'asc';

    // ตารางสรุป — sort คอลัมน์ที่คำนวณ (submission_total/graded_total) ทำใน collection
    public function sort(string $column): void
    {
        if (! in_array($column, ['code', 'name', 'students_count', 'assignments_count', 'submission_total', 'graded_total'], true)) {
            return;
        }
        $this->sortDir = $this->sortBy === $column && $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
    }

    // ตารางสถิติต่อชิ้นงาน — แยกคนละ state กับตารางสรุป
    public function sortStat(string $column): void
    {
        if (! in_array($column, ['title', 'max_score', 'submitted', 'graded', 'avg', 'max', 'min'], true)) {
            return;
        }
        $this->statDir = $this->statSort === $column && $this->statDir === 'asc' ? 'desc' : 'asc';
        $this->statSort = $column;
    }

    public function render()
    {
        $subjects = Subject::withCount(['assignments', 'students'])->orderBy('code')->get()
            ->map(function ($s) {
                $assignmentIds = $s->assignments()->pluck('id');
                $s->submission_total = Submission::whereIn('assignment_id', $assignmentIds)->whereNotNull('submitted_at')->count();
                $s->graded_total = Submission::whereIn('assignment_id', $assignmentIds)->whereNotNull('score')->count();
                return $s;
            })
            ->when($this->search, fn ($c) => $c->filter(fn ($s) => mb_stripos($s->code . ' ' . $s->name, $this->search) !== false))
            ->sortBy($this->sortBy, SORT_REGULAR, $this->sortDir === 'desc')->values();

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
                })
                ->sortBy(fn ($row) => in_array($this->statSort, ['title', 'max_score'], true)
                    ? $row['assignment']->{$this->statSort}
                    : ($row[$this->statSort] ?? 0), SORT_REGULAR, $this->statDir === 'desc')->values();
        }

        return view('livewire.admin.reports', compact('subjects', 'assignmentStats'));
    }
}
