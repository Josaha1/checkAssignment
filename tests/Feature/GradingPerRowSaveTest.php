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

// วิชา + 1 งาน + 2 นศ. ที่ส่งงานแล้ว (มีหลาย input คะแนนในตาราง)
function perRowContext(): array
{
    $subject = Subject::create(['code' => 'PR-1', 'name' => 'วิชาราย row']);
    $a = Assignment::create(['subject_id' => $subject->id, 'title' => 'งาน', 'max_score' => 10]);

    $mk = function (string $code) use ($subject) {
        $st = Student::create([
            'student_code' => $code, 'full_name' => 'น ' . $code,
            'email' => $code . '@x.net', 'password' => 'x', 'study_group' => '044',
        ]);
        $st->subjects()->attach($subject->id);
        return $st;
    };
    $s1 = $mk('P001');
    $s2 = $mk('P002');
    $sub1 = Submission::create(['assignment_id' => $a->id, 'student_id' => $s1->id, 'submitted_at' => now()]);
    $sub2 = Submission::create(['assignment_id' => $a->id, 'student_id' => $s2->id, 'submitted_at' => now()]);

    return compact('subject', 'a', 's1', 's2', 'sub1', 'sub2');
}

// บันทึกราย row: ระบุ submission → บันทึกเฉพาะแถวนั้น แม้แถวอื่นพิมพ์คะแนนค้างไว้
it('saveScores(subId) บันทึกเฉพาะ submission ที่ระบุ แถวอื่นไม่ถูกบันทึก', function () {
    $admin = User::factory()->create();
    $c = perRowContext();

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['a']->id)
        ->set('scores.' . $c['sub1']->id, '8')  // พิมพ์คะแนนทั้ง 2 แถว
        ->set('scores.' . $c['sub2']->id, '9')
        ->call('saveScores', $c['sub1']->id);    // แต่กดบันทึกเฉพาะแถว sub1

    expect($c['sub1']->fresh()->score)->toEqual(8.0); // แถวที่กด → บันทึก
    expect($c['sub2']->fresh()->score)->toBeNull();   // แถวอื่น → ยังไม่บันทึก
});

// โครงสร้าง: ตารางโหมดต่อชิ้นงานมีปุ่มบันทึกราย row (wire:click ผูก submissionId) ต่อทุกแถวที่ส่งงาน
it('โหมดต่อชิ้นงาน: แต่ละแถวมีปุ่มบันทึกราย row (wire:click=saveScores(subId))', function () {
    $admin = User::factory()->create();
    $c = perRowContext();

    $t = Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['a']->id)
        ->assertOk();

    $t->assertSeeHtml('wire:click="saveScores(' . $c['sub1']->id . ')"');
    $t->assertSeeHtml('wire:click="saveScores(' . $c['sub2']->id . ')"');
});

// ปุ่มบันทึกรวมเดิม (ไม่ส่ง arg) ต้องบันทึกทุกแถวเหมือนเดิม (backward compat)
it('saveScores() ไม่ระบุ → บันทึกทุกแถวเหมือนเดิม (ปุ่มรวม)', function () {
    $admin = User::factory()->create();
    $c = perRowContext();

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['a']->id)
        ->set('scores.' . $c['sub1']->id, '8')
        ->set('scores.' . $c['sub2']->id, '9')
        ->call('saveScores');

    expect($c['sub1']->fresh()->score)->toEqual(8.0);
    expect($c['sub2']->fresh()->score)->toEqual(9.0);
});
