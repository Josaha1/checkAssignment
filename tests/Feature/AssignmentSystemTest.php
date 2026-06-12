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
use App\Contracts\DriveStorage;
use App\Services\FakeDriveStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function fakeDrive(): FakeDriveStorage
{
    $fake = new FakeDriveStorage();
    app()->instance(DriveStorage::class, $fake);
    return $fake;
}

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

it('นักศึกษาแนบไฟล์ขึ้น Drive และส่งซ้ำไม่ได้ถ้าให้คะแนนแล้ว', function () {
    $ctx = makeStudentWithSubject();
    $fake = fakeDrive();

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Submit::class, ['assignment' => $ctx['assignment']])
        ->set('uploads', [
            \Illuminate\Http\UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
            \Illuminate\Http\UploadedFile::fake()->create('b.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ])
        ->call('save')
        ->assertHasNoErrors();

    $sub = Submission::with('files')->first();
    expect($sub->files)->toHaveCount(2);
    // โครงสร้างโฟลเดอร์ วิชา/ห้อง/งาน/รหัสนศ.
    expect($fake->paths[0])->toContain('T-001')->toContain('ปวส.1/1')->toContain('งานทดสอบ')->toContain('670099999');

    // ให้คะแนนแล้ว → แนบเพิ่มไม่ได้
    $sub->update(['score' => 8, 'graded_at' => now()]);
    Livewire::actingAs($ctx['student'], 'student')
        ->test(Submit::class, ['assignment' => $ctx['assignment']])
        ->set('uploads', [\Illuminate\Http\UploadedFile::fake()->create('c.pdf', 50, 'application/pdf')])
        ->call('save')
        ->assertHasErrors('uploads');

    expect(Submission::first()->files()->count())->toBe(2);
});

it('แนบไฟล์ชนิดไม่อนุญาตถูกปฏิเสธ', function () {
    $ctx = makeStudentWithSubject();
    fakeDrive();

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Submit::class, ['assignment' => $ctx['assignment']])
        ->set('uploads', [\Illuminate\Http\UploadedFile::fake()->create('evil.exe', 50, 'application/x-msdownload')])
        ->call('save')
        ->assertHasErrors('uploads.*');
});

it('ส่งงานไม่ได้ถ้ายังไม่เชื่อม Drive', function () {
    $ctx = makeStudentWithSubject();
    // ไม่ผูก FakeDrive → ใช้ GoogleDriveService จริงที่ยังไม่เชื่อม (isConnected=false)

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Submit::class, ['assignment' => $ctx['assignment']])
        ->set('uploads', [\Illuminate\Http\UploadedFile::fake()->create('a.pdf', 50, 'application/pdf')])
        ->call('save')
        ->assertHasErrors('uploads');
});

it('นักศึกษามองไม่เห็นคะแนนตัวเองในหน้า dashboard', function () {
    $ctx = makeStudentWithSubject();
    Submission::create([
        'assignment_id' => $ctx['assignment']->id,
        'student_id' => $ctx['student']->id,
        'link' => 'https://drive.google.com/x',
        // ใช้ค่าที่เป็นไปไม่ได้ในพิกัด SVG icon (>24) กัน false-positive
        'score' => 63.41, 'submitted_at' => now(), 'graded_at' => now(),
    ]);

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Dashboard::class)
        ->assertDontSee('63.41')
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
