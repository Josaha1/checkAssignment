<?php

namespace App\Livewire\Admin;

use App\Models\Student;
use App\Models\Subject;
use Livewire\Component;

class Enroll extends Component
{
    public Subject $subject;
    public ?string $groupFilter = null;
    public array $enrolled = []; // student_id => true
    public string $sortBy = 'student_code';
    public string $sortDir = 'asc';

    // กดหัวตาราง: คอลัมน์เดิม=สลับทิศ, ใหม่=asc — whitelist กัน inject ผ่าน orderBy
    public function sort(string $column): void
    {
        if (! in_array($column, ['student_code', 'full_name', 'study_group'], true)) {
            return;
        }
        $this->sortDir = $this->sortBy === $column && $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
    }

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

    public function enrollGroup(): void
    {
        if (! $this->groupFilter) {
            return;
        }
        $ids = Student::where('study_group', $this->groupFilter)->pluck('id')->all();
        $this->subject->students()->syncWithoutDetaching($ids);
        foreach ($ids as $id) {
            $this->enrolled[$id] = true;
        }
        session()->flash('ok', 'ลงทะเบียนทั้งกลุ่มเรียบร้อย');
    }

    public function render()
    {
        $students = Student::query()
            ->when($this->groupFilter, fn ($q) => $q->where('study_group', $this->groupFilter))
            ->orderBy($this->sortBy, $this->sortDir)->get();

        return view('livewire.admin.enroll', [
            'students' => $students,
            'groups' => Student::whereNotNull('study_group')->distinct()->orderBy('study_group')->pluck('study_group'),
            'enrolledCount' => count($this->enrolled),
        ]);
    }
}
