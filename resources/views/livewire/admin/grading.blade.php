<div>
    <x-admin-shell title="ตรวจ / ให้คะแนน">
        <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-slate-500 mb-1">รายวิชา</label>
                <select wire:model.live="subjectId" class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none">
                    <option value="">— เลือกวิชา —</option>
                    @foreach ($subjects as $s)
                        <option value="{{ $s->id }}">{{ $s->code }} · {{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">ห้อง</label>
                <select wire:model.live="roomId" class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none">
                    <option value="">ทุกห้อง</option>
                    @foreach ($rooms as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            @if ($subjectId)
                <div class="ml-auto flex gap-2">
                    <button wire:click="saveScores" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">
                        บันทึกคะแนน
                    </button>
                    <a href="{{ route('admin.export', ['subject' => $subjectId, 'room' => $roomId]) }}"
                       class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 rounded-lg">
                        Export CSV
                    </a>
                </div>
            @endif
        </div>

        @if ($subjectId)
            <div class="mt-4 bg-white rounded-xl border border-slate-200 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium sticky left-0 bg-slate-50 z-10">นักศึกษา</th>
                            @foreach ($assignments as $a)
                                <th class="px-4 py-3 font-medium text-center whitespace-nowrap">
                                    {{ $a->title }}
                                    <span class="block text-xs text-slate-400">เต็ม {{ rtrim(rtrim($a->max_score, '0'), '.') }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($students as $st)
                            <tr>
                                <td class="px-4 py-2 sticky left-0 bg-white z-10">
                                    <p class="font-medium text-slate-700">{{ $st->student_code }}</p>
                                    <p class="text-xs text-slate-400">{{ $st->full_name }} · {{ $st->room?->name }}</p>
                                </td>
                                @foreach ($assignments as $a)
                                    @php $sub = $matrix[$st->id][$a->id] ?? null; @endphp
                                    <td class="px-3 py-2 text-center">
                                        @if ($sub)
                                            <div class="flex flex-col items-center gap-1">
                                                <a href="{{ $sub->link }}" target="_blank" rel="noopener"
                                                   class="text-xs text-indigo-600 hover:underline">เปิดลิงก์ ↗</a>
                                                <input type="number" step="0.01" min="0" max="{{ $a->max_score }}"
                                                       wire:model="scores.{{ $sub->id }}"
                                                       class="w-20 rounded-lg border border-slate-300 px-2 py-1 text-center outline-none focus:ring-2 focus:ring-indigo-500">
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-300">ยังไม่ส่ง</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ $assignments->count() + 1 }}" class="px-5 py-6 text-center text-slate-400">ไม่มีนักศึกษาในเงื่อนไขนี้</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="mt-4 bg-white rounded-xl border border-slate-200 px-5 py-10 text-center text-slate-400">
                เลือกรายวิชาเพื่อเริ่มตรวจงาน
            </div>
        @endif
    </x-admin-shell>
</div>
