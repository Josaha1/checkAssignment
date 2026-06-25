<?php

use App\Livewire\Admin\Admins;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Enroll;
use App\Livewire\Admin\Grading;
use App\Livewire\Admin\Reports;
use App\Livewire\Admin\Students;
use App\Livewire\Admin\Subjects;
use App\Livewire\Admin\SubmissionBoard;
use App\Livewire\Student\History;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeStudent(string $code, string $name, string $group = '043'): Student
{
    return Student::create([
        'student_code' => $code, 'full_name' => $name,
        'email' => $code . '@x.net', 'password' => 'x', 'study_group' => $group,
    ]);
}

it('Subjects: หัวตารางสลับทิศ asc⇄desc + เปลี่ยนคอลัมน์เริ่ม asc', function () {
    $admin = User::factory()->create();
    Subject::create(['code' => 'B-200', 'name' => 'ฟิสิกส์']);
    Subject::create(['code' => 'A-100', 'name' => 'คณิตศาสตร์']);

    $c = Livewire::actingAs($admin)->test(Subjects::class)
        ->assertOk()
        ->assertSet('sortBy', 'code')->assertSet('sortDir', 'asc')
        ->assertViewHas('subjects', fn ($s) => $s->pluck('code')->all() === ['A-100', 'B-200']);

    $c->call('sort', 'code')               // คลิกซ้ำ → desc
        ->assertSet('sortDir', 'desc')
        ->assertViewHas('subjects', fn ($s) => $s->pluck('code')->all() === ['B-200', 'A-100']);

    $c->call('sort', 'name')               // คอลัมน์ใหม่ → asc
        ->assertSet('sortBy', 'name')->assertSet('sortDir', 'asc')
        ->assertViewHas('subjects', fn ($s) => $s->pluck('name')->all() === ['คณิตศาสตร์', 'ฟิสิกส์']);
});

it('Subjects: ค้นหากรองเฉพาะที่ตรง', function () {
    $admin = User::factory()->create();
    Subject::create(['code' => 'A-100', 'name' => 'คณิตศาสตร์']);
    Subject::create(['code' => 'B-200', 'name' => 'ฟิสิกส์']);

    Livewire::actingAs($admin)->test(Subjects::class)
        ->set('search', 'ฟิสิกส์')
        ->assertViewHas('subjects', fn ($s) => $s->count() === 1 && $s->first()->code === 'B-200');
});

it('Subjects: sort คอลัมน์นอก whitelist ไม่ขยับ state (กัน inject)', function () {
    $admin = User::factory()->create();
    Livewire::actingAs($admin)->test(Subjects::class)
        ->call('sort', 'code); drop table')
        ->assertSet('sortBy', 'code')->assertSet('sortDir', 'asc');
});

it('Students: เรียงแบบ paginate + ค้นหา', function () {
    $admin = User::factory()->create();
    makeStudent('002', 'บี');
    makeStudent('001', 'เอ');

    Livewire::actingAs($admin)->test(Students::class)
        ->assertViewHas('students', fn ($p) => $p->getCollection()->pluck('student_code')->all() === ['001', '002'])
        ->call('sort', 'student_code')     // desc
        ->assertViewHas('students', fn ($p) => $p->getCollection()->pluck('student_code')->all() === ['002', '001'])
        ->set('search', 'บี')
        ->assertViewHas('students', fn ($p) => $p->getCollection()->pluck('student_code')->all() === ['002']);
});

it('Admins: เรียง email + ค้นหาชื่อ/อีเมล', function () {
    $admin = User::factory()->create(['name' => 'Zed', 'email' => 'zed@x.net']);
    User::factory()->create(['name' => 'Amy', 'email' => 'amy@x.net']);

    Livewire::actingAs($admin)->test(Admins::class)
        ->assertViewHas('admins', fn ($a) => $a->pluck('name')->all() === ['Amy', 'Zed']) // name asc
        ->call('sort', 'email')
        ->assertViewHas('admins', fn ($a) => $a->pluck('email')->all() === ['amy@x.net', 'zed@x.net'])
        ->set('search', 'amy')
        ->assertViewHas('admins', fn ($a) => $a->count() === 1 && $a->first()->name === 'Amy');
});

