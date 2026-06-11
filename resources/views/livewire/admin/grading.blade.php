<div>
    <x-admin-shell title="ตรวจ / ให้คะแนน">
        <div class="card card-pad flex flex-wrap items-end gap-3">
            <div>
                <label class="label">รายวิชา</label>
                <select wire:model.live="subjectId" class="select !w-auto min-w-[220px]">
                    <option value="">— เลือกวิชา —</option>
                    @foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->code }} · {{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label">ห้อง</label>
                <select wire:model.live="roomId" class="select !w-auto">
                    <option value="">ทุกห้อง</option>
                    @foreach ($rooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
                </select>
            </div>
            @if ($subjectId)
                <div class="ml-auto flex gap-2">
                    <button wire:click="saveScores" class="btn-primary"><x-icon name="check" class="w-4 h-4" /> บันทึกคะแนน</button>
                    <a href="{{ route('admin.export', ['subject' => $subjectId, 'room' => $roomId]) }}" class="btn-success"><x-icon name="download" class="w-4 h-4" /> Export CSV</a>
                </div>
            @endif
        </div>

        @if ($subjectId)
            <div class="card mt-6 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <th class="th sticky left-0 bg-slate-50 dark:bg-slate-800 z-10">นักศึกษา</th>
                                @foreach ($assignments as $a)
                                    <th class="th text-center whitespace-nowrap">
                                        {{ $a->title }}
                                        <span class="block text-[10px] font-normal text-slate-400">เต็ม {{ rtrim(rtrim($a->max_score, '0'), '.') }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($students as $st)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="td sticky left-0 bg-white dark:bg-slate-900 z-10">
                                        <p class="font-medium text-slate-900 dark:text-white">{{ $st->student_code }}</p>
                                        <p class="text-xs text-slate-400">{{ $st->full_name }} · {{ $st->room?->name }}</p>
                                    </td>
                                    @foreach ($assignments as $a)
                                        @php $sub = $matrix[$st->id][$a->id] ?? null; @endphp
                                        <td class="px-3 py-3 text-center align-top">
                                            @if ($sub)
                                                <div class="flex flex-col items-center gap-1.5">
                                                    <a href="{{ $sub->link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline">
                                                        <x-icon name="external" class="w-3 h-3" /> ลิงก์
                                                    </a>
                                                    <input type="number" step="0.01" min="0" max="{{ $a->max_score }}"
                                                           wire:model="scores.{{ $sub->id }}"
                                                           class="input !w-20 text-center !py-1.5">
                                                </div>
                                            @else
                                                <span class="badge-slate">ยังไม่ส่ง</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ $assignments->count() + 1 }}" class="td text-center text-slate-400 py-10">ไม่มีนักศึกษาในเงื่อนไขนี้</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card card-pad text-center text-slate-400 py-16 mt-6">
                <x-icon name="check-square" class="w-12 h-12 mx-auto mb-3 opacity-40" />
                <p>เลือกรายวิชาเพื่อเริ่มตรวจงาน</p>
            </div>
        @endif
    </x-admin-shell>
</div>
