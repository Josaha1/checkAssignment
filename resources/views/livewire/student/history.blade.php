<div>
    <x-student-shell title="ประวัติการส่งงาน">
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr><th class="th">วิชา · งาน</th><th class="th">ส่งเมื่อ</th><th class="th">ลิงก์</th><th class="th">สถานะ</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($submissions as $s)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="td">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $s->assignment?->title }}</p>
                                    <p class="text-xs text-slate-400">{{ $s->assignment?->subject?->code }} · {{ $s->assignment?->subject?->name }}</p>
                                </td>
                                <td class="td text-slate-500">{{ $s->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="td">
                                    <a href="{{ $s->link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-brand-600 hover:underline"><x-icon name="external" class="w-4 h-4" /> เปิด</a>
                                </td>
                                <td class="td"><span class="badge-green"><x-icon name="check" class="w-3 h-3" /> ส่งแล้ว</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="td text-center text-slate-400 py-12">
                                <x-icon name="clock" class="w-10 h-10 mx-auto mb-2 opacity-40" />
                                ยังไม่มีประวัติการส่งงาน
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-student-shell>
</div>
