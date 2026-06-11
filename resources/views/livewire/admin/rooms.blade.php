<div>
    <x-admin-shell title="ห้องเรียน">
        <div class="grid lg:grid-cols-3 gap-6">
            <form wire:submit="save" class="card card-pad space-y-4 h-fit lg:sticky lg:top-24">
                <div class="flex items-center gap-2">
                    <x-icon name="school" class="w-5 h-5 text-brand-600" />
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100">{{ $editingId ? 'แก้ไขห้อง' : 'เพิ่มห้องใหม่' }}</h2>
                </div>
                <div>
                    <label class="label">ชื่อห้อง</label>
                    <input type="text" wire:model="name" placeholder="เช่น ปวส.1/1" class="input">
                    @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button class="btn-primary flex-1"><x-icon name="check" class="w-4 h-4" /> บันทึก</button>
                    @if ($editingId)
                        <button type="button" wire:click="cancel" class="btn-ghost">ยกเลิก</button>
                    @endif
                </div>
            </form>

            <div class="lg:col-span-2 card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr><th class="th">ห้อง</th><th class="th">นักศึกษา</th><th class="th text-right">จัดการ</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($rooms as $room)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="td font-medium text-slate-900 dark:text-white">{{ $room->name }}</td>
                                    <td class="td"><span class="badge-slate">{{ $room->students_count }} คน</span></td>
                                    <td class="td text-right">
                                        <button wire:click="edit({{ $room->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="w-4 h-4" /></button>
                                        <button wire:click="delete({{ $room->id }})" wire:confirm="ลบห้องนี้?" class="btn-danger btn-sm"><x-icon name="trash" class="w-4 h-4" /></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="td text-center text-slate-400 py-10">ยังไม่มีห้องเรียน</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-admin-shell>
</div>
