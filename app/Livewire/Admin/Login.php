<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';

    public function mount(): void
    {
        if (Auth::guard('web')->check()) {
            $this->redirectRoute('admin.dashboard', navigate: true);
        }
    }

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::guard('web')->attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->addError('email', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');
            return;
        }

        session()->regenerate();
        $this->redirectRoute('admin.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.login');
    }
}
