<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    // CSV: รหัสนักศึกษา + คะแนนแต่ละชิ้นงาน (กรองตามวิชา/ห้อง)
    public function csv(Request $request, Subject $subject): StreamedResponse
    {
        $roomId = $request->integer('room') ?: null;

        $assignments = $subject->assignments()->orderBy('id')->get();

        $students = Student::with('room')
            ->whereHas('subjects', fn ($q) => $q->whereKey($subject->id))
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
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

            $header = ['รหัสนักศึกษา', 'ชื่อ-สกุล', 'ห้อง'];
            foreach ($assignments as $a) {
                $header[] = $a->title;
            }
            $header[] = 'รวม';
            fputcsv($out, $header);

            foreach ($students as $st) {
                $row = [$st->student_code, $st->full_name, $st->room?->name ?? ''];
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
