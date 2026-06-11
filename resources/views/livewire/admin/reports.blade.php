<div>
    <x-admin-shell title="ศูนย์รายงาน & ส่งออก">
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                <x-icon name="report" class="w-5 h-5 text-brand-600" />
                <h2 class="font-semibold text-slate-800 dark:text-slate-100">สรุปรายวิชา</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr><th class="th">วิชา</th><th class="th">นักศึกษา</th><th class="th">ชิ้นงาน</th><th class="th">ส่งแล้ว</th><th class="th">ตรวจแล้ว</th><th class="th text-right">รายงาน</th></tr>
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
                            <tr><th class="th">ชิ้นงาน</th><th class="th">เต็ม</th><th class="th">ส่ง</th><th class="th">ตรวจ</th><th class="th">เฉลี่ย</th><th class="th">สูงสุด</th><th class="th">ต่ำสุด</th></tr>
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
