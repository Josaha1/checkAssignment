<div>
    <x-admin-shell title="รายวิชา">
        <div class="grid md:grid-cols-3 gap-6">
            <form wire:submit="save" class="bg-white rounded-xl border border-slate-200 p-5 space-y-3 h-fit">
                <h2 class="font-semibold text-slate-800">{{ $editingId ? 'แก้ไขวิชา' : 'เพิ่มวิชาใหม่' }}</h2>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">รหัสวิชา</label>
                    <input type="text" wire:model="code" placeholder="เช่น 30901-2001"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('code') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">ชื่อวิชา</label>
                    <input type="text" wire:model="name"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('name') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">บันทึก</button>
                    @if ($editingId)
                        <button type="button" wire:click="cancel" class="text-sm px-4 py-2 rounded-lg border border-slate-300">ยกเลิก</button>
                    @endif
                </div>
            </form>

            <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="text-left px-5 py-2 font-medium">วิชา</th>
                            <th class="text-left px-5 py-2 font-medium">งาน</th>
                            <th class="text-left px-5 py-2 font-medium">นักศึกษา</th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($subjects as $s)
                            <tr>
                                <td class="px-5 py-2">
                                    <p class="font-medium text-slate-700">{{ $s->code }}</p>
                                    <p class="text-slate-400 text-xs">{{ $s->name }}</p>
                                </td>
                                <td class="px-5 py-2 text-slate-500">{{ $s->assignments_count }}</td>
                                <td class="px-5 py-2 text-slate-500">{{ $s->students_count }}</td>
                                <td class="px-5 py-2 text-right text-xs space-x-2 whitespace-nowrap">
                                    <a href="{{ route('admin.assignments', $s) }}" class="text-violet-600 hover:underline">งาน</a>
                                    <a href="{{ route('admin.enroll', $s) }}" class="text-sky-600 hover:underline">ลงทะเบียน</a>
                                    <button wire:click="edit({{ $s->id }})" class="text-indigo-600 hover:underline">แก้ไข</button>
                                    <button wire:click="delete({{ $s->id }})" wire:confirm="ลบวิชานี้และงานทั้งหมด?" class="text-rose-600 hover:underline">ลบ</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400">ยังไม่มีรายวิชา</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-admin-shell>
</div>
