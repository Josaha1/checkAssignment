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

// วิชา + งานสอบ(เต็ม100) + งานราย week(เต็ม10) + นศ.1 คน ที่ส่งทั้งสองงาน
// จำลองเคสจริง: คะแนนสอบ Level 3 (100) รั่วไปทับ week10 (10) ของคนเดียวกัน
function bleedContext(): array
{
    $subject = Subject::create(['code' => 'BL-1', 'name' => 'วิชาบลีด']);
    $exam = Assignment::create(['subject_id' => $subject->id, 'title' => 'คะแนนสอบ Level 3', 'max_score' => 100]);
    $week = Assignment::create(['subject_id' => $subject->id, 'title' => 'week10', 'max_score' => 10]);

    $st = Student::create([
        'student_code' => 'B001', 'full_name' => 'น บลีด',
        'email' => 'b001@x.net', 'password' => 'x', 'study_group' => '044',
    ]);
    $st->subjects()->attach($subject->id);

    $examSub = Submission::create(['assignment_id' => $exam->id, 'student_id' => $st->id, 'submitted_at' => now()]);
    $weekSub = Submission::create(['assignment_id' => $week->id, 'student_id' => $st->id, 'submitted_at' => now()]);

    return compact('subject', 'exam', 'week', 'st', 'examSub', 'weekSub');
}

// A) โครงสร้าง: input คะแนนโหมดต่อชิ้นงานต้องมี wire:key ผูก submissionId
//    → พอสลับงาน submissionId เปลี่ยน morphdom ทิ้ง element เดิม สร้างใหม่ ค่าที่ค้างใน DOM หายไป
it('โหมดต่อชิ้นงาน: input คะแนนมี wire:key ผูก submissionId (กันค่าค้างข้ามงานตอนสลับงาน)', function () {
    $admin = User::factory()->create();
    $c = bleedContext();

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['week']->id)
        ->assertOk()
        ->assertSeeHtml('wire:key="score-' . $c['weekSub']->id . '"');
});

// B) พฤติกรรม: พิมพ์คะแนนงานสอบไว้ (ยังไม่กดบันทึก) แล้วสลับไปงาน week
//    ค่าที่ค้างต้องไม่ถูกบันทึกลงงานสอบตอนกดบันทึกงาน week
it('สลับงานแล้วล้างคะแนนที่พิมพ์ค้าง ไม่ให้ persist ข้ามงานตอนกดบันทึก', function () {
    $admin = User::factory()->create();
    $c = bleedContext();

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['exam']->id)
        ->set('scores.' . $c['examSub']->id, '82') // จำลองพิมพ์คะแนนสอบ (deferred sync)
        ->set('assignmentId', $c['week']->id)      // สลับไป week (ยังไม่กดบันทึกงานสอบ)
        ->call('saveScores');                       // กดบันทึกตอนอยู่หน้า week

    // คะแนนสอบต้องยังว่าง — ค่า 82 ที่ค้างต้องไม่ไหลไปลงงานสอบ
    expect($c['examSub']->fresh()->score)->toBeNull();
    // และต้องไม่หลุดไปลง week ด้วย
    expect($c['weekSub']->fresh()->score)->toBeNull();
});
