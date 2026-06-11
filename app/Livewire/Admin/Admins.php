<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Admins extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public ?int $editingId = null;

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->editingId)],
            // แก้ไข: เว้นว่าง = ไม่เปลี่ยนรหัส / เพิ่มใหม่: บังคับ
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:6'],
        ], attributes: ['name' => 'ชื่อ', 'email' => 'อีเมล', 'password' => 'รหัสผ่าน']);

        $payload = ['name' => $data['name'], 'email' => $data['email']];
        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        User::updateOrCreate(['id' => $this->editingId], $payload);
        $this->cancel();
        session()->flash('ok', 'บันทึกบัญชีผู้ดูแลแล้ว');
    }

    public function edit(int $id): void
    {
        $u = User::findOrFail($id);
        $this->editingId = $u->id;
        $this->name = $u->name;
        $this->email = $u->email;
        $this->password = '';
    }

    public function cancel(): void
    {
        $this->reset('name', 'email', 'password', 'editingId');
        $this->resetErrorBag();
    }

    public function delete(int $id): void
    {
        // กันลบตัวเอง
        if ($id === Auth::id()) {
            session()->flash('ok', 'ลบบัญชีตัวเองไม่ได้');
            return;
        }
        User::findOrFail($id)->delete();
        session()->flash('ok', 'ลบบัญชีผู้ดูแลแล้ว');
    }

    public function render()
    {
        return view('livewire.admin.admins', [
            'admins' => User::orderBy('name')->get(),
            'currentId' => Auth::id(),
        ]);
    }
}
