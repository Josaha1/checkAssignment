<?php

use App\Livewire\Admin\Grading;
use App\Livewire\Student\Dashboard;
use App\Livewire\Student\Login as StudentLogin;
use App\Livewire\Student\Submit;
use App\Models\Assignment;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeStudentWithSubject(): array
{
    $room = Room::create(['name' => 'ปวส.1/1']);
    $student = Student::create([
        'student_code' => '670099999',
        'full_name' => 'ทดสอบ ระบบ',
        'birthdate' => '2005-05-20',
        'room_id' => $room->id,
    ]);
    $subject = Subject::create(['code' => 'T-001', 'name' => 'วิชาทดสอบ']);
    $student->subjects()->attach($subject->id);
    $assignment = Assignment::create([
        'subject_id' => $subject->id, 'title' => 'งานทดสอบ', 'max_score' => 10,
    ]);

    return compact('room', 'student', 'subject', 'assignment');
}

it('นักศึกษา login ด้วยรหัส + วันเกิดถูกต้องได้', function () {
    $ctx = makeStudentWithSubject();

    Livewire::test(StudentLogin::class)
        ->set('student_code', '670099999')
        ->set('birthdate', '2005-05-20')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('student.dashboard'));

    expect(auth('student')->id())->toBe($ctx['student']->id);
});

it('นักศึกษา login วันเกิดผิด ถูกปฏิเสธ', function () {
    makeStudentWithSubject();

    Livewire::test(StudentLogin::class)
        ->set('student_code', '670099999')
        ->set('birthdate', '2000-01-01')
        ->call('login')
        ->assertHasErrors('student_code');

    expect(auth('student')->check())->toBeFalse();
});

it('dashboard แสดงงานที่ยังไม่ส่ง', function () {
    $ctx = makeStudentWithSubject();

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Dashboard::class)
        ->assertSee('งานทดสอบ')
        ->assertViewHas('pendingTotal', 1);
});

it('นักศึกษาส่งงานด้วยลิงก์ และส่งซ้ำไม่ได้ถ้าให้คะแนนแล้ว', function () {
    $ctx = makeStudentWithSubject();

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Submit::class, ['assignment' => $ctx['assignment']])
        ->set('link', 'https://drive.google.com/abc')
        ->call('save')
        ->assertHasNoErrors();

    $sub = Submission::first();
    expect($sub->link)->toBe('https://drive.google.com/abc');

    // ให้คะแนนแล้ว → แก้ลิงก์ไม่ได้
    $sub->update(['score' => 8, 'graded_at' => now()]);
    Livewire::actingAs($ctx['student'], 'student')
        ->test(Submit::class, ['assignment' => $ctx['assignment']])
        ->set('link', 'https://drive.google.com/changed')
        ->call('save')
        ->assertHasErrors('link');

    expect(Submission::first()->link)->toBe('https://drive.google.com/abc');
});

it('ส่งลิงก์ที่ไม่ใช่ URL ถูกปฏิเสธ', function () {
    $ctx = makeStudentWithSubject();

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Submit::class, ['assignment' => $ctx['assignment']])
        ->set('link', 'ไม่ใช่ลิงก์')
        ->call('save')
        ->assertHasErrors('link');
});

it('นักศึกษามองไม่เห็นคะแนนตัวเองในหน้า dashboard', function () {
    $ctx = makeStudentWithSubject();
    Submission::create([
        'assignment_id' => $ctx['assignment']->id,
        'student_id' => $ctx['student']->id,
        'link' => 'https://drive.google.com/x',
        'score' => 9.5, 'submitted_at' => now(), 'graded_at' => now(),
    ]);

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Dashboard::class)
        ->assertDontSee('9.5')
        ->assertSee('ส่งแล้ว');
});

it('แอดมินให้คะแนนผ่านหน้า grading แล้วบันทึก', function () {
    $admin = User::factory()->create();
    $ctx = makeStudentWithSubject();
    $sub = Submission::create([
        'assignment_id' => $ctx['assignment']->id,
        'student_id' => $ctx['student']->id,
        'link' => 'https://drive.google.com/x', 'submitted_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(Grading::class)
        ->set('subjectId', $ctx['subject']->id)
        ->set("scores.{$sub->id}", '7.5')
        ->call('saveScores')
        ->assertHasNoErrors();

    expect($sub->fresh()->score)->toEqual('7.50');
    expect($sub->fresh()->graded_at)->not->toBeNull();
});

it('export csv มีรหัสนักศึกษาและคะแนน', function () {
    $admin = User::factory()->create();
    $ctx = makeStudentWithSubject();
    Submission::create([
        'assignment_id' => $ctx['assignment']->id,
        'student_id' => $ctx['student']->id,
        'link' => 'https://drive.google.com/x',
        'score' => 7, 'submitted_at' => now(), 'graded_at' => now(),
    ]);

    $res = $this->actingAs($admin)->get(route('admin.export', $ctx['subject']));
    $res->assertOk();

    $csv = $res->streamedContent();
    expect($csv)->toContain('670099999');
    expect($csv)->toContain('รหัสนักศึกษา');
    expect($csv)->toContain('7'); // คะแนน
});

it('guest เข้า /admin ถูก redirect ไป admin.login', function () {
    $this->get('/admin')->assertRedirect(route('admin.login'));
});
