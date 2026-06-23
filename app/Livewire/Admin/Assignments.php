<?php

namespace App\Livewire\Admin;

use App\Contracts\DriveStorage;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\Subject;
use Livewire\Component;

class Assignments extends Component
{
    public Subject $subject;

    public string $title = '';
    public string $description = '';
    public string $max_score = '100';
    public ?string $due_date = null;
    public ?int $editingId = null;

    public function mount(Subject $subject): void
    {
        $this->subject = $subject;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'max_score' => ['required', 'numeric', 'min:0', 'max:9999'],
            'due_date' => ['nullable', 'date'],
        ], attributes: ['title' => 'ชื่องาน', 'max_score' => 'คะแนนเต็ม']);

        $data['subject_id'] = $this->subject->id;
        Assignment::updateOrCreate(['id' => $this->editingId], $data);
        $this->cancel();
        session()->flash('ok', 'บันทึกชิ้นงานแล้ว');
    }

    public function edit(int $id): void
    {
        $a = Assignment::where('subject_id', $this->subject->id)->findOrFail($id);
        $this->editingId = $a->id;
        $this->title = $a->title;
        $this->description = $a->description ?? '';
        $this->max_score = (string) $a->max_score;
        $this->due_date = $a->due_date?->format('Y-m-d');
    }

    public function cancel(): void
    {
        $this->reset('title', 'description', 'editingId', 'due_date');
        $this->max_score = '100';
        $this->resetErrorBag();
    }

    public function delete(int $id): void
    {
        Assignment::where('subject_id', $this->subject->id)->findOrFail($id)->delete();
        session()->flash('ok', 'ลบชิ้นงานแล้ว');
    }

    // สร้างโฟลเดอร์ วิชา/ห้อง/งาน ล่วงหน้าบน Drive (ทุกห้องที่มีนักศึกษาลงทะเบียนวิชานี้)
    public function prepareFolders(int $id): void
    {
        $assignment = Assignment::where('subject_id', $this->subject->id)->findOrFail($id);
        $drive = app(DriveStorage::class);
        if (! $drive->isConnected()) {
            session()->flash('ok', 'ยังไม่ได้เชื่อมต่อ Google Drive');
            return;
        }

        // "ห้อง" = กลุ่มเรียน เก็บใน field study_group (Student ไม่มี relationship room → with('room') เดิมทำ 500)
        $rooms = Student::whereHas('subjects', fn ($q) => $q->whereKey($this->subject->id))
            ->pluck('study_group')->filter()->unique();

        $count = 0;
        foreach ($rooms as $roomName) {
            $drive->ensureFolderPath([
                trim($this->subject->code . ' ' . $this->subject->name),
                trim($roomName),
                trim($assignment->title),
            ]);
            $count++;
        }
        session()->flash('ok', "สร้างโฟลเดอร์งานนี้บน Drive แล้ว ({$count} ห้อง)");
    }

    public function render()
    {
        return view('livewire.admin.assignments', [
            'assignments' => $this->subject->assignments()->withCount('submissions')->latest()->get(),
        ]);
    }
}
