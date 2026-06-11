<div>
    <x-admin-shell title="นักศึกษา">
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="space-y-6">
                <form wire:submit="save" class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                    <h2 class="font-semibold text-slate-800">{{ $editingId ? 'แก้ไขนักศึกษา' : 'เพิ่มนักศึกษา' }}</h2>
                    <input type="text" wire:model="student_code" placeholder="รหัสนักศึกษา"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('student_code') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    <input type="text" wire:model="full_name" placeholder="ชื่อ-สกุล"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('full_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">วันเกิด (ใช้เป็นรหัสผ่าน)</label>
                        <input type="date" wire:model="birthdate"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('birthdate') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <select wire:model="room_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none">
                        <option value="">— เลือกห้อง —</option>
                        @foreach ($rooms as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">บันทึก</button>
                        @if ($editingId)
                            <button type="button" wire:click="cancel" class="text-sm px-4 py-2 rounded-lg border border-slate-300">ยกเลิก</button>
                        @endif
                    </div>
                </form>

                <form wire:submit="import" class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                    <h2 class="font-semibold text-slate-800">นำเข้าจาก CSV</h2>
                    <p class="text-xs text-slate-500">หัวคอลัมน์: <code class="bg-slate-100 px-1 rounded">student_code,full_name,birthdate,room</code><br>วันเกิดรูปแบบ Y-m-d หรือ d/m/Y</p>
                    <input type="file" wire:model="csv" accept=".csv,.txt"
                           class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700">
                    @error('csv') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    <button class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 rounded-lg" wire:loading.attr="disabled" wire:target="import,csv">
                        <span wire:loading.remove wire:target="import">นำเข้า</span>
                        <span wire:loading wire:target="import">กำลังนำเข้า...</span>
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="ค้นหารหัส / ชื่อ"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="text-left px-5 py-2 font-medium">รหัส</th>
                            <th class="text-left px-5 py-2 font-medium">ชื่อ-สกุล</th>
                            <th class="text-left px-5 py-2 font-medium">ห้อง</th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($students as $s)
                            <tr>
                                <td class="px-5 py-2 font-medium text-slate-700">{{ $s->student_code }}</td>
                                <td class="px-5 py-2 text-slate-600">{{ $s->full_name }}</td>
                                <td class="px-5 py-2 text-slate-500">{{ $s->room?->name ?? '—' }}</td>
                                <td class="px-5 py-2 text-right text-xs space-x-2">
                                    <button wire:click="edit({{ $s->id }})" class="text-indigo-600 hover:underline">แก้ไข</button>
                                    <button wire:click="delete({{ $s->id }})" wire:confirm="ลบนักศึกษาคนนี้?" class="text-rose-600 hover:underline">ลบ</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400">ไม่พบนักศึกษา</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-3">{{ $students->links() }}</div>
            </div>
        </div>
    </x-admin-shell>
</div>
