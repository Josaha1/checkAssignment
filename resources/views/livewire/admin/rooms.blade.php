<div>
    <x-admin-shell title="ห้องเรียน">
        <div class="grid md:grid-cols-3 gap-6">
            <form wire:submit="save" class="bg-white rounded-xl border border-slate-200 p-5 space-y-3 h-fit">
                <h2 class="font-semibold text-slate-800">{{ $editingId ? 'แก้ไขห้อง' : 'เพิ่มห้องใหม่' }}</h2>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">ชื่อห้อง</label>
                    <input type="text" wire:model="name" placeholder="เช่น ปวส.1/1"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('name') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">บันทึก</button>
                    @if ($editingId)
                        <button type="button" wire:click="cancel"
                                class="text-sm px-4 py-2 rounded-lg border border-slate-300">ยกเลิก</button>
                    @endif
                </div>
            </form>

            <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="text-left px-5 py-2 font-medium">ห้อง</th>
                            <th class="text-left px-5 py-2 font-medium">นักศึกษา</th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rooms as $room)
                            <tr>
                                <td class="px-5 py-2 font-medium text-slate-700">{{ $room->name }}</td>
                                <td class="px-5 py-2 text-slate-500">{{ $room->students_count }}</td>
                                <td class="px-5 py-2 text-right space-x-3">
                                    <button wire:click="edit({{ $room->id }})" class="text-indigo-600 hover:underline">แก้ไข</button>
                                    <button wire:click="delete({{ $room->id }})" wire:confirm="ลบห้องนี้?" class="text-rose-600 hover:underline">ลบ</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-6 text-center text-slate-400">ยังไม่มีห้องเรียน</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-admin-shell>
</div>
