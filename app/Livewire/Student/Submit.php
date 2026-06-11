<?php

namespace App\Livewire\Student;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Submit extends Component
{
    public Assignment $assignment;
    public ?Submission $submission = null;
    public string $link = '';

    public function mount(Assignment $assignment): void
    {
        $student = Auth::guard('student')->user();

        // ต้องลงทะเบียนวิชานี้เท่านั้น
        abort_unless($student->subjects()->whereKey($assignment->subject_id)->exists(), 403);

        $this->assignment = $assignment->load('subject');
        $this->submission = Submission::where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)->first();

        $this->link = $this->submission?->link ?? '';
    }

    public function save(): void
    {
        $student = Auth::guard('student')->user();

        // ส่งได้เฉพาะลิงก์ และห้ามแก้ถ้าให้คะแนนแล้ว
        if ($this->submission && $this->submission->score !== null) {
            throw ValidationException::withMessages([
                'link' => 'งานนี้ถูกตรวจให้คะแนนแล้ว ไม่สามารถแก้ไขได้',
            ]);
        }

        $this->validate([
            'link' => ['required', 'url', 'max:1000'],
        ], attributes: ['link' => 'ลิงก์งาน']);

        Submission::updateOrCreate(
            ['assignment_id' => $this->assignment->id, 'student_id' => $student->id],
            ['link' => trim($this->link), 'submitted_at' => now()],
        );

        session()->flash('ok', 'ส่งงานเรียบร้อยแล้ว');
        $this->redirectRoute('student.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.student.submit');
    }
}
