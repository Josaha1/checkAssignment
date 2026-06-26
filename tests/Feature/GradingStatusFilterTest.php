<?php

use App\Livewire\Admin\Grading;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\SubmissionHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// วิชา + งาน + นศ. 3 สถานะ: 001 รอตรวจ / 002 ตรวจแล้ว / 003 ยังไม่ส่ง
function gradingContext(): array
{
    $subject = Subject::create(['code' => 'G-1', 'name' => 'วิชาตรวจ']);
    $assignment = Assignment::create(['subject_id' => $subject->id, 'title' => 'งานที่ 1', 'max_score' => 10]);

    $enroll = function (string $code) use ($subject) {
        $st = Student::create([
            'student_code' => $code, 'full_name' => 'น ' . $code,
            'email' => $code . '@x.net', 'password' => 'x', 'study_group' => '043',
        ]);
        $st->subjects()->attach($subject->id);
        return $st;
    };

    $pending = $enroll('001');
    $graded = $enroll('002');
    $missing = $enroll('003');

    Submission::create(['assignment_id' => $assignment->id, 'student_id' => $pending->id, 'submitted_at' => now()]);
    Submission::create(['assignment_id' => $assignment->id, 'student_id' => $graded->id, 'submitted_at' => now(), 'score' => 8, 'graded_at' => now()]);

    return compact('subject', 'assignment', 'pending', 'graded', 'missing');
}

it('เลือกงาน → ตารางต่อชิ้นงาน 1 แถว/นักศึกษา พร้อมสถานะ', function () {
    $admin = User::factory()->create();
    $c = gradingContext();

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['assignment']->id)
        ->assertOk()
        ->assertViewHas('rows', fn ($r) => $r->count() === 3)
        ->assertViewHas('rows', fn ($r) => $r->pluck('status')->all() === ['pending', 'graded', 'missing'])
        ->assertViewHas('matrix', fn ($m) => $m === []); // ไม่ใช่โหมด matrix
});

it('filter สถานะการส่ง กรองแถวได้ (missing/pending/graded)', function () {
    $admin = User::factory()->create();
    $c = gradingContext();

    $t = Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['assignment']->id);

    $t->set('statusFilter', 'missing')
        ->assertViewHas('rows', fn ($r) => $r->count() === 1 && $r->first()['student']->student_code === '003');
    $t->set('statusFilter', 'pending')
        ->assertViewHas('rows', fn ($r) => $r->count() === 1 && $r->first()['student']->student_code === '001');
    $t->set('statusFilter', 'graded')
        ->assertViewHas('rows', fn ($r) => $r->count() === 1 && $r->first()['student']->student_code === '002');
    $t->set('statusFilter', '')
        ->assertViewHas('rows', fn ($r) => $r->count() === 3);
});

it('ไม่เลือกงาน → คงโหมด matrix รวมทุกงาน (backward compat)', function () {
    $admin = User::factory()->create();
    $c = gradingContext();

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->assertOk()
        ->assertViewHas('rows', fn ($r) => $r->isEmpty())
        ->assertViewHas('matrix', fn ($m) => ! empty($m));
});

it('โหมดต่อชิ้นงานโชว์เวลาส่ง + ปุ่มประวัติ + timeline', function () {
    $admin = User::factory()->create();
    $c = gradingContext();
    $sub = Submission::where('student_id', $c['pending']->id)->first();
    SubmissionHistory::create(['submission_id' => $sub->id, 'action' => 'uploaded', 'actor' => 'นศ', 'detail' => 'a.pdf']);

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['assignment']->id)
        ->assertOk()
        ->assertSee($sub->submitted_at->format('d/m/Y H:i')) // คอลัมน์เวลาส่ง
        ->assertSee('ประวัติ')                                // ปุ่ม toggle
        ->assertSee('อัปโหลดไฟล์');                           // x-submission-timeline
});

it('เปลี่ยนวิชา รีเซ็ต assignmentId + statusFilter', function () {
    $admin = User::factory()->create();
    $c = gradingContext();
    $other = Subject::create(['code' => 'G-2', 'name' => 'อื่น']);

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['assignment']->id)
        ->set('statusFilter', 'graded')
        ->set('subjectId', $other->id) // updatedSubjectId
        ->assertSet('assignmentId', null)
        ->assertSet('statusFilter', '');
});

it('บันทึกคะแนนในโหมดต่อชิ้นงานได้', function () {
    $admin = User::factory()->create();
    $c = gradingContext();
    $sub = Submission::where('student_id', $c['pending']->id)->first();

    Livewire::actingAs($admin)->test(Grading::class)
        ->set('subjectId', $c['subject']->id)
        ->set('assignmentId', $c['assignment']->id)
        ->set("scores.{$sub->id}", '9')
        ->call('saveScores')
        ->assertHasNoErrors();

    expect((float) $sub->fresh()->score)->toBe(9.0);
});
