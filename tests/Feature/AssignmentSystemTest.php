<?php

use App\Livewire\Admin\Assignments;
use App\Livewire\Admin\Grading;
use App\Livewire\Admin\Students;
use App\Livewire\Student\Dashboard;
use App\Livewire\Student\Login as StudentLogin;
use App\Livewire\Student\Submit;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\User;
use App\Contracts\DriveStorage;
use App\Services\FakeDriveStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function fakeDrive(): FakeDriveStorage
{
    $fake = new FakeDriveStorage();
    app()->instance(DriveStorage::class, $fake);
    return $fake;
}

function makeStudentWithSubject(): array
{
    $student = Student::create([
        'student_code' => '670099999',
        'full_name' => 'นาย ทดสอบ ระบบ',
        'email' => 'test.sys@spulive.net',
        'password' => '670099999', // cast 'hashed' จะ hash ให้
        'faculty' => 'นิติศาสตร์',
        'study_group' => '043',
    ]);
    $subject = Subject::create(['code' => 'T-001', 'name' => 'วิชาทดสอบ']);
    $student->subjects()->attach($subject->id);
    $assignment = Assignment::create([
        'subject_id' => $subject->id, 'title' => 'งานทดสอบ', 'max_score' => 10,
    ]);

    return compact('student', 'subject', 'assignment');
}

// สร้างไฟล์ xlsx จำลองใบลงทะเบียน (header เหมือน SPU) คืน path
function makeRosterXlsx(): string
{
    $ss = new Spreadsheet();
    $ss->getActiveSheet()->fromArray([
        ['ใบแจ้งรายชื่อการลงทะเบียนเรียน ภาคการศึกษา 3/2568'],
        ['ชื่อวิชา : UID10667 - เทคโนโลยีสารสนเทศเพื่ออาชีพและการทำงาน 1(0-2)'],
        ['ลำดับ', 'รหัสนักศึกษา', 'ชื่อ-นามสกุล', 'คณะ', 'สาขา', 'รอบ', 'กลุ่มเรียน', 'email-สถาบัน-1'],
        ['1', '67078724', 'นาย พิพัฒน์ สงค์มี', 'นิติศาสตร์', '-', 'หลักสูตรตรีเช้า', '043', 'piphat.son@spulive.net'],
        ['2', '67089254', 'น.ส. สุภาวณี บุญภิบาล', 'นิเทศศาสตร์', 'สื่อสารการแสดง', 'หลักสูตรตรีเช้า', '044', 'suparwanee.boo@spulive.net'],
    ], null, 'A1');

    $path = tempnam(sys_get_temp_dir(), 'roster') . '.xlsx';
    (new Xlsx($ss))->save($path);
    return $path;
}

it('นักศึกษา login ด้วยอีเมล + รหัสผ่านถูกต้องได้', function () {
    $ctx = makeStudentWithSubject();

    Livewire::test(StudentLogin::class)
        ->set('email', 'test.sys@spulive.net')
        ->set('password', '670099999')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('student.dashboard'));

    expect(auth('student')->id())->toBe($ctx['student']->id);
});

it('นักศึกษา login รหัสผ่านผิด ถูกปฏิเสธ', function () {
    makeStudentWithSubject();

    Livewire::test(StudentLogin::class)
        ->set('email', 'test.sys@spulive.net')
        ->set('password', 'wrong-pass')
        ->call('login')
        ->assertHasErrors('email');

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
            UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
            UploadedFile::fake()->create('b.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ])
        ->call('save')
        ->assertHasNoErrors();

    $sub = Submission::with('files')->first();
    expect($sub->files)->toHaveCount(2);
    // โครงสร้างโฟลเดอร์ วิชา/กลุ่มเรียน/งาน/รหัสนศ.
    expect($fake->paths[0])->toContain('T-001')->toContain('043')->toContain('งานทดสอบ')->toContain('670099999');

    // ให้คะแนนแล้ว → แนบเพิ่มไม่ได้
    $sub->update(['score' => 8, 'graded_at' => now()]);
    Livewire::actingAs($ctx['student'], 'student')
        ->test(Submit::class, ['assignment' => $ctx['assignment']])
        ->set('uploads', [UploadedFile::fake()->create('c.pdf', 50, 'application/pdf')])
        ->call('save')
        ->assertHasErrors('uploads');

    expect(Submission::first()->files()->count())->toBe(2);
});

it('แนบไฟล์ชนิดไม่อนุญาตถูกปฏิเสธ', function () {
    $ctx = makeStudentWithSubject();
    fakeDrive();

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Submit::class, ['assignment' => $ctx['assignment']])
        ->set('uploads', [UploadedFile::fake()->create('evil.exe', 50, 'application/x-msdownload')])
        ->call('save')
        ->assertHasErrors('uploads.*');
});

