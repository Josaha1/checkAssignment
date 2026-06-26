<?php

namespace App\Livewire\Student;

use App\Contracts\DriveStorage;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SubmissionHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Submit extends Component
{
    use WithFileUploads;

    public Assignment $assignment;
    public ?Submission $submission = null;
    public array $uploads = []; // ไฟล์ที่เลือกใหม่ (หลายไฟล์)

    public const ALLOWED = 'pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip';
    public const MAX_KB = 40960; // 40MB/ไฟล์ (Livewire temp-upload + PHP ini 100M รองรับอยู่แล้ว)
    public const MAX_FILES = 10;

    public function mount(Assignment $assignment): void
    {
        $student = Auth::guard('student')->user();
        abort_unless($student->subjects()->whereKey($assignment->subject_id)->exists(), 403);

        $this->assignment = $assignment->load('subject');
        $this->submission = Submission::with('files')
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)->first();
    }

    public function save(): void
    {
        $student = Auth::guard('student')->user();

        if ($this->submission && $this->submission->score !== null) {
            throw ValidationException::withMessages(['uploads' => 'งานนี้ถูกตรวจให้คะแนนแล้ว แก้ไขไม่ได้']);
        }

        $existing = $this->submission?->files()->count() ?? 0;
        $this->validate([
            'uploads' => [$existing > 0 ? 'nullable' : 'required', 'array', 'max:' . self::MAX_FILES],
            // ใช้ extensions (เช็คนามสกุล) ไม่ใช่ mimes — .xls จากระบบทะเบียนมักเป็น HTML-table, .xlsx เดาเป็น zip → mimes ปัดทิ้ง
            'uploads.*' => ['file', 'max:' . self::MAX_KB, 'extensions:' . self::ALLOWED],
        ], attributes: ['uploads' => 'ไฟล์งาน', 'uploads.*' => 'ไฟล์']);

        $drive = app(DriveStorage::class);
        if (! $drive->isConnected()) {
            throw ValidationException::withMessages(['uploads' => 'ระบบยังไม่ได้เชื่อมต่อ Google Drive — แจ้งอาจารย์ผู้ดูแล']);
        }

        // โครงสร้าง: วิชา / กลุ่มเรียน / งาน / รหัสนักศึกษา
        $folderId = $drive->ensureFolderPath([
            $this->clean($this->assignment->subject->code . ' ' . $this->assignment->subject->name),
            $this->clean($student->study_group ?? 'ไม่มีกลุ่ม'),
            $this->clean($this->assignment->title),
            $student->student_code,
        ]);

        $submission = Submission::updateOrCreate(
            ['assignment_id' => $this->assignment->id, 'student_id' => $student->id],
            ['submitted_at' => now()],
        );

        foreach ($this->uploads as $file) {
            $info = $drive->upload(
                $file->getRealPath(),
                $file->getClientOriginalName(),
                $file->getMimeType() ?: 'application/octet-stream',
                $folderId,
            );
            SubmissionFile::create([
                'submission_id' => $submission->id,
                'drive_file_id' => $info['id'],
                'name' => $file->getClientOriginalName(),
                'url' => $info['url'],
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            SubmissionHistory::create([
                'submission_id' => $submission->id,
                'action' => 'uploaded',
                'actor' => $student->full_name,
                'detail' => $file->getClientOriginalName(),
            ]);
        }

        session()->flash('ok', 'ส่งงานเรียบร้อยแล้ว');
        $this->redirectRoute('student.dashboard', navigate: true);
    }

    public function removeFile(int $fileId): void
    {
        $file = SubmissionFile::where('submission_id', $this->submission?->id)->find($fileId);
        if (! $file) {
            return;
        }
        $student = Auth::guard('student')->user();
        $fileName = $file->name;

        app(DriveStorage::class)->delete($file->drive_file_id);
        $file->delete();

        SubmissionHistory::create([
            'submission_id' => $this->submission->id,
            'action' => 'file_deleted',
            'actor' => $student?->full_name,
            'detail' => $fileName,
        ]);

        // ลบไฟล์ → คะแนนงานนั้นหายทันที (ลบไฟล์ใดก็ตาม) — แม้ตรวจแล้วก็ลบได้
        if ($this->submission->score !== null) {
            $this->submission->update(['score' => null, 'graded_at' => null]);
            SubmissionHistory::create([
                'submission_id' => $this->submission->id,
                'action' => 'score_cleared',
                'actor' => $student?->full_name,
                'detail' => 'ลบไฟล์ ' . $fileName,
            ]);
        }

        // ลบไฟล์ครบ (0 ไฟล์) → ถือว่ายังไม่ส่ง: เคลียร์ submitted_at ให้สถานะทุกหน้าตรงกัน (เก็บ record+history ไว้)
        if ($this->submission->files()->count() === 0) {
            $this->submission->update(['submitted_at' => null]);
        }
        $this->submission->refresh();
    }

    private function clean(string $name): string
    {
        // Drive รองรับ / ในชื่อโฟลเดอร์ — เก็บชื่อตามจริง แค่ตัดช่องว่างหัวท้าย
        return trim($name) ?: '-';
    }

    public function render()
    {
        return view('livewire.student.submit');
    }
}
