<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    // CSV: รหัสนักศึกษา + คะแนนแต่ละชิ้นงาน (กรองตามวิชา/กลุ่มเรียน)
    public function csv(Request $request, Subject $subject): StreamedResponse
    {
        $group = $request->string('group')->toString() ?: null;

        $assignments = $subject->assignments()->orderBy('id')->get();

        $students = Student::query()
            ->whereHas('subjects', fn ($q) => $q->whereKey($subject->id))
            ->when($group, fn ($q) => $q->where('study_group', $group))
            ->orderBy('student_code')->get();

        // map: student_id => [assignment_id => score]
        $scores = \App\Models\Submission::whereIn('assignment_id', $assignments->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->keyBy('assignment_id'));

        $filename = 'scores_' . $subject->code . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($assignments, $students, $scores) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM ให้ Excel อ่านภาษาไทยถูก

            $header = ['รหัสนักศึกษา', 'ชื่อ-นามสกุล', 'กลุ่มเรียน'];
            foreach ($assignments as $a) {
                $header[] = $a->title;
            }
            $header[] = 'รวม';
            fputcsv($out, $header);

            foreach ($students as $st) {
                $row = [$st->student_code, $st->full_name, $st->study_group ?? ''];
                $total = 0;
                foreach ($assignments as $a) {
                    $sub = $scores[$st->id][$a->id] ?? null;
                    $score = $sub && $sub->score !== null ? (float) $sub->score : null;
                    $row[] = $score !== null ? $score : '';
                    $total += $score ?? 0;
                }
                $row[] = $total;
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