it('ส่งงานไม่ได้ถ้ายังไม่เชื่อม Drive', function () {
    $ctx = makeStudentWithSubject();

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Submit::class, ['assignment' => $ctx['assignment']])
        ->set('uploads', [UploadedFile::fake()->create('a.pdf', 50, 'application/pdf')])
        ->call('save')
        ->assertHasErrors('uploads');
});

it('นักศึกษามองไม่เห็นคะแนนตัวเองในหน้า dashboard', function () {
    $ctx = makeStudentWithSubject();
    Submission::create([
        'assignment_id' => $ctx['assignment']->id,
        'student_id' => $ctx['student']->id,
        'score' => 63.41, 'submitted_at' => now(), 'graded_at' => now(),
    ]);

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Dashboard::class)
        ->assertDontSee('63.41')
        ->assertSee('ตรวจแล้ว');
});

it('งานที่ตรวจแล้วขึ้นสถานะ "ตรวจแล้ว" โดยไม่โชว์คะแนน', function () {
    $ctx = makeStudentWithSubject();
    Submission::create([
        'assignment_id' => $ctx['assignment']->id,
        'student_id' => $ctx['student']->id,
        'score' => 8, 'submitted_at' => now(), 'graded_at' => now(),
    ]);

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Dashboard::class)
        ->assertSee('ตรวจแล้ว')
        ->assertDontSee('8.00');
});

it('งานที่ส่งแล้วแต่ยังไม่ตรวจขึ้นสถานะ "ส่งแล้ว"', function () {
    $ctx = makeStudentWithSubject();
    Submission::create([
        'assignment_id' => $ctx['assignment']->id,
        'student_id' => $ctx['student']->id,
        'submitted_at' => now(), // score null = ยังไม่ตรวจ
    ]);

    Livewire::actingAs($ctx['student'], 'student')
        ->test(Dashboard::class)
        ->assertSee('ส่งแล้ว')
        ->assertDontSee('ตรวจแล้ว');
});

it('แอดมินให้คะแนนผ่านหน้า grading แล้วบันทึก', function () {
    $admin = User::factory()->create();
    $ctx = makeStudentWithSubject();
    $sub = Submission::create([
        'assignment_id' => $ctx['assignment']->id,
        'student_id' => $ctx['student']->id,
        'submitted_at' => now(),
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
        'score' => 7, 'submitted_at' => now(), 'graded_at' => now(),
    ]);

    $res = $this->actingAs($admin)->get(route('admin.export', $ctx['subject']));
    $res->assertOk();

    $csv = $res->streamedContent();
    expect($csv)->toContain('670099999');
    expect($csv)->toContain('รหัสนักศึกษา');
    expect($csv)->toContain('7');
});

it('แอดมินนำเข้าใบลงทะเบียน xlsx → สร้างวิชา + นักศึกษา + ลงทะเบียน', function () {
    $admin = User::factory()->create();
    $path = makeRosterXlsx();
    $file = UploadedFile::fake()->createWithContent('roster.xlsx', file_get_contents($path));

    Livewire::actingAs($admin)
        ->test(Students::class)
        ->set('file', $file)
        ->call('import')
        ->assertHasNoErrors();

    expect(Student::count())->toBe(2);
    $subject = Subject::where('code', 'UID10667')->first();
    expect($subject)->not->toBeNull();

    $s = Student::where('student_code', '67078724')->first();
    expect($s->email)->toBe('piphat.son@spulive.net');
    expect($s->faculty)->toBe('นิติศาสตร์');
    expect($s->major)->toBeNull(); // "-" → null
    expect($s->study_group)->toBe('043');
    expect(Hash::check('67078724', $s->password))->toBeTrue(); // รหัสผ่านตั้งต้น = รหัสนักศึกษา
    expect($s->subjects()->whereKey($subject->id)->exists())->toBeTrue();

    @unlink($path);
});

