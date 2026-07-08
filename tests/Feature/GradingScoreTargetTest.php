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

function enrollStudent(string $code, Subject $subject): Student
{
    $st = Student::create([
        'student_code' => $code, 'full_name' => 'น ' . $code,
        'email' => $code . '@x.net', 'password' => 'x', 'study_group' => '043',
    ]);
    $st->subjects()->attach($subject->id);
    return $st;
}

it('บันทึกคะแนนลง "ถูกคน" — ตั้งของคนเดียว คนอื่นไม่เปลี่ยน (โหมดต่อชิ้นงาน)', function () {
    $admin = User::factory()->create();
    $subject = Subject::create(['code' => 'T-1', 'name' => 'วิชา']);
    $asg = Assignment::create(['subject_id' => $subject->id, 'title' => 'งาน X', 'max_score' => 10]);
    $a = enrollStudent('A01', $subject);
    $b = enrollStudent('B02', $subject);
    $subA = Submission::create(['assignment_id' => $asg->id, 'student_id' => $a->id, 'submitted_at' => now()]);
    $subB = Submission::create(['assignment_id' => $asg->id, 'student_id' => $b->id, 'submitted_at' => now()]);

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $subject->id)
        ->set('assignmentId', $asg->id)
        ->set("scores.{$subA->id}", '9') // ให้คะแนนเฉพาะ A
        ->call('saveScores')
        ->assertHasNoErrors();

    expect((float) $subA->fresh()->score)->toBe(9.0);       // A ได้คะแนน
    expect($subB->fresh()->score)->toBeNull();               // B ไม่รั่ว
    // ถูกงาน: submission ผูก (คน, งาน) ตายตัว — saveScores ไม่แตะ
    expect($subA->fresh()->student_id)->toBe($a->id);
    expect($subA->fresh()->assignment_id)->toBe($asg->id);
});

it('บันทึกคะแนนลง "ถูกงาน" — คนเดียวหลายงาน ตั้งงานเดียว งานอื่นไม่เปลี่ยน (โหมด matrix)', function () {
    $admin = User::factory()->create();
    $subject = Subject::create(['code' => 'T-2', 'name' => 'วิชา']);
    $asgX = Assignment::create(['subject_id' => $subject->id, 'title' => 'งาน X', 'max_score' => 10]);
    $asgY = Assignment::create(['subject_id' => $subject->id, 'title' => 'งาน Y', 'max_score' => 10]);
    $st = enrollStudent('C03', $subject);
    $subX = Submission::create(['assignment_id' => $asgX->id, 'student_id' => $st->id, 'submitted_at' => now()]);
    $subY = Submission::create(['assignment_id' => $asgY->id, 'student_id' => $st->id, 'submitted_at' => now()]);

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $subject->id) // matrix (ไม่เลือกงาน)
        ->set("scores.{$subX->id}", '7') // ให้คะแนนเฉพาะงาน X
        ->call('saveScores')
        ->assertHasNoErrors();

    expect((float) $subX->fresh()->score)->toBe(7.0);       // งาน X ได้คะแนน
    expect($subY->fresh()->score)->toBeNull();               // งาน Y ไม่เปลี่ยน
    expect($subX->fresh()->assignment_id)->toBe($asgX->id);  // ลงงานที่ถูก
});
