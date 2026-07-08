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

// สร้างวิชา + งาน + นักศึกษา n คน (ผูกวิชา) — คืน subject/assignment/นักศึกษาที่รู้จัก
function gradingPageCtx(int $extra = 0): array
{
    $subject = Subject::create(['code' => 'P-1', 'name' => 'วิชาแบ่งหน้า']);
    $assignment = Assignment::create(['subject_id' => $subject->id, 'title' => 'งาน 1', 'max_score' => 10]);

    $mk = function (string $code, string $name) use ($subject) {
        $st = Student::create([
            'student_code' => $code, 'full_name' => $name,
            'email' => $code . '@x.net', 'password' => 'x', 'study_group' => '043',
        ]);
        $st->subjects()->attach($subject->id);
        return $st;
    };
    $somchai = $mk('67001', 'สมชาย ใจดี');
    $somsri = $mk('67002', 'สมศรี มีสุข');
    $mana = $mk('67003', 'มานะ อดทน');

    // นักศึกษาเสริมให้เกิน 1 หน้า (perPage=20) เมื่อ $extra>0
    for ($i = 1; $i <= $extra; $i++) {
        $mk('68' . str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'นศเสริม ' . $i);
    }

    return compact('subject', 'assignment', 'somchai', 'somsri', 'mana');
}

it('โหมด matrix: students เป็น paginator + ค้นหาด้วยรหัส/ชื่อได้', function () {
    $admin = User::factory()->create();
    $c = gradingPageCtx();

    $t = Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id) // matrix (ไม่เลือกงาน)
        ->assertOk()
        ->assertViewHas('students', fn ($p) => $p instanceof LengthAwarePaginator && $p->total() === 3);

    // ค้นด้วยรหัส
    $t->set('search', '67002')
        ->assertViewHas('students', fn ($p) => $p->total() === 1 && $p->getCollection()->first()->student_code === '67002');

    // ค้นด้วยชื่อ (บางส่วน)
    $t->set('search', 'มานะ')
        ->assertViewHas('students', fn ($p) => $p->total() === 1 && $p->getCollection()->first()->student_code === '67003');

    // ล้างค้นหา → ครบ 3
    $t->set('search', '')
        ->assertViewHas('students', fn ($p) => $p->total() === 3);
});

it('โหมดต่อชิ้นงาน: ค้นหากรอง rows ได้ + students ยังเป็น paginator', function () {
    $admin = User::factory()->create();
    $c = gradingPageCtx();
    Submission::create(['assignment_id' => $c['assignment']->id, 'student_id' => $c['somchai']->id, 'submitted_at' => now()]);

    $t = Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['assignment']->id)
        ->assertOk()
        ->assertViewHas('students', fn ($p) => $p instanceof LengthAwarePaginator)
        ->assertViewHas('rows', fn ($r) => $r->count() === 3);

    $t->set('search', 'สมชาย')
        ->assertViewHas('rows', fn ($r) => $r->count() === 1 && $r->first()['student']->student_code === '67001');
});

it('เกิน 20 คน → แบ่งหน้า 20/หน้า', function () {
    $admin = User::factory()->create();
    $c = gradingPageCtx(22); // 3 + 22 = 25 คน

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->assertViewHas('students', fn ($p) => $p->total() === 25 && $p->perPage() === 20 && $p->count() === 20);
});

it('เปลี่ยนค้นหา รีเซ็ตกลับหน้า 1', function () {
    $admin = User::factory()->create();
    $c = gradingPageCtx(22);

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->call('gotoPage', 2)
        ->assertViewHas('students', fn ($p) => $p->currentPage() === 2)
        ->set('search', 'นศเสริม') // เปลี่ยนตัวกรอง → resetPage
        ->assertViewHas('students', fn ($p) => $p->currentPage() === 1);
});