it('นำเข้าซ้ำ (นักศึกษามีอยู่แล้ว) ไม่ติด NOT NULL password + รีเซ็ตรหัสเป็นรหัสนักศึกษา', function () {
    $admin = User::factory()->create();

    // นศ.มีอยู่ก่อนแล้ว (ชื่อ/อีเมล/รหัสผ่านเดิม) → import รอบนี้ต้องวิ่ง upsert path
    Student::create([
        'student_code' => '67078724',
        'full_name' => 'ชื่อเก่า',
        'email' => 'old@spulive.net',
        'password' => 'รหัสที่นศเปลี่ยนเอง',
    ]);

    $path = makeRosterXlsx();
    $file = UploadedFile::fake()->createWithContent('roster.xlsx', file_get_contents($path));

    Livewire::actingAs($admin)
        ->test(Students::class)
        ->set('file', $file)
        ->call('import')
        ->assertHasNoErrors(); // ก่อนแก้: upsert ไม่มี password → SQLSTATE 23502 → addError('file')

    expect(Student::count())->toBe(2); // 67078724 ถูก update, 67089254 ถูก insert

    $existing = Student::where('student_code', '67078724')->first();
    expect($existing->full_name)->toBe('นาย พิพัฒน์ สงค์มี'); // update ทับชื่อเก่า
    expect($existing->email)->toBe('piphat.son@spulive.net');
    expect(Hash::check('67078724', $existing->password))->toBeTrue();   // รีเซ็ตเป็นรหัสนักศึกษา (Option B)
    expect(Hash::check('รหัสที่นศเปลี่ยนเอง', $existing->password))->toBeFalse(); // รหัสเดิมใช้ไม่ได้แล้ว

    $new = Student::where('student_code', '67089254')->first();
    expect(Hash::check('67089254', $new->password))->toBeTrue();

    $subject = Subject::where('code', 'UID10667')->first();
    expect($existing->subjects()->whereKey($subject->id)->exists())->toBeTrue();

    @unlink($path);
});

it('เตรียมโฟลเดอร์ Drive สร้างตามกลุ่มเรียน (study_group) ไม่ขึ้น 500', function () {
    $admin = User::factory()->create();
    $fake = fakeDrive(); // isConnected()=true → วิ่งเข้า path สร้างโฟลเดอร์

    $subject = Subject::create(['code' => 'UID10667', 'name' => 'วิชาทดสอบ']);
    $assignment = Assignment::create(['subject_id' => $subject->id, 'title' => 'งานที่ 1', 'max_score' => 10]);

    // 043 มี 2 คน → unique เหลือ 1 โฟลเดอร์/กลุ่ม
    foreach ([['s1', '043'], ['s2', '043'], ['s3', '044']] as [$code, $grp]) {
        $st = Student::create([
            'student_code' => $code, 'full_name' => 'น ' . $code,
            'email' => $code . '@spulive.net', 'password' => $code, 'study_group' => $grp,
        ]);
        $st->subjects()->attach($subject->id);
    }

    Livewire::actingAs($admin)
        ->test(Assignments::class, ['subject' => $subject])
        ->call('prepareFolders', $assignment->id) // ก่อนแก้: with('room') → RelationNotFoundException → 500
        ->assertHasNoErrors();

    expect($fake->paths)->toHaveCount(2); // 1 โฟลเดอร์ต่อกลุ่มเรียน (043, 044)
    expect($fake->paths)->toContain('UID10667 วิชาทดสอบ/043/งานที่ 1');
    expect($fake->paths)->toContain('UID10667 วิชาทดสอบ/044/งานที่ 1');
});

it('guest เข้า /admin ถูก redirect ไป admin.login', function () {
    $this->get('/admin')->assertRedirect(route('admin.login'));
});
