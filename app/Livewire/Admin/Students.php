<?php

namespace App\Livewire\Admin;

use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Students extends Component
{
    use WithFileUploads, WithPagination;

    public string $student_code = '';
    public string $full_name = '';
    public string $email = '';
    public string $password = '';
    public string $faculty = '';
    public string $major = '';
    public string $program = '';
    public string $study_group = '';
    public ?int $editingId = null;

    public string $search = '';
    public $file; // ไฟล์ xlsx อัปโหลด
    public array $importResult = [];

    public function save(): void
    {
        $data = $this->validate([
            'student_code' => ['required', 'string', 'max:50',
                'unique:students,student_code' . ($this->editingId ? ',' . $this->editingId : '')],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150',
                'unique:students,email' . ($this->editingId ? ',' . $this->editingId : '')],
            'password' => [$this->editingId ? 'nullable' : 'nullable', 'string', 'min:6'],
            'faculty' => ['nullable', 'string', 'max:150'],
            'major' => ['nullable', 'string', 'max:150'],
            'program' => ['nullable', 'string', 'max:150'],
            'study_group' => ['nullable', 'string', 'max:50'],
        ], attributes: [
            'student_code' => 'รหัสนักศึกษา', 'full_name' => 'ชื่อ-นามสกุล', 'email' => 'อีเมล',
        ]);

        // รหัสผ่านว่าง: ตอนเพิ่มใหม่ตั้งต้น = รหัสนักศึกษา, ตอนแก้ไขคงรหัสเดิม
        if (($data['password'] ?? '') === '') {
            unset($data['password']);
            if (! $this->editingId) {
                $data['password'] = $this->student_code;
            }
        }

        Student::updateOrCreate(['id' => $this->editingId], $data);
        $this->cancel();
        session()->flash('ok', 'บันทึกนักศึกษาแล้ว');
    }

    public function edit(int $id): void
    {
        $s = Student::findOrFail($id);
        $this->editingId = $s->id;
        $this->student_code = $s->student_code;
        $this->full_name = $s->full_name;
        $this->email = $s->email;
        $this->password = '';
        $this->faculty = $s->faculty ?? '';
        $this->major = $s->major ?? '';
        $this->program = $s->program ?? '';
        $this->study_group = $s->study_group ?? '';
    }

    public function cancel(): void
    {
        $this->reset('student_code', 'full_name', 'email', 'password',
            'faculty', 'major', 'program', 'study_group', 'editingId');
        $this->resetErrorBag();
    }

    public function delete(int $id): void
    {
        Student::findOrFail($id)->delete();
        session()->flash('ok', 'ลบนักศึกษาแล้ว');
    }

    // นำเข้าใบลงทะเบียน .xlsx (1 ไฟล์ = 1 วิชา) → สร้างวิชาจาก header + เพิ่มนักศึกษา + enroll
    public function import(): void
    {
        // ใช้ extensions (เช็คนามสกุล) — xlsx เป็น zip การ guess จาก content มักได้ zip ทำให้ mimes ปัด
        $this->validate(['file' => ['required', 'file', 'extensions:xlsx,xls', 'max:8192']]);

        // อ่านเฉพาะข้อมูล ข้าม styles — กัน RAM พุ่งจน worker ตาย (free tier 256M)
        $reader = IOFactory::createReaderForFile($this->file->getRealPath());
        $reader->setReadDataOnly(true);
        $rows = $reader->load($this->file->getRealPath())->getActiveSheet()->toArray();

        $subject = $this->resolveSubject($rows);
        if (! $subject) {
            $this->addError('file', 'หาชื่อวิชาในไฟล์ไม่พบ (บรรทัด "ชื่อวิชา :")');
            return;
        }

        $map = $this->resolveHeader($rows);
        if ($map === null) {
            $this->addError('file', 'หาหัวตาราง "รหัสนักศึกษา" ไม่พบ');
            return;
        }
        [$headerIdx, $col] = $map;

        // parse ทุกแถวก่อน (กันรหัสซ้ำในไฟล์ด้วย key) — ลด query โดยทำงานเป็นชุด
        $parsed = [];
        $failed = 0;
        foreach (array_slice($rows, $headerIdx + 1) as $row) {
            $code = trim((string) ($row[$col['code']] ?? ''));
            $name = trim((string) ($row[$col['name']] ?? ''));
            if ($code === '' || ! ctype_digit($code)) { continue; } // ข้ามแถวว่าง/ไม่ใช่ข้อมูล
            if ($name === '') { $failed++; continue; }

            $email = trim((string) ($row[$col['email']] ?? ''));
            $parsed[$code] = [
                'student_code' => $code,
                'full_name' => $name,
                'email' => $email !== '' ? $email : $code . '@spulive.net',
                'faculty' => $this->val($row, $col, 'faculty'),
                'major' => $this->val($row, $col, 'major'),
                'program' => $this->val($row, $col, 'program'),
                'study_group' => $this->val($row, $col, 'group'),
            ];
        }

        if (! $parsed) {
            $this->addError('file', 'ไม่พบข้อมูลนักศึกษาในไฟล์');
            return;
        }

        $codes = array_keys($parsed);
        $now = now();
        $created = 0; $updated = 0;

        try {
            DB::transaction(function () use ($parsed, $codes, $subject, $now, &$created, &$updated) {
                $existing = array_flip(Student::whereIn('student_code', $codes)->pluck('student_code')->all());

                $inserts = []; $upserts = [];
                foreach ($parsed as $code => $attr) {
                    if (isset($existing[$code])) {
                        // ต้องใส่ password ใน candidate tuple ด้วย — Postgres ตรวจ NOT NULL ก่อน resolve ON CONFLICT (ไม่ใส่ = 23502)
                        $upserts[] = $attr + ['password' => Hash::make($code, ['rounds' => 10]), 'updated_at' => $now];
                        $updated++;
                    } else {
                        // bulk insert ข้าม cast → hash เอง (rounds 10: default pw = รหัสนศ. ต้องเปลี่ยนทีหลังอยู่แล้ว)
                        $inserts[] = $attr + ['password' => Hash::make($code, ['rounds' => 10]), 'created_at' => $now, 'updated_at' => $now];
                        $created++;
                    }
                }

                foreach (array_chunk($inserts, 100) as $batch) {
                    Student::insert($batch);
                }
                foreach (array_chunk($upserts, 100) as $batch) {
                    Student::upsert($batch, ['student_code'],
                        ['full_name', 'email', 'faculty', 'major', 'program', 'study_group', 'password', 'updated_at']);
                }

                // ลงทะเบียนทั้งหมดครั้งเดียว (ไม่ใช่ราย row)
                $ids = Student::whereIn('student_code', $codes)->pluck('id')->all();
                $subject->students()->syncWithoutDetaching($ids);
            });
        } catch (\Throwable $e) {
            $this->addError('file', 'นำเข้าไม่สำเร็จ: ' . $e->getMessage());
            return;
        }

        $this->reset('file');
        $this->importResult = ['subject' => $subject->code, 'created' => $created, 'updated' => $updated, 'failed' => $failed];
        session()->flash('ok', "นำเข้าวิชา {$subject->code}: เพิ่ม {$created} แก้ไข {$updated} ผิดพลาด {$failed}");
    }

    // หาวิชาจากบรรทัด "ชื่อวิชา : <code> - <name> ..."
    private function resolveSubject(array $rows): ?Subject
    {
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $text = trim((string) $cell);
                if (! str_contains($text, 'ชื่อวิชา')) { continue; }
                $text = preg_replace('/^.*ชื่อวิชา\s*:?\s*/u', '', $text);
                $parts = preg_split('/\s+-\s+/u', $text, 2);
                $code = trim($parts[0]);
                $name = trim($parts[1] ?? $code);
                if ($code === '') { return null; }
                return Subject::firstOrCreate(['code' => $code], ['name' => $name]);
            }
        }
        return null;
    }

    // หาแถว header + แม็พชื่อคอลัมน์เป็น index
    private function resolveHeader(array $rows): ?array
    {
        foreach ($rows as $i => $row) {
            $cells = array_map(fn ($c) => trim((string) $c), $row);
            if (! in_array('รหัสนักศึกษา', $cells, true)) { continue; }

            $find = function (array $needles) use ($cells) {
                foreach ($cells as $idx => $label) {
                    foreach ($needles as $n) {
                        if ($label !== '' && str_contains($label, $n)) { return $idx; }
                    }
                }
                return null;
            };

            return [$i, [
                'code' => $find(['รหัสนักศึกษา']),
                'name' => $find(['ชื่อ']),
                'faculty' => $find(['คณะ']),
                'major' => $find(['สาขา']),
                'program' => $find(['รอบ', 'หลักสูตร']),
                'group' => $find(['กลุ่ม']),
                'email' => $find(['email', 'อีเมล', 'mail']),
            ]];
        }
        return null;
    }

    // ดึงค่าจาก row ตาม key คอลัมน์ — "-"/ว่าง → null
    private function val(array $row, array $col, string $key): ?string
    {
        $i = $col[$key] ?? null;
        if ($i === null) { return null; }
        $v = trim((string) ($row[$i] ?? ''));
        return ($v === '' || $v === '-') ? null : $v;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $students = Student::query()
            ->when($this->search, fn ($q) => $q
                ->where('student_code', 'like', "%{$this->search}%")
                ->orWhere('full_name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->orderBy('student_code')
            ->paginate(20);

        return view('livewire.admin.students', ['students' => $students]);
    }
}
