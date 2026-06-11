<div class="min-h-screen bg-slate-50">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-2xl mx-auto px-4 py-4 flex items-center gap-3">
            <a href="{{ route('student.dashboard') }}" class="text-slate-400 hover:text-slate-700">←</a>
            <h1 class="text-lg font-semibold text-slate-900">ส่งงาน</h1>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-5">
            <div>
                <p class="text-xs text-slate-400">{{ $assignment->subject->code }} · {{ $assignment->subject->name }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ $assignment->title }}</h2>
                @if ($assignment->due_date)
                    <p class="text-sm text-slate-500 mt-1">กำหนดส่ง {{ $assignment->due_date->format('d/m/Y') }}</p>
                @endif
            </div>

            @if ($assignment->description)
                <div class="rounded-lg bg-slate-50 border border-slate-100 p-4 text-sm text-slate-600 whitespace-pre-line">{{ $assignment->description }}</div>
            @endif

            @if ($submission && $submission->score !== null)
                <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700">
                    งานนี้ถูกตรวจแล้ว ไม่สามารถแก้ไขลิงก์ได้
                </div>
            @else
                <form wire:submit="save" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ลิงก์งาน (Google Drive / ออนไลน์)</label>
                        <input type="url" wire:model="link" placeholder="https://drive.google.com/..."
                               class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        @error('link') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                        @if ($submission)
                            <p class="text-xs text-emerald-600 mt-1">เคยส่งแล้ว — บันทึกใหม่เพื่อแก้ลิงก์เดิม</p>
                        @endif
                    </div>
                    <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 rounded-lg transition">
                        {{ $submission ? 'อัปเดตลิงก์' : 'ส่งงาน' }}
                    </button>
                </form>
            @endif
        </div>
    </main>
</div>
