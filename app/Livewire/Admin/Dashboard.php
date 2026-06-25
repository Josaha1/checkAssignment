<?php

namespace App\Livewire\Admin;

use App\Models\Assignment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public string $sortBy = 'submitted_at';
    public string $sortDir = 'desc';

    // เรียง 8 รายการล่าสุดในหน่วยความจำ (คอลัมน์ student/subject/สถานะ เป็น relation/derived)
    public function sort(string $column): void
    {
        if (! in_array($column, ['student_code', 'subject', 'submitted_at', 'status'], true)) {
            return;
        }
        $this->sortDir = $this->sortBy === $column && $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
    }

    public function render()
    {
        $stats = [
            'students' => Student::count(),
            'subjects' => Subject::count(),
            'groups' => Student::whereNotNull('study_group')->distinct('study_group')->count('study_group'),
            'assignments' => Assignment::count(),
            'submissions' => Submission::whereNotNull('submitted_at')->count(),
            'ungraded' => Submission::whereNotNull('submitted_at')->whereNull('score')->count(),
        ];

        // กราฟแท่ง: การส่งงาน 7 วันล่าสุด
        $days = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));
        $perDay = Submission::whereNotNull('submitted_at')
            ->where('submitted_at', '>=', Carbon::today()->subDays(6))
            ->get()
            ->groupBy(fn ($s) => $s->submitted_at->toDateString());

        $chartDays = [
            'labels' => $days->map(fn ($d) => $d->format('d/m'))->values(),
            'data' => $days->map(fn ($d) => ($perDay[$d->toDateString()] ?? collect())->count())->values(),
        ];

        $recent = Submission::with(['student', 'assignment.subject'])
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')->limit(8)->get()
            ->sortBy(fn ($s) => match ($this->sortBy) {
                'student_code' => $s->student->student_code,
                'subject' => $s->assignment?->subject?->code,
                'status' => $s->score !== null ? 1 : 0,
                default => $s->submitted_at,
            }, SORT_REGULAR, $this->sortDir === 'desc')->values();

        return view('livewire.admin.dashboard', compact('stats', 'chartDays', 'recent'));
    }
}
