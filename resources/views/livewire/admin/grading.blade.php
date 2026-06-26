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
                <label class="label">กลุ่มเรียน</label>
                <select wire:model.live="group" class="select !w-auto">
                    <option value="">ทุกกลุ่ม</option>
                    @foreach ($groups as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach
                </select>
            </div>
            @if ($subjectId)
                <div>
                    <label class="label">ชิ้นงาน</label>
                    <select wire:model.live="assignmentId" class="select !w-auto min-w-[180px]">
                        <option value="">ทุกชิ้น (ตารางรวม)</option>
                        @foreach ($assignments as $a)<option value="{{ $a->id }}">{{ $a->title }}</option>@endforeach
                    </select>
                </div>
            @endif
            @if ($assignmentId)
                <div>
                    <label class="label">สถานะการส่ง</label>
                    <select wire:model.live="statusFilter" class="select !w-auto">
                        <option value="">ทุกสถานะ</option>
                        <option value="missing">ยังไม่ส่ง</option>
                        <option value="pending">รอตรวจ</option>
                        <option value="graded">ตรวจแล้ว</option>
                    </select>
                </div>
            @endif
            @if ($subjectId)
                <div class="ml-auto flex gap-2">
                    <button wire:click="saveScores" class="btn-primary"><x-icon name="check" class="w-4 h-4" /> บันทึกคะแนน</button>
                    <a href="{{ route('admin.export', ['subject' => $subjectId, 'group' => $group]) }}" class="btn-success"><x-icon name="download" class="w-4 h-4" /> Export CSV</a>
                </div>
            @endif
        </div>

        @if ($subjectId && $assignmentId)
            {{-- โหมดต่อชิ้นงาน: 1 แถว/นักศึกษา + เวลาส่ง + สถานะ + ประวัติ + กรองสถานะ --}}
            <div class="card mt-6 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <x-th-sort column="student_code" :sort-by="$sortBy" :sort-dir="$sortDir">นักศึกษา</x-th-sort>
                                <th class="th">ไฟล์</th>
                                <th class="th">เวลาส่ง</th>
                                <th class="th">สถานะ</th>
                                <th class="th text-center whitespace-nowrap">คะแนน @if ($selectedAssignment)<span class="font-normal text-slate-400">(เต็ม {{ rtrim(rtrim($selectedAssignment->max_score, '0'), '.') }})</span>@endif</th>
                            </tr>
                        </thead>
                        @forelse ($rows as $row)
                            <tbody x-data="{ open: false }" class="border-t border-slate-100 dark:border-slate-800">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="td">
                                        <p class="font-medium text-slate-900 dark:text-white">{{ $row['student']->student_code }}</p>
                                        <p class="text-xs text-slate-400">{{ $row['student']->full_name }} · {{ $row['student']->study_group }}</p>
                                    </td>
                                    <td class="td">
                                        @if ($row['submission'] && $row['submission']->files->isNotEmpty())
                                            <div class="flex flex-col gap-0.5">
                                                @foreach ($row['submission']->files as $f)
                                                    <a href="{{ $f->url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-brand-600 hover:underline max-w-[200px] truncate"><x-icon name="external" class="w-4 h-4 shrink-0" /> <span class="truncate">{{ $f->name }}</span></a>
                                                @endforeach
                                            </div>
                                        @else <span class="text-slate-400">—</span> @endif
                                    </td>
                                    <td class="td text-slate-400 whitespace-nowrap">{{ $row['submission']?->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="td">
                                        @if ($row['status'] === 'graded')
                                            <span class="badge-blue"><x-icon name="circle-check" class="w-3 h-3" /> ตรวจแล้ว</span>
                                        @elseif ($row['status'] === 'pending')
                                            <span class="badge-amber"><x-icon name="clock" class="w-3 h-3" /> รอตรวจ</span>
                                        @else
                                            <span class="badge-slate"><x-icon name="x" class="w-3 h-3" /> ยังไม่ส่ง</span>
                                        @endif
                                        @if ($row['submission'] && $row['submission']->histories->isNotEmpty())
                                            <button type="button" @click="open = !open" class="mt-1.5 flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                                                <x-icon name="list" class="w-3.5 h-3.5" />
                                                <span x-text="open ? 'ซ่อนประวัติ' : 'ประวัติ ({{ $row['submission']->histories->count() }})'"></span>
                                                <span class="transition-transform" :class="open && 'rotate-180'"><x-icon name="chevron-down" class="w-3 h-3" /></span>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="td text-center">
                                        @if ($row['submission'])
                                            <input type="number" step="0.01" min="0" max="{{ $selectedAssignment?->max_score }}"
                                                   wire:model="scores.{{ $row['submission']->id }}"
                                                   class="input !w-20 text-center !py-1.5">
                                        @else
                                            <span class="text-slate-400 text-xs">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @if ($row['submission'] && $row['submission']->histories->isNotEmpty())
                                    <tr x-show="open" style="display:none">
                                        <td colspan="5" class="px-5 pb-4 bg-slate-50/60 dark:bg-slate-800/20">
                                            <x-submission-timeline :histories="$row['submission']->histories" :show-score="true" />
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        @empty
                            <tbody><tr><td colspan="5" class="td text-center text-slate-400 py-10">ไม่มีนักศึกษาในเงื่อนไขนี้</td></tr></tbody>
                        @endforelse
                    </table>
                </div>
            </div>
        @elseif ($subjectId)
            <div class="card mt-6 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <x-th-sort column="student_code" :sort-by="$sortBy" :sort-dir="$sortDir" class="sticky left-0 bg-slate-50 dark:bg-slate-800 z-10">นักศึกษา</x-th-sort>
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
                                        <p class="text-xs text-slate-400">{{ $st->full_name }} · {{ $st->study_group }}</p>
                                    </td>
                                    @foreach ($assignments as $a)
                                        @php $sub = $matrix[$st->id][$a->id] ?? null; @endphp
                                        <td class="px-3 py-3 text-center align-top">
                                            @if ($sub)
                                                <div class="flex flex-col items-center gap-1.5">
                                                    @forelse ($sub->files as $f)
                                                        <a href="{{ $f->url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline max-w-[120px] truncate">
                                                            <x-icon name="external" class="w-3 h-3 shrink-0" /> <span class="truncate">{{ $f->name }}</span>
                                                        </a>
                                                    @empty
                                                        <span class="text-[10px] text-slate-400">ไม่มีไฟล์</span>
                                                    @endforelse
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
