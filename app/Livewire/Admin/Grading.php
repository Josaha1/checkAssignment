<?php

namespace App\Livewire\Admin;

use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\SubmissionHistory;
use Livewire\Component;
use Livewire\WithPagination;

class Grading extends Component
{
    use WithPagination;

    public ?int $subjectId = null;
    public ?int $assignmentId = null;  // เลือกงาน → โหมดตารางต่อชิ้นงาน (ว่าง = matrix รวมทุกงาน)
    public ?string $group = null;
    public string $statusFilter = '';  // กรองสถานะ: '' ทั้งหมด / missing / pending / graded
    public string $search = '';        // ค้นหารหัส/ชื่อนักศึกษา
    public array $scores = []; // submissionId => score
    public string $sortBy = 'student_code';
    public string $sortDir = 'asc';

    // เปลี่ยนตัวกรอง/ค้นหา → กลับหน้า 1 กัน currentPage เกินช่วง (เว้น scores.* ที่พิมพ์คะแนน)
    public function updated(string $name): void
    {
        if (in_array($name, ['subjectId', 'group', 'assignmentId', 'statusFilter', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function updatedSubjectId(): void
    {
        $this->scores = [];
        $this->assignmentId = null; // เปลี่ยนวิชา → ล้างงาน/ตัวกรองที่เลือกไว้
        $this->statusFilter = '';
    }

    // เรียงได้เฉพาะคอลัมน์นักศึกษา (matrix คะแนนต่องานเรียงไม่ได้)
    public function sort(string $column): void
    {
        if (! in_array($column, ['student_code', 'full_name'], true)) {
            return;
        }
        $this->sortDir = $this->sortBy === $column && $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
        $this->resetPage(); // เรียงใหม่ → กลับหน้า 1
    }

    public function saveScores(): void
    {
        $actor = auth('web')->user()?->name;
        foreach ($this->scores as $submissionId => $value) {
            $submission = Submission::find($submissionId);
            if (! $submission) {
                continue;
            }
            // ว่าง = ล้างคะแนนกลับเป็นยังไม่ตรวจ (log เฉพาะที่เคยมีคะแนน — กัน history ซ้ำทุกครั้งที่กดบันทึก)
            if ($value === '' || $value === null) {
                if ($submission->score !== null) {
                    $submission->update(['score' => null, 'graded_at' => null]);
                    SubmissionHistory::create(['submission_id' => $submission->id, 'action' => 'score_cleared', 'actor' => $actor]);
                }
                continue;
            }
            $new = (float) $value;
            if ($submission->score === null || (float) $submission->score !== $new) { // เปลี่ยนจริงเท่านั้น
                $submission->update(['score' => $new, 'graded_at' => now()]);
                SubmissionHistory::create(['submission_id' => $submission->id, 'action' => 'graded', 'actor' => $actor, 'detail' => (string) $new]);
            }
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
        $rows = collect(); // โหมดต่อชิ้นงาน: [student, submission, status]
        $selectedAssignment = null;

        if ($this->subjectId) {
            $subject = Subject::with('assignments')->find($this->subjectId);
            $assignments = $subject?->assignments->sortBy('id')->values() ?? collect();

            $students = Student::query()
                ->whereHas('subjects', fn ($q) => $q->whereKey($this->subjectId))
                ->when($this->group, fn ($q) => $q->where('study_group', $this->group))
                // ห่อ closure เพราะมี whereHas นำหน้า — กัน orWhere หลุด scope
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                    ->where('student_code', 'like', "%{$this->search}%")
                    ->orWhere('full_name', 'like', "%{$this->search}%")))
                ->orderBy($this->sortBy, $this->sortDir)->paginate(20);

            if ($this->assignmentId) {
                // โหมดต่อชิ้นงาน: 1 แถว/นักศึกษา + สถานะ + กรองสถานะได้
                $selectedAssignment = $assignments->firstWhere('id', $this->assignmentId);

                $subs = Submission::with('files', 'histories')
                    ->whereNotNull('submitted_at')
                    ->where('assignment_id', $this->assignmentId)
                    ->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');

                $rows = $students->map(function ($st) use ($subs) {
                    $sub = $subs[$st->id] ?? null;
                    // ยังไม่ส่ง = ไม่มี submission, รอตรวจ = ส่งแล้วยังไม่มีคะแนน, ตรวจแล้ว = มีคะแนน
                    $status = $sub === null ? 'missing' : ($sub->score !== null ? 'graded' : 'pending');
                    if ($sub && ! array_key_exists($sub->id, $this->scores)) {
                        $this->scores[$sub->id] = $sub->score !== null ? rtrim(rtrim($sub->score, '0'), '.') : '';
                    }
                    return ['student' => $st, 'submission' => $sub, 'status' => $status];
                })->when($this->statusFilter, fn ($c) => $c->where('status', $this->statusFilter))->values();
            } else {
                // โหมด matrix เดิม (รวมทุกงาน)
                $subs = Submission::with('files')
                    ->whereNotNull('submitted_at') // ลบไฟล์ครบ → ไม่นับว่าส่ง (ไม่มีอะไรให้ตรวจ)
                    ->whereIn('assignment_id', $assignments->pluck('id'))
                    ->whereIn('student_id', $students->pluck('id'))->get();

                foreach ($subs as $sub) {
                    $matrix[$sub->student_id][$sub->assignment_id] = $sub;
                    if (! array_key_exists($sub->id, $this->scores)) {
                        $this->scores[$sub->id] = $sub->score !== null ? rtrim(rtrim($sub->score, '0'), '.') : '';
                    }
                }
            }
        }

        return view('livewire.admin.grading', compact(
            'subjects', 'groups', 'assignments', 'students', 'matrix', 'rows', 'selectedAssignment'
        ));
    }
}
