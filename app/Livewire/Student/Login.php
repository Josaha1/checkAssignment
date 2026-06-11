<?php

namespace App\Livewire\Student;

use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $student_code = '';
    public string $birthdate = ''; // input type=date → Y-m-d

    public function mount(): void
    {
        if (Auth::guard('student')->check()) {
            $this->redirectRoute('student.dashboard', navigate: true);
        }
    }

    public function login(): void
    {
        $this->validate([
            'student_code' => ['required', 'string'],
            'birthdate' => ['required', 'date'],
        ], attributes: [
            'student_code' => 'รหัสนักศึกษา',
            'birthdate' => 'วันเกิด',
        ]);

        $student = Student::where('student_code', trim($this->student_code))->first();

        // รหัสผ่าน = วันเกิด เทียบตรง ๆ ตามสเปก (ไม่ hash)
        if (! $student || $student->birthdate->format('Y-m-d') !== $this->birthdate) {
            $this->addError('student_code', 'รหัสนักศึกษาหรือวันเกิดไม่ถูกต้อง');
            return;
        }

        Auth::guard('student')->login($student);
        session()->regenerate();
        $this->redirectRoute('student.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.student.login');
    }
}
