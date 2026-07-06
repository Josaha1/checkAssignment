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
    public string $links = '';  // ลิงก์งานที่แปะ (1 บรรทัด = 1 ลิงก์)

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

        // ลิงก์: 1 บรรทัด = 1 ลิงก์ (ตัดช่องว่าง + ข้ามบรรทัดว่าง)
        $links = collect(preg_split('/\r\n|\r|\n/', $this->links))
            ->map(fn ($l) => trim($l))->filter()->values();

        $existing = $this->submission?->files()->count() ?? 0;
        $this->validate([
            'uploads' => ['nullable', 'array', 'max:' . self::MAX_FILES],
            // ใช้ extensions (เช็คนามสกุล) ไม่ใช่ mimes — .xls จากระบบทะเบียนมักเป็น HTML-table, .xlsx เดาเป็น zip → mimes ปัดทิ้ง
            'uploads.*' => ['file', 'max:' . self::MAX_KB, 'extensions:' . self::ALLOWED],
        ], attributes: ['uploads' => 'ไฟล์งาน', 'uploads.*' => 'ไฟล์']);

        // ลิงก์ต้องเป็น http/https ที่ถูกต้อง — กัน javascript:/ftp ที่คลิกแล้วอันตราย
        $badLinks = $links->reject(fn ($u) => filter_var($u, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $u));
        if ($badLinks->isNotEmpty()) {
            throw ValidationException::withMessages(['links' => 'ลิงก์ต้องขึ้นต้น http:// หรือ https:// : ' . $badLinks->implode(', ')]);
        }
        if ($links->count() > self::MAX_FILES) {
            throw ValidationException::withMessages(['links' => 'ใส่ลิงก์ได้สูงสุด ' . self::MAX_FILES . ' ลิงก์']);
        }

        // ต้องมีอย่างน้อย 1 อย่าง: ของเดิม / ไฟล์ใหม่ / ลิงก์ใหม่
        if ($existing === 0 && count($this->uploads) === 0 && $links->isEmpty()) {
            throw ValidationException::withMessages(['uploads' => 'แนบไฟล์หรือใส่ลิงก์อย่างน้อย 1 อย่าง']);
        }

        // เตรียม Drive เฉพาะเมื่อมีไฟล์ใหม่ (เช็ค+สร้างโฟลเดอร์ก่อน updateOrCreate เพื่อไม่ทิ้ง submission ค้างถ้า Drive ล่ม)
        $drive = null;
        $folderId = null;
        if (count($this->uploads) > 0) {
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
        }

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
                'type' => 'file',
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

        // ลิงก์: drive_file_id/mime/size = null, name เก็บ url เพื่อให้ทุกหน้าที่วน files() แสดงเป็นลิงก์กดได้เลย
        foreach ($links as $url) {
            SubmissionFile::create([
                'submission_id' => $submission->id,
                'type' => 'link',
                'drive_file_id' => null,
                'name' => $url,
                'url' => $url,
            ]);
            SubmissionHistory::create([
                'submission_id' => $submission->id,
                'action' => 'link_added',
                'actor' => $student->full_name,
                'detail' => $url,
            ]);
        }

        // ส่งไฟล์/ลิงก์เพิ่มหลังตรวจแล้ว → ล้างคะแนนงานนั้น ต้องให้อาจารย์ตรวจใหม่
        if ((count($this->uploads) > 0 || $links->isNotEmpty()) && $submission->score !== null) {
            $submission->update(['score' => null, 'graded_at' => null]);
            SubmissionHistory::create([
                'submission_id' => $submission->id,
                'action' => 'score_cleared',
                'actor' => $student->full_name,
                'detail' => 'ส่งไฟล์/ลิงก์เพิ่มหลังตรวจแล้ว',
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
        $isLink = $file->type === 'link';

        // ลิงก์ไม่มีไฟล์บน Drive → ข้ามการลบ Drive (delete(string) รับ null ไม่ได้)
        if ($file->drive_file_id) {
            app(DriveStorage::class)->delete($file->drive_file_id);
        }
        $file->delete();

        SubmissionHistory::create([
            'submission_id' => $this->submission->id,
            'action' => $isLink ? 'link_deleted' : 'file_deleted',
            'actor' => $student?->full_name,
            'detail' => $fileName,
        ]);

        // ลบไฟล์/ลิงก์ → คะแนนงานนั้นหายทันที (ลบอันใดก็ตาม) — แม้ตรวจแล้วก็ลบได้
        if ($this->submission->score !== null) {
            $this->submission->update(['score' => null, 'graded_at' => null]);
            SubmissionHistory::create([
                'submission_id' => $this->submission->id,
                'action' => 'score_cleared',
                'actor' => $student?->full_name,
                'detail' => 'ลบ' . ($isLink ? 'ลิงก์ ' : 'ไฟล์ ') . $fileName,
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
