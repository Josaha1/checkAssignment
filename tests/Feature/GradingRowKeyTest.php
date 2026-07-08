<?php

use App\Livewire\Admin\Grading;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// วิชา + 2 งาน + 3 นศ. (มี submission หลายคน → หลาย input คะแนนใน loop เดียว)
function keyContext(): array
{
    $subject = Subject::create(['code' => 'K-1', 'name' => 'วิชาคีย์']);
    $a1 = Assignment::create(['subject_id' => $subject->id, 'title' => 'งาน A', 'max_score' => 10]);
    $a2 = Assignment::create(['subject_id' => $subject->id, 'title' => 'งาน B', 'max_score' => 10]);

    $mk = function (string $code) use ($subject) {
        $st = Student::create([
            'student_code' => $code, 'full_name' => 'น ' . $code,
            'email' => $code . '@x.net', 'password' => 'x', 'study_group' => '043',
        ]);
        $st->subjects()->attach($subject->id);
        return $st;
    };
    $s1 = $mk('K001');
    $s2 = $mk('K002');
    $s3 = $mk('K003');

    // ส่งงาน A: s1, s2 (ยังไม่ตรวจ) — เกิด input คะแนนหลายตัวใน loop
    Submission::create(['assignment_id' => $a1->id, 'student_id' => $s1->id, 'submitted_at' => now()]);
    Submission::create(['assignment_id' => $a1->id, 'student_id' => $s2->id, 'submitted_at' => now()]);

    return compact('subject', 'a1', 'a2', 's1', 's2', 's3');
}

it('โหมดต่อชิ้นงาน: ทุกแถวนักศึกษามี wire:key เฉพาะตัว (กัน morphdom สลับ input คะแนนข้ามคน)', function () {
    $admin = User::factory()->create();
    $c = keyContext();

    $t = Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['a1']->id)
        ->assertOk();

    // แต่ละ <tbody> ของนักศึกษาต้องมี key ยึด identity ไม่ให้ค่า input reuse ข้ามแถวตอน re-render
    foreach ([$c['s1'], $c['s2'], $c['s3']] as $st) {
        $t->assertSeeHtml('wire:key="grow-' . $st->id . '"');
    }
});

it('โหมด matrix: ทุกแถว + ทุกช่องคะแนนมี wire:key เฉพาะตัว', function () {
    $admin = User::factory()->create();
    $c = keyContext();

    $t = Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id) // assignmentId ว่าง = matrix
        ->assertOk();

    foreach ([$c['s1'], $c['s2'], $c['s3']] as $st) {
        $t->assertSeeHtml('wire:key="mrow-' . $st->id . '"');
    }
    // ช่องคะแนน (td) ต่องาน ต้อง key ด้วย submissionId+assignment กัน bleed ในตารางรวม
    $t->assertSeeHtml('wire:key="mcell-' . $c['s1']->id . '-' . $c['a1']->id . '"');
    $t->assertSeeHtml('wire:key="mcell-' . $c['s2']->id . '-' . $c['a1']->id . '"');
});
