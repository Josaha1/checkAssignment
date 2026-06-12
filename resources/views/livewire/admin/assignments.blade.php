<div>
    <x-admin-shell title="ชิ้นงาน · {{ $subject->code }}">
        <a href="{{ route('admin.subjects') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-600 mb-4">
            <x-icon name="arrow-left" class="w-4 h-4" /> กลับรายวิชา
        </a>
        <p class="text-slate-500 dark:text-slate-400 -mt-2 mb-4 text-sm">{{ $subject->name }}</p>

        <div class="grid lg:grid-cols-3 gap-6">
            <form wire:submit="save" class="card card-pad space-y-4 h-fit lg:sticky lg:top-24">
                <div class="flex items-center gap-2">
                    <x-icon name="clipboard" class="w-5 h-5 text-brand-600" />
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100">{{ $editingId ? 'แก้ไขงาน' : 'เพิ่มงานใหม่' }}</h2>
                </div>
                <div>
                    <label class="label">ชื่องาน</label>
                    <input type="text" wire:model="title" class="input">
                    @error('title') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">โจทย์ / รายละเอียด</label>
                    <textarea wire:model="description" rows="3" class="textarea"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">คะแนนเต็ม</label>
                        <input type="number" step="0.01" wire:model="max_score" class="input">
                        @error('max_score') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">กำหนดส่ง</label>
                        <input type="date" wire:model="due_date" class="input">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="btn-primary flex-1"><x-icon name="check" class="w-4 h-4" /> บันทึก</button>
                    @if ($editingId)
                        <button type="button" wire:click="cancel" class="btn-ghost">ยกเลิก</button>
                    @endif
                </div>
            </form>

            <div class="lg:col-span-2 space-y-3">
                @forelse ($assignments as $a)
                    <div class="card card-pad flex items-start gap-4">
                        <span class="grid place-items-center w-10 h-10 rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 shrink-0">
                            <x-icon name="clipboard" class="w-5 h-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $a->title }}</p>
                            <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                <span class="badge-brand">เต็ม {{ rtrim(rtrim($a->max_score, '0'), '.') }}</span>
                                <span class="badge-green">ส่งแล้ว {{ $a->submissions_count }}</span>
                                @if ($a->due_date)
                                    <span class="badge-slate"><x-icon name="calendar" class="w-3 h-3" /> {{ $a->due_date->format('d/m/Y') }}</span>
                                @endif
                            </div>
                            @if ($a->description)
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 line-clamp-2">{{ $a->description }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col gap-1.5 shrink-0">
                            <button wire:click="edit({{ $a->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="w-4 h-4" /></button>
                            <button wire:click="prepareFolders({{ $a->id }})" class="btn-ghost btn-sm" title="เตรียมโฟลเดอร์บน Drive"><x-icon name="link" class="w-4 h-4" /></button>
                            <button wire:click="delete({{ $a->id }})" wire:confirm="ลบงานนี้?" class="btn-danger btn-sm"><x-icon name="trash" class="w-4 h-4" /></button>
                        </div>
                    </div>
                @empty
                    <div class="card card-pad text-center text-slate-400 py-12">
                        <x-icon name="clipboard" class="w-10 h-10 mx-auto mb-2 opacity-40" />
                        ยังไม่มีชิ้นงานในวิชานี้
                    </div>
                @endforelse
            </div>
        </div>
    </x-admin-shell>
</div>
