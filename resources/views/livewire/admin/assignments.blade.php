<div>
    <x-admin-shell title="ชิ้นงาน — {{ $subject->code }} {{ $subject->name }}">
        <a href="{{ route('admin.subjects') }}" class="text-sm text-slate-500 hover:text-slate-700">← กลับรายวิชา</a>

        <div class="grid md:grid-cols-3 gap-6 mt-3">
            <form wire:submit="save" class="bg-white rounded-xl border border-slate-200 p-5 space-y-3 h-fit">
                <h2 class="font-semibold text-slate-800">{{ $editingId ? 'แก้ไขงาน' : 'เพิ่มงานใหม่' }}</h2>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">ชื่องาน</label>
                    <input type="text" wire:model="title"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('title') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">โจทย์ / รายละเอียด</label>
                    <textarea wire:model="description" rows="3"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">คะแนนเต็ม</label>
                        <input type="number" step="0.01" wire:model="max_score"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none">
                        @error('max_score') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">กำหนดส่ง</label>
                        <input type="date" wire:model="due_date"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none">
                    </div>
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
                            <th class="text-left px-5 py-2 font-medium">งาน</th>
                            <th class="text-left px-5 py-2 font-medium">เต็ม</th>
                            <th class="text-left px-5 py-2 font-medium">ส่งแล้ว</th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($assignments as $a)
                            <tr>
                                <td class="px-5 py-2">
                                    <p class="font-medium text-slate-700">{{ $a->title }}</p>
                                    <p class="text-slate-400 text-xs">@if ($a->due_date) ส่ง {{ $a->due_date->format('d/m/Y') }} @endif</p>
                                </td>
                                <td class="px-5 py-2 text-slate-500">{{ rtrim(rtrim($a->max_score, '0'), '.') }}</td>
                                <td class="px-5 py-2 text-slate-500">{{ $a->submissions_count }}</td>
                                <td class="px-5 py-2 text-right text-xs space-x-2">
                                    <button wire:click="edit({{ $a->id }})" class="text-indigo-600 hover:underline">แก้ไข</button>
                                    <button wire:click="delete({{ $a->id }})" wire:confirm="ลบงานนี้?" class="text-rose-600 hover:underline">ลบ</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400">ยังไม่มีชิ้นงาน</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-admin-shell>
</div>
