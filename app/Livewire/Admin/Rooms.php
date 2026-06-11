<?php

namespace App\Livewire\Admin;

use App\Models\Room;
use Livewire\Component;

class Rooms extends Component
{
    public string $name = '';
    public ?int $editingId = null;

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100',
                'unique:rooms,name' . ($this->editingId ? ',' . $this->editingId : '')],
        ], attributes: ['name' => 'ชื่อห้อง']);

        Room::updateOrCreate(['id' => $this->editingId], $data);
        $this->reset('name', 'editingId');
        session()->flash('ok', 'บันทึกห้องเรียนแล้ว');
    }

    public function edit(int $id): void
    {
        $room = Room::findOrFail($id);
        $this->editingId = $room->id;
        $this->name = $room->name;
    }

    public function cancel(): void
    {
        $this->reset('name', 'editingId');
        $this->resetErrorBag();
    }

    public function delete(int $id): void
    {
        Room::findOrFail($id)->delete();
        session()->flash('ok', 'ลบห้องเรียนแล้ว');
    }

    public function render()
    {
        return view('livewire.admin.rooms', [
            'rooms' => Room::withCount('students')->orderBy('name')->get(),
        ]);
    }
}
