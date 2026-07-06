<div>
    <x-student-shell>
        <a href="{{ route('student.dashboard') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-600 mb-4">
            <x-icon name="arrow-left" class="w-4 h-4" /> กลับหน้าหลัก
        </a>

        <div class="max-w-2xl mx-auto">
            <div class="card card-pad space-y-5">
                <div>
                    <span class="badge-brand">{{ $assignment->subject->code }} · {{ $assignment->subject->name }}</span>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-2">{{ $assignment->title }}</h2>
                    @if ($assignment->due_date)
                        <p class="text-sm text-slate-500 mt-1 flex items-center gap-1"><x-icon name="calendar" class="w-4 h-4" /> กำหนดส่ง {{ $assignment->due_date->format('d/m/Y') }}</p>
                    @endif
                </div>

                @if ($assignment->description)
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700 p-4 text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">{{ $assignment->description }}</div>
                @endif

                {{-- ไฟล์/ลิงก์ที่ส่งแล้ว --}}
                @if ($submission && $submission->files->isNotEmpty())
                    <div>
                        <p class="label">ไฟล์/ลิงก์ที่ส่งแล้ว ({{ $submission->files->count() }})</p>
                        <div class="space-y-2">
                            @foreach ($submission->files as $f)
                                <div class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2">
                                    <x-icon :name="$f->type === 'link' ? 'link' : 'clipboard'" class="w-4 h-4 text-slate-400 shrink-0" />
                                    <a href="{{ $f->url }}" target="_blank" rel="noopener" class="flex-1 text-sm text-slate-700 dark:text-slate-200 truncate hover:text-brand-600">{{ $f->name }}</a>
                                    {{-- ลบได้เสมอ — ถ้าตรวจแล้ว เตือนว่าคะแนนจะหาย --}}
                                    <button wire:click="removeFile({{ $f->id }})"
                                            wire:confirm="{{ $submission->score !== null ? 'ลบ' . ($f->type === 'link' ? 'ลิงก์' : 'ไฟล์') . 'นี้จะทำให้คะแนนที่ได้หายไป และต้องส่งงานใหม่ — ยืนยันลบ?' : ('ลบ' . ($f->type === 'link' ? 'ลิงก์' : 'ไฟล์') . 'นี้?') }}"
                                            class="text-rose-500 hover:text-rose-600 shrink-0"><x-icon name="trash" class="w-4 h-4" /></button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($submission && $submission->score !== null)
                    <div class="flex items-center gap-3 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 px-4 py-3 text-sm text-amber-700 dark:text-amber-400">
                        <x-icon name="alert" class="w-5 h-5 shrink-0" /> งานนี้ถูกตรวจแล้ว — ถ้าส่งไฟล์เพิ่ม คะแนนจะถูกล้างและต้องให้อาจารย์ตรวจใหม่
                    </div>
                @endif

                <form wire:submit="save" class="space-y-3">
                        <div>
                            <label class="label">{{ $submission?->files->isNotEmpty() ? 'แนบไฟล์เพิ่ม' : 'แนบไฟล์งาน' }}</label>
                            <x-file-dropzone model="uploads" multiple accent="brand" :files="$uploads"
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.zip"
                                hint="รองรับ pdf, word, ppt, excel, รูป, zip · ไม่เกิน 40MB/ไฟล์ · สูงสุด 10 ไฟล์" />
                            @error('uploads') <p class="mt-1 flex items-center gap-1.5 text-sm text-rose-600"><x-icon name="alert" class="w-4 h-4" /> {{ $message }}</p> @enderror
                            @error('uploads.*') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- ส่งเป็นลิงก์ได้ด้วย (Google Drive, YouTube ฯลฯ) — 1 บรรทัด 1 ลิงก์ --}}
                        <div>
                            <label class="label flex items-center gap-1.5"><x-icon name="link" class="w-4 h-4 text-slate-400" /> ลิงก์งาน (ถ้ามี)</label>
                            <textarea wire:model="links" rows="2" class="input resize-y"
                                placeholder="วางลิงก์ที่นี่ — 1 ลิงก์ต่อบรรทัด"></textarea>
                            <p class="mt-1 text-xs text-slate-400">ใส่ได้หลายลิงก์ บรรทัดละ 1 ลิงก์ · ต้องขึ้นต้น http:// หรือ https://</p>
                            @error('links') <p class="mt-1 flex items-center gap-1.5 text-sm text-rose-600"><x-icon name="alert" class="w-4 h-4" /> {{ $message }}</p> @enderror
                        </div>

                        <div wire:loading wire:target="uploads" class="text-sm text-slate-400">กำลังเตรียมไฟล์...</div>

                        {{-- งานที่ตรวจแล้ว: ยืนยันต้องผูกกับ wire:click ไม่ใช่ form-submit — wire:confirm บน <form wire:submit> ไม่ fire บน Livewire build ที่ prod รัน (เด้งเฉพาะ wire:click) --}}
                        <button type="{{ $submission && $submission->score !== null ? 'button' : 'submit' }}"
                            @if ($submission && $submission->score !== null) wire:click="save" wire:confirm="ส่งไฟล์เพิ่มจะทำให้คะแนนที่ได้ ({{ $submission->score }}) หาย และต้องให้อาจารย์ตรวจใหม่ — ยืนยันส่ง?" @endif
                            class="btn-primary w-full" wire:loading.attr="disabled" wire:target="save,uploads">
                            <span wire:loading.remove wire:target="save"><x-icon name="upload" class="w-4 h-4" /> ส่งงาน</span>
                            <span wire:loading wire:target="save">กำลังอัปโหลดขึ้น Drive...</span>
                        </button>
                    </form>
            </div>
        </div>
    </x-student-shell>
</div>
