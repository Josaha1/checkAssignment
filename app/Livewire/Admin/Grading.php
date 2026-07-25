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
    public int $perPage = 20;          // จำนวน record/หน้า (whitelist 10/20/50/100)
    public array $scores = []; // submissionId => score
    public string $sortBy = 'student_code';
    public string $sortDir = 'asc';

    // เปลี่ยนตัวกรอง/ค้นหา/จำนวนต่อหน้า → กลับหน้า 1 กัน currentPage เกินช่วง (เว้น scores.* ที่พิมพ์คะแนน)
    public function updated(string $name): void
    {
        if (in_array($name, ['subjectId', 'group', 'assignmentId', 'statusFilter', 'search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function updatedSubjectId(): void
    {
        $this->scores = [];
        $this->assignmentId = null; // เปลี่ยนวิชา → ล้างงาน/ตัวกรองที่เลือกไว้
        $this->statusFilter = '';
    }

    // สลับงาน → ทิ้งคะแนนที่พิมพ์ค้างของงานก่อน กัน persist ข้ามงานตอนกดบันทึก (แต่ละงานตรวจแยกกัน)
    public function updatedAssignmentId(): void
    {
        $this->scores = [];
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

    // $only = submissionId → บันทึกเฉพาะแถวนั้น (ปุ่มราย row); ไม่ส่ง = บันทึกทุกแถว (ปุ่มรวมเดิม)
    public function saveScores(?int $only = null): void
    {
        $actor = auth('web')->user()?->name;
        foreach ($this->scores as $submissionId => $value) {
            if ($only !== null && (int) $submissionId !== $only) {
                continue; // ระบุแถว → ข้ามแถวอื่นที่พิมพ์ค้าง ไม่ให้บันทึกพลอย
            }
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

            // clamp กัน inject perPage ใหญ่ผิดปกติ (ดึงทั้งตาราง)
            $per = in_array($this->perPage, [10, 20, 50, 100], true) ? $this->perPage : 20;

            $students = Student::query()
                ->whereHas('subjects', fn ($q) => $q->whereKey($this->subjectId))
                ->when($this->group, fn ($q) => $q->where('study_group', $this->group))
                // ห่อ closure เพราะมี whereHas นำหน้า — กัน orWhere หลุด scope
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                    ->where('student_code', 'like', "%{$this->search}%")
                    ->orWhere('full_name', 'like', "%{$this->search}%")))
                // กรองสถานะที่ DB (ไม่ใช่ใน memory หลัง paginate) → แต่ละหน้าจำนวนเท่ากัน
                ->when($this->assignmentId && $this->statusFilter, fn ($q) => match ($this->statusFilter) {
                    'missing' => $q->whereDoesntHave('submissions', fn ($s) => $s->where('assignment_id', $this->assignmentId)->whereNotNull('submitted_at')),
                    'pending' => $q->whereHas('submissions', fn ($s) => $s->where('assignment_id', $this->assignmentId)->whereNotNull('submitted_at')->whereNull('score')),
                    'graded' => $q->whereHas('submissions', fn ($s) => $s->where('assignment_id', $this->assignmentId)->whereNotNull('submitted_at')->whereNotNull('score')),
                    default => $q,
                })
                ->orderBy($this->sortBy, $this->sortDir)->paginate($per);

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
                })->values(); // กรองสถานะย้ายไปที่ DB แล้ว (paginate นับถูก) — ไม่ต้องกรองซ้ำใน memory
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
