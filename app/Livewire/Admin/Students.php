<?php

namespace App\Livewire\Admin;

use App\Models\Room;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Students extends Component
{
    use WithFileUploads, WithPagination;

    public string $student_code = '';
    public string $full_name = '';
    public string $birthdate = '';
    public ?int $room_id = null;
    public ?int $editingId = null;

    public string $search = '';
    public $csv; // ไฟล์อัปโหลด
    public array $importResult = [];

    public function save(): void
    {
        $data = $this->validate([
            'student_code' => ['required', 'string', 'max:50',
                'unique:students,student_code' . ($this->editingId ? ',' . $this->editingId : '')],
            'full_name' => ['required', 'string', 'max:150'],
            'birthdate' => ['required', 'date'],
            'room_id' => ['nullable', 'exists:rooms,id'],
        ], attributes: [
            'student_code' => 'รหัสนักศึกษา', 'full_name' => 'ชื่อ-สกุล', 'birthdate' => 'วันเกิด',
        ]);

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
        $this->birthdate = $s->birthdate->format('Y-m-d');
        $this->room_id = $s->room_id;
    }

    public function cancel(): void
    {
        $this->reset('student_code', 'full_name', 'birthdate', 'room_id', 'editingId');
        $this->resetErrorBag();
    }

    public function delete(int $id): void
    {
        Student::findOrFail($id)->delete();
        session()->flash('ok', 'ลบนักศึกษาแล้ว');
    }

    // นำเข้า CSV: header = student_code,full_name,birthdate,room
    public function import(): void
    {
        $this->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:4096']]);

        $handle = fopen($this->csv->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => strtolower(trim($h ?? '')), $header ?: []);
        $idx = array_flip($header);

        $created = 0; $updated = 0; $failed = 0;
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue; // ข้ามบรรทัดว่าง
                }
                $code = trim($row[$idx['student_code']] ?? '');
                $name = trim($row[$idx['full_name']] ?? '');
                $bd = $this->parseDate(trim($row[$idx['birthdate']] ?? ''));
                $roomName = isset($idx['room']) ? trim($row[$idx['room']] ?? '') : '';

                if ($code === '' || $name === '' || ! $bd) { $failed++; continue; }

                $roomId = $roomName !== ''
                    ? Room::firstOrCreate(['name' => $roomName])->id
                    : null;

                $existing = Student::where('student_code', $code)->exists();
                Student::updateOrCreate(
                    ['student_code' => $code],
                    ['full_name' => $name, 'birthdate' => $bd, 'room_id' => $roomId],
                );
                $existing ? $updated++ : $created++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            $this->addError('csv', 'นำเข้าไม่สำเร็จ: ' . $e->getMessage());
            return;
        }
        fclose($handle);

        $this->reset('csv');
        $this->importResult = ['created' => $created, 'updated' => $updated, 'failed' => $failed];
        session()->flash('ok', "นำเข้าสำเร็จ: เพิ่ม {$created} แก้ไข {$updated} ผิดพลาด {$failed}");
    }

    private function parseDate(string $value): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value)->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }
        return null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $students = Student::with('room')
            ->when($this->search, fn ($q) => $q
                ->where('student_code', 'like', "%{$this->search}%")
                ->orWhere('full_name', 'like', "%{$this->search}%"))
            ->orderBy('student_code')
            ->paginate(20);

        return view('livewire.admin.students', [
            'students' => $students,
            'rooms' => Room::orderBy('name')->get(),
        ]);
    }
}
