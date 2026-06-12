<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // แอดมิน
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'ผู้ดูแลระบบ', 'password' => Hash::make('password')],
        );

        // ห้องเรียน
        $r1 = Room::firstOrCreate(['name' => 'ปวส.1/1']);
        $r2 = Room::firstOrCreate(['name' => 'ปวส.1/2']);

        // รายวิชา
        $web = Subject::firstOrCreate(['code' => '30901-2001'], ['name' => 'การพัฒนาเว็บแอปพลิเคชัน']);
        $db = Subject::firstOrCreate(['code' => '30901-2002'], ['name' => 'ระบบฐานข้อมูล']);

        // ชิ้นงาน
        $a1 = Assignment::firstOrCreate(['subject_id' => $web->id, 'title' => 'งานที่ 1 — HTML/CSS'], ['description' => 'ทำหน้าเว็บ portfolio แล้วแนบลิงก์', 'max_score' => 10, 'due_date' => now()->addDays(7)]);
        $a2 = Assignment::firstOrCreate(['subject_id' => $web->id, 'title' => 'งานที่ 2 — JavaScript'], ['description' => 'เกมทายเลข แนบลิงก์', 'max_score' => 20, 'due_date' => now()->addDays(14)]);
        Assignment::firstOrCreate(['subject_id' => $db->id, 'title' => 'งานที่ 1 — ER Diagram'], ['description' => 'ออกแบบ ER แนบลิงก์', 'max_score' => 15]);

        // นักศึกษา (วันเกิด = รหัสผ่าน)
        $students = [
            ['670012345', 'สมชาย ใจดี', '2005-03-15', $r1->id],
            ['670012346', 'สมหญิง รักเรียน', '2005-07-22', $r1->id],
            ['670012347', 'มานะ พากเพียร', '2006-01-09', $r2->id],
            ['670012348', 'ปิติ ยินดี', '2005-11-30', $r2->id],
        ];
        foreach ($students as [$code, $name, $bd, $roomId]) {
            $s = Student::updateOrCreate(
                ['student_code' => $code],
                ['full_name' => $name, 'birthdate' => $bd, 'room_id' => $roomId],
            );
            $s->subjects()->syncWithoutDetaching([$web->id, $db->id]);
        }

        // ตัวอย่างการส่งงาน (พร้อมไฟล์ตัวอย่าง)
        $first = Student::where('student_code', '670012345')->first();
        $s1 = Submission::updateOrCreate(
            ['assignment_id' => $a1->id, 'student_id' => $first->id],
            ['submitted_at' => now()],
        );
        \App\Models\SubmissionFile::updateOrCreate(
            ['submission_id' => $s1->id, 'name' => 'portfolio.pdf'],
            ['drive_file_id' => 'demo1', 'url' => 'https://drive.google.com/file/d/demo1/view', 'mime' => 'application/pdf', 'size' => 102400],
        );
        $s2 = Submission::updateOrCreate(
            ['assignment_id' => $a2->id, 'student_id' => $first->id],
            ['submitted_at' => now(), 'score' => 18, 'graded_at' => now()],
        );
        \App\Models\SubmissionFile::updateOrCreate(
            ['submission_id' => $s2->id, 'name' => 'game.zip'],
            ['drive_file_id' => 'demo2', 'url' => 'https://drive.google.com/file/d/demo2/view', 'mime' => 'application/zip', 'size' => 204800],
        );
    }
}