it('Reports: เรียงคอลัมน์คำนวณ (submission_total) ใน collection + ค้นหา', function () {
    $admin = User::factory()->create();
    $s1 = Subject::create(['code' => 'A-1', 'name' => 'Alpha']);
    $s2 = Subject::create(['code' => 'B-2', 'name' => 'Beta']);
    $a2 = Assignment::create(['subject_id' => $s2->id, 'title' => 'g2', 'max_score' => 10]);
    // B-2 มี 2 ที่ส่ง, A-1 มี 0
    Submission::create(['assignment_id' => $a2->id, 'student_id' => makeStudent('1', 'n1')->id, 'submitted_at' => now()]);
    Submission::create(['assignment_id' => $a2->id, 'student_id' => makeStudent('2', 'n2')->id, 'submitted_at' => now()]);

    $c = Livewire::actingAs($admin)->test(Reports::class)->assertOk()
        ->call('sort', 'submission_total')  // asc: A-1(0) ก่อน B-2(2)
        ->assertViewHas('subjects', fn ($s) => $s->pluck('code')->all() === ['A-1', 'B-2'])
        ->call('sort', 'submission_total')  // desc
        ->assertViewHas('subjects', fn ($s) => $s->pluck('code')->all() === ['B-2', 'A-1']);

    $c->set('search', 'Beta')
        ->assertViewHas('subjects', fn ($s) => $s->count() === 1 && $s->first()->code === 'B-2');
});

it('Reports: ตารางสถิติ sort แยก state กับตารางสรุป', function () {
    $admin = User::factory()->create();
    $s = Subject::create(['code' => 'A-1', 'name' => 'Alpha']);
    Assignment::create(['subject_id' => $s->id, 'title' => 'g1', 'max_score' => 10]);

    Livewire::actingAs($admin)->test(Reports::class)
        ->set('subjectId', $s->id)
        ->call('sortStat', 'submitted')
        ->assertSet('statSort', 'submitted')->assertSet('statDir', 'asc')
        ->assertSet('sortBy', 'code'); // ตารางสรุปไม่กระทบ
});

it('Dashboard: เรียงงานล่าสุด (8 ราย) ในหน่วยความจำ', function () {
    $admin = User::factory()->create();
    $s = Subject::create(['code' => 'C-1', 'name' => 'c']);
    $a = Assignment::create(['subject_id' => $s->id, 'title' => 'g', 'max_score' => 10]);
    Submission::create(['assignment_id' => $a->id, 'student_id' => makeStudent('999', 'Z')->id, 'submitted_at' => now()]);
    Submission::create(['assignment_id' => $a->id, 'student_id' => makeStudent('111', 'B')->id, 'submitted_at' => now()->subMinute()]);

    Livewire::actingAs($admin)->test(Dashboard::class)->assertOk()
        ->assertViewHas('recent', fn ($r) => $r->pluck('student.student_code')->all() === ['999', '111']) // default submitted_at desc
        ->call('sort', 'student_code')  // asc
        ->assertViewHas('recent', fn ($r) => $r->pluck('student.student_code')->all() === ['111', '999']);
});

it('History นักศึกษา: กรองตามวิชา + เรียงเวลา', function () {
    $st = makeStudent('670', 'น');
    $s1 = Subject::create(['code' => 'H-1', 'name' => 'Hist1']);
    $s2 = Subject::create(['code' => 'H-2', 'name' => 'Hist2']);
    $st->subjects()->attach([$s1->id, $s2->id]);
    $a1 = Assignment::create(['subject_id' => $s1->id, 'title' => 'งานเอ', 'max_score' => 10]);
    $a2 = Assignment::create(['subject_id' => $s2->id, 'title' => 'งานบี', 'max_score' => 10]);
    Submission::create(['assignment_id' => $a1->id, 'student_id' => $st->id, 'submitted_at' => now()]);
    Submission::create(['assignment_id' => $a2->id, 'student_id' => $st->id, 'submitted_at' => now()]);

    Livewire::actingAs($st, 'student')->test(History::class)->assertOk()
        ->assertViewHas('submissions', fn ($c) => $c->count() === 2)
        ->set('subjectId', $s1->id)
        ->assertViewHas('submissions', fn ($c) => $c->count() === 1)
        ->assertSee('งานเอ')->assertDontSee('งานบี');
});

it('Enroll/Grading/SubmissionBoard: sort คอลัมน์นักศึกษาได้ คอลัมน์คะแนนเรียงไม่ได้', function () {
    $admin = User::factory()->create();
    $s = Subject::create(['code' => 'X-1', 'name' => 'x']);

    Livewire::actingAs($admin)->test(Enroll::class, ['subject' => $s])
        ->call('sort', 'full_name')->assertSet('sortBy', 'full_name')->assertOk();

    Livewire::actingAs($admin)->test(SubmissionBoard::class)
        ->call('sort', 'full_name')->assertSet('sortBy', 'full_name')->assertOk();

    Livewire::actingAs($admin)->test(Grading::class)
        ->call('sort', 'full_name')->assertSet('sortBy', 'full_name')
        ->call('sort', 'score')->assertSet('sortBy', 'full_name')->assertOk(); // score นอก whitelist → คงเดิม
});
