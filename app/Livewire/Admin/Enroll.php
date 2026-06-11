<?php

namespace App\Livewire\Admin;

use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use Livewire\Component;

class Enroll extends Component
{
    public Subject $subject;
    public ?int $roomFilter = null;
    public array $enrolled = []; // student_id => true

    public function mount(Subject $subject): void
    {
        $this->subject = $subject;
        $this->enrolled = $subject->students()->pluck('students.id')
            ->mapWithKeys(fn ($id) => [$id => true])->all();
    }

    public function toggle(int $studentId): void
    {
        if ($this->subject->students()->whereKey($studentId)->exists()) {
            $this->subject->students()->detach($studentId);
            unset($this->enrolled[$studentId]);
        } else {
            $this->subject->students()->attach($studentId);
            $this->enrolled[$studentId] = true;
        }
    }

    public function enrollRoom(): void
    {
        if (! $this->roomFilter) {
            return;
        }
        $ids = Student::where('room_id', $this->roomFilter)->pluck('id')->all();
        $this->subject->students()->syncWithoutDetaching($ids);
        foreach ($ids as $id) {
            $this->enrolled[$id] = true;
        }
        session()->flash('ok', 'ลงทะเบียนทั้งห้องเรียบร้อย');
    }

    public function render()
    {
        $students = Student::with('room')
            ->when($this->roomFilter, fn ($q) => $q->where('room_id', $this->roomFilter))
            ->orderBy('student_code')->get();

        return view('livewire.admin.enroll', [
            'students' => $students,
            'rooms' => Room::orderBy('name')->get(),
            'enrolledCount' => count($this->enrolled),
        ]);
    }
}
