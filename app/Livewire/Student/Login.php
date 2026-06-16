<?php

namespace App\Livewire\Student;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';

    public function mount(): void
    {
        if (Auth::guard('student')->check()) {
            $this->redirectRoute('student.dashboard', navigate: true);
        }
    }

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], attributes: [
            'email' => 'อีเมล',
            'password' => 'รหัสผ่าน',
        ]);

        // รหัสผ่าน hash แล้ว → ใช้ attempt เทียบ Hash::check ให้
        if (! Auth::guard('student')->attempt(['email' => trim($this->email), 'password' => $this->password])) {
            $this->addError('email', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');
            return;
        }

        session()->regenerate();
        $this->redirectRoute('student.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.student.login');
    }
}
