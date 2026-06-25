<?php

namespace App\Livewire\Admin;

use App\Models\Subject;
use Livewire\Component;

class Subjects extends Component
{
    public string $code = '';
    public string $name = '';
    public ?int $editingId = null;

    public string $search = '';     // กรองรหัส/ชื่อวิชา
    public string $sortBy = 'code';
    public string $sortDir = 'asc';

    // กดหัวตาราง: คอลัมน์เดิม=สลับทิศ, ใหม่=asc — whitelist กัน inject ผ่าน orderBy
    public function sort(string $column): void
    {
        if (! in_array($column, ['code', 'name', 'assignments_count', 'students_count'], true)) {
            return;
        }
        $this->sortDir = $this->sortBy === $column && $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => ['required', 'string', 'max:50',
                'unique:subjects,code' . ($this->editingId ? ',' . $this->editingId : '')],
            'name' => ['required', 'string', 'max:150'],
        ], attributes: ['code' => 'รหัสวิชา', 'name' => 'ชื่อวิชา']);

        Subject::updateOrCreate(['id' => $this->editingId], $data);
        $this->cancel();
        session()->flash('ok', 'บันทึกรายวิชาแล้ว');
    }

    public function edit(int $id): void
    {
        $s = Subject::findOrFail($id);
        $this->editingId = $s->id;
        $this->code = $s->code;
        $this->name = $s->name;
    }

    public function cancel(): void
    {
        $this->reset('code', 'name', 'editingId');
        $this->resetErrorBag();
    }

    public function delete(int $id): void
    {
        Subject::findOrFail($id)->delete();
        session()->flash('ok', 'ลบรายวิชาแล้ว');
    }

    public function render()
    {
        return view('livewire.admin.subjects', [
            'subjects' => Subject::withCount(['assignments', 'students'])
                ->when($this->search, fn ($q) => $q
                    ->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%"))
                ->orderBy($this->sortBy, $this->sortDir)->get(),
        ]);
    }
}
