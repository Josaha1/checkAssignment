<div>
    <x-admin-shell title="รายวิชา">
        <div class="grid lg:grid-cols-3 gap-6">
            <form wire:submit="save" class="card card-pad space-y-4 h-fit lg:sticky lg:top-24">
                <div class="flex items-center gap-2">
                    <x-icon name="book" class="w-5 h-5 text-brand-600" />
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100">{{ $editingId ? 'แก้ไขวิชา' : 'เพิ่มวิชาใหม่' }}</h2>
                </div>
                <div>
                    <label class="label">รหัสวิชา</label>
                    <input type="text" wire:model="code" placeholder="เช่น 30901-2001" class="input">
                    @error('code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">ชื่อวิชา</label>
                    <input type="text" wire:model="name" class="input">
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
                            <tr><th class="th">วิชา</th><th class="th">งาน</th><th class="th">นักศึกษา</th><th class="th text-right">จัดการ</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($subjects as $s)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="td">
                                        <p class="font-medium text-slate-900 dark:text-white">{{ $s->code }}</p>
                                        <p class="text-xs text-slate-400">{{ $s->name }}</p>
                                    </td>
                                    <td class="td"><span class="badge-brand">{{ $s->assignments_count }}</span></td>
                                    <td class="td"><span class="badge-slate">{{ $s->students_count }}</span></td>
                                    <td class="td text-right whitespace-nowrap">
                                        <a href="{{ route('admin.assignments', $s) }}" wire:navigate class="btn-ghost btn-sm"><x-icon name="clipboard" class="w-4 h-4" /> งาน</a>
                                        <a href="{{ route('admin.enroll', $s) }}" wire:navigate class="btn-ghost btn-sm"><x-icon name="users" class="w-4 h-4" /> ลงทะเบียน</a>
                                        <button wire:click="edit({{ $s->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="w-4 h-4" /></button>
                                        <button wire:click="delete({{ $s->id }})" wire:confirm="ลบวิชานี้และงานทั้งหมด?" class="btn-danger btn-sm"><x-icon name="trash" class="w-4 h-4" /></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="td text-center text-slate-400 py-10">ยังไม่มีรายวิชา</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-admin-shell>
</div>
