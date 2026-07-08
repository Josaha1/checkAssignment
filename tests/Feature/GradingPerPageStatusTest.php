<?php

use App\Livewire\Admin\Grading;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function ppStudent(string $code, Subject $subject): Student
{
    $st = Student::create([
        'student_code' => $code, 'full_name' => 'น ' . $code,
        'email' => $code . '@x.net', 'password' => 'x', 'study_group' => '043',
    ]);
    $st->subjects()->attach($subject->id);
    return $st;
}

// 12 ตรวจแล้ว + 5 รอตรวจ + 3 ยังไม่ส่ง = 20 คน
function ppCtx(): array
{
    $subject = Subject::create(['code' => 'PP-1', 'name' => 'วิชา']);
    $asg = Assignment::create(['subject_id' => $subject->id, 'title' => 'งาน X', 'max_score' => 10]);
    for ($i = 1; $i <= 12; $i++) {
        $st = ppStudent('G' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), $subject);
        Submission::create(['assignment_id' => $asg->id, 'student_id' => $st->id, 'submitted_at' => now(), 'score' => 8, 'graded_at' => now()]);
    }
    for ($i = 1; $i <= 5; $i++) {
        $st = ppStudent('P' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), $subject);
        Submission::create(['assignment_id' => $asg->id, 'student_id' => $st->id, 'submitted_at' => now()]);
    }
    for ($i = 1; $i <= 3; $i++) {
        ppStudent('M' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), $subject);
    }
    return compact('subject', 'asg');
}

it('กรองสถานะแล้วแบ่งหน้าเท่ากัน — paginator นับ "หลัง" กรอง (BUG fix: ไม่ใช่ก่อน)', function () {
    $admin = User::factory()->create();
    $c = ppCtx();

    $t = Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['asg']->id)
        ->set('perPage', 10)
        ->set('statusFilter', 'graded'); // 12 คนตรวจแล้ว

    // ก่อน fix: total = 20 (นับนักศึกษาทั้งหมด) → หน้าไม่เท่ากัน. หลัง fix: total = 12
    $t->assertViewHas('students', fn ($p) => $p instanceof LengthAwarePaginator && $p->total() === 12 && $p->count() === 10)
        ->assertViewHas('rows', fn ($r) => $r->count() === 10 && $r->every(fn ($x) => $x['status'] === 'graded'));

    $t->call('gotoPage', 2)
        ->assertViewHas('students', fn ($p) => $p->currentPage() === 2 && $p->count() === 2)
        ->assertViewHas('rows', fn ($r) => $r->count() === 2);
});

it('missing/pending ก็กรองที่ DB นับถูก', function () {
    $admin = User::factory()->create();
    $c = ppCtx();

    $t = Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['asg']->id)
        ->set('perPage', 100);

    $t->set('statusFilter', 'pending')->assertViewHas('students', fn ($p) => $p->total() === 5);
    $t->set('statusFilter', 'missing')->assertViewHas('students', fn ($p) => $p->total() === 3);
    $t->set('statusFilter', '')->assertViewHas('students', fn ($p) => $p->total() === 20);
});

it('เลือก per-page เปลี่ยนจำนวนต่อหน้าได้', function () {
    $admin = User::factory()->create();
    $c = ppCtx();

    $t = Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id); // matrix, 20 คน

    $t->set('perPage', 10)->assertViewHas('students', fn ($p) => $p->perPage() === 10 && $p->total() === 20 && $p->count() === 10);
    $t->set('perPage', 50)->assertViewHas('students', fn ($p) => $p->perPage() === 50 && $p->count() === 20);
});

it('เปลี่ยน per-page รีเซ็ตกลับหน้า 1', function () {
    $admin = User::factory()->create();
    $c = ppCtx();

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('perPage', 10)
        ->call('gotoPage', 2)
        ->assertViewHas('students', fn ($p) => $p->currentPage() === 2)
        ->set('perPage', 50)
        ->assertViewHas('students', fn ($p) => $p->currentPage() === 1);
});

it('per-page นอก whitelist ถูก clamp กลับ 20 (กัน inject ดึงทั้งตาราง)', function () {
    $admin = User::factory()->create();
    $c = ppCtx();

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('perPage', 99999)
        ->assertViewHas('students', fn ($p) => $p->perPage() === 20);
});
