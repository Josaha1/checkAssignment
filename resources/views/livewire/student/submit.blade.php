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

                @if ($submission && $submission->score !== null)
                    <div class="flex items-center gap-3 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 px-4 py-3 text-sm text-amber-700 dark:text-amber-400">
                        <x-icon name="alert" class="w-5 h-5 shrink-0" /> งานนี้ถูกตรวจแล้ว ไม่สามารถแก้ไขลิงก์ได้
                    </div>
                @else
                    <form wire:submit="save" class="space-y-3">
                        <div>
                            <label class="label">ลิงก์งาน (Google Drive / ออนไลน์)</label>
                            <div class="relative">
                                <x-icon name="link" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                <input type="url" wire:model="link" placeholder="https://drive.google.com/..." class="input pl-9">
                            </div>
                            @error('link') <p class="mt-1 flex items-center gap-1.5 text-sm text-rose-600"><x-icon name="alert" class="w-4 h-4" /> {{ $message }}</p> @enderror
                            @if ($submission)
                                <p class="text-xs text-emerald-600 mt-1.5 flex items-center gap-1"><x-icon name="check" class="w-3 h-3" /> เคยส่งแล้ว — บันทึกใหม่เพื่อแก้ลิงก์เดิม</p>
                            @endif
                        </div>
                        <button type="submit" class="btn-primary w-full">
                            <x-icon name="send" class="w-4 h-4" /> {{ $submission ? 'อัปเดตลิงก์' : 'ส่งงาน' }}
                        </button>
                    </form>
                    <p class="text-xs text-slate-400 text-center flex items-center justify-center gap-1">
                        <x-icon name="alert" class="w-3 h-3" /> ตรวจสอบให้ลิงก์เปิดดูได้ (แชร์เป็น "ทุกคนที่มีลิงก์")
                    </p>
                @endif
            </div>
        </div>
    </x-student-shell>
</div>
