<div>
    <x-admin-shell title="นักศึกษา">
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="space-y-6 lg:sticky lg:top-24 h-fit">
                <form wire:submit="save" class="card card-pad space-y-4">
                    <div class="flex items-center gap-2">
                        <x-icon name="users" class="w-5 h-5 text-brand-600" />
                        <h2 class="font-semibold text-slate-800 dark:text-slate-100">{{ $editingId ? 'แก้ไขนักศึกษา' : 'เพิ่มนักศึกษา' }}</h2>
                    </div>
                    <div>
                        <label class="label">รหัสนักศึกษา</label>
                        <input type="text" wire:model="student_code" class="input">
                        @error('student_code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">ชื่อ-สกุล</label>
                        <input type="text" wire:model="full_name" class="input">
                        @error('full_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">วันเกิด <span class="text-slate-400 font-normal">(ใช้เป็นรหัสผ่าน)</span></label>
                        <input type="date" wire:model="birthdate" class="input">
                        @error('birthdate') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">ห้อง</label>
                        <select wire:model="room_id" class="select">
                            <option value="">— เลือกห้อง —</option>
                            @foreach ($rooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button class="btn-primary flex-1"><x-icon name="check" class="w-4 h-4" /> บันทึก</button>
                        @if ($editingId)<button type="button" wire:click="cancel" class="btn-ghost">ยกเลิก</button>@endif
                    </div>
                </form>

                <form wire:submit="import" class="card card-pad space-y-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="upload" class="w-5 h-5 text-emerald-600" />
                        <h2 class="font-semibold text-slate-800 dark:text-slate-100">นำเข้าจาก CSV</h2>
                    </div>
                    <p class="text-xs text-slate-500">คอลัมน์: <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800">student_code,full_name,birthdate,room</code></p>
                    <input type="file" wire:model="csv" accept=".csv,.txt"
                           class="block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-medium dark:file:bg-emerald-500/15 dark:file:text-emerald-400">
                    @error('csv') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    <button class="btn-success w-full" wire:loading.attr="disabled" wire:target="import,csv">
                        <span wire:loading.remove wire:target="import"><x-icon name="upload" class="w-4 h-4" /> นำเข้า</span>
                        <span wire:loading wire:target="import">กำลังนำเข้า...</span>
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 card overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="relative">
                        <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="ค้นหารหัส / ชื่อ" class="input pl-9">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr><th class="th">รหัส</th><th class="th">ชื่อ-สกุล</th><th class="th">ห้อง</th><th class="th text-right">จัดการ</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($students as $s)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="td font-medium text-slate-900 dark:text-white">{{ $s->student_code }}</td>
                                    <td class="td">{{ $s->full_name }}</td>
                                    <td class="td"><span class="badge-slate">{{ $s->room?->name ?? '—' }}</span></td>
                                    <td class="td text-right whitespace-nowrap">
                                        <button wire:click="edit({{ $s->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="w-4 h-4" /></button>
                                        <button wire:click="delete({{ $s->id }})" wire:confirm="ลบนักศึกษาคนนี้?" class="btn-danger btn-sm"><x-icon name="trash" class="w-4 h-4" /></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="td text-center text-slate-400 py-10">ไม่พบนักศึกษา</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $students->links() }}</div>
            </div>
        </div>
    </x-admin-shell>
</div>
