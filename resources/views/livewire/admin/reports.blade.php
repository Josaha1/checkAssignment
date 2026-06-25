<div>
    <x-admin-shell title="ศูนย์รายงาน & ส่งออก">
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center gap-3">
                <x-icon name="report" class="w-5 h-5 text-brand-600" />
                <h2 class="font-semibold text-slate-800 dark:text-slate-100">สรุปรายวิชา</h2>
                <div class="relative ml-auto max-w-xs">
                    <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="ค้นหารหัส/ชื่อวิชา" class="input pl-9">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <x-th-sort column="code" :sort-by="$sortBy" :sort-dir="$sortDir">วิชา</x-th-sort>
                            <x-th-sort column="students_count" :sort-by="$sortBy" :sort-dir="$sortDir">นักศึกษา</x-th-sort>
                            <x-th-sort column="assignments_count" :sort-by="$sortBy" :sort-dir="$sortDir">ชิ้นงาน</x-th-sort>
                            <x-th-sort column="submission_total" :sort-by="$sortBy" :sort-dir="$sortDir">ส่งแล้ว</x-th-sort>
                            <x-th-sort column="graded_total" :sort-by="$sortBy" :sort-dir="$sortDir">ตรวจแล้ว</x-th-sort>
                            <th class="th text-right">รายงาน</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($subjects as $s)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="td">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $s->code }}</p>
                                    <p class="text-xs text-slate-400">{{ $s->name }}</p>
                                </td>
                                <td class="td">{{ $s->students_count }}</td>
                                <td class="td">{{ $s->assignments_count }}</td>
                                <td class="td"><span class="badge-green">{{ $s->submission_total }}</span></td>
                                <td class="td"><span class="badge-brand">{{ $s->graded_total }}</span></td>
                                <td class="td text-right whitespace-nowrap">
                                    <button wire:click="$set('subjectId', {{ $s->id }})" class="btn-ghost btn-sm"><x-icon name="report" class="w-4 h-4" /> ดูสถิติ</button>
                                    <a href="{{ route('admin.export', $s) }}" class="btn-success btn-sm"><x-icon name="download" class="w-4 h-4" /> CSV</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="td text-center text-slate-400 py-10">ยังไม่มีรายวิชา</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($subjectId && $assignmentStats->isNotEmpty())
            <div class="card mt-6 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100">สถิติคะแนนรายชิ้นงาน</h2>
                    <button wire:click="$set('subjectId', null)" class="btn-ghost btn-sm"><x-icon name="x" class="w-4 h-4" /></button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <x-th-sort column="title" action="sortStat" :sort-by="$statSort" :sort-dir="$statDir">ชิ้นงาน</x-th-sort>
                                <x-th-sort column="max_score" action="sortStat" :sort-by="$statSort" :sort-dir="$statDir">เต็ม</x-th-sort>
                                <x-th-sort column="submitted" action="sortStat" :sort-by="$statSort" :sort-dir="$statDir">ส่ง</x-th-sort>
                                <x-th-sort column="graded" action="sortStat" :sort-by="$statSort" :sort-dir="$statDir">ตรวจ</x-th-sort>
                                <x-th-sort column="avg" action="sortStat" :sort-by="$statSort" :sort-dir="$statDir">เฉลี่ย</x-th-sort>
                                <x-th-sort column="max" action="sortStat" :sort-by="$statSort" :sort-dir="$statDir">สูงสุด</x-th-sort>
                                <x-th-sort column="min" action="sortStat" :sort-by="$statSort" :sort-dir="$statDir">ต่ำสุด</x-th-sort>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($assignmentStats as $row)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="td font-medium text-slate-900 dark:text-white">{{ $row['assignment']->title }}</td>
                                    <td class="td text-slate-400">{{ rtrim(rtrim($row['assignment']->max_score, '0'), '.') }}</td>
                                    <td class="td">{{ $row['submitted'] }}</td>
                                    <td class="td">{{ $row['graded'] }}</td>
                                    <td class="td font-semibold text-brand-600 dark:text-brand-400">{{ $row['avg'] }}</td>
                                    <td class="td">{{ $row['max'] !== null ? rtrim(rtrim($row['max'], '0'), '.') : '—' }}</td>
                                    <td class="td">{{ $row['min'] !== null ? rtrim(rtrim($row['min'], '0'), '.') : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-admin-shell>
</div>
