<div>
    <x-admin-shell title="สถานะการส่ง">
        <div class="card card-pad flex flex-wrap items-end gap-3">
            <div>
                <label class="label">รายวิชา</label>
                <select wire:model.live="subjectId" class="select !w-auto min-w-[200px]">
                    <option value="">— เลือกวิชา —</option>
                    @foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->code }} · {{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label">ชิ้นงาน</label>
                <select wire:model.live="assignmentId" class="select !w-auto min-w-[180px]" @disabled(!$subjectId)>
                    <option value="">— เลือกงาน —</option>
                    @foreach ($assignments as $a)<option value="{{ $a->id }}">{{ $a->title }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label">ห้อง</label>
                <select wire:model.live="roomId" class="select !w-auto">
                    <option value="">ทุกห้อง</option>
                    @foreach ($rooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
                </select>
            </div>
        </div>

        @if ($assignmentId)
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-6">
                <div class="card card-pad flex items-center gap-3">
                    <span class="grid place-items-center w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400"><x-icon name="circle-check" class="w-5 h-5" /></span>
                    <div><p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $submittedCount }}</p><p class="text-xs text-slate-500">ส่งแล้ว</p></div>
                </div>
                <div class="card card-pad flex items-center gap-3">
                    <span class="grid place-items-center w-10 h-10 rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400"><x-icon name="clock" class="w-5 h-5" /></span>
                    <div><p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $missingCount }}</p><p class="text-xs text-slate-500">ยังไม่ส่ง</p></div>
                </div>
                <div class="card card-pad flex items-center gap-3 col-span-2 sm:col-span-1">
                    <span class="grid place-items-center w-10 h-10 rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"><x-icon name="users" class="w-5 h-5" /></span>
                    <div><p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $rows->count() }}</p><p class="text-xs text-slate-500">ทั้งหมด</p></div>
                </div>
            </div>

            <div class="card mt-6 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr><th class="th">รหัส</th><th class="th">ชื่อ-สกุล</th><th class="th">ห้อง</th><th class="th">สถานะ</th><th class="th">เวลาส่ง</th><th class="th">ลิงก์</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="td font-medium text-slate-900 dark:text-white">{{ $row['student']->student_code }}</td>
                                    <td class="td">{{ $row['student']->full_name }}</td>
                                    <td class="td"><span class="badge-slate">{{ $row['student']->room?->name ?? '—' }}</span></td>
                                    <td class="td">
                                        @if ($row['submission'])
                                            <span class="badge-green"><x-icon name="check" class="w-3 h-3" /> ส่งแล้ว</span>
                                        @else
                                            <span class="badge-amber"><x-icon name="clock" class="w-3 h-3" /> ยังไม่ส่ง</span>
                                        @endif
                                    </td>
                                    <td class="td text-slate-400">{{ $row['submission']?->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="td">
                                        @if ($row['submission'])
                                            <a href="{{ $row['submission']->link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-brand-600 hover:underline"><x-icon name="external" class="w-4 h-4" /> เปิด</a>
                                        @else — @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card card-pad text-center text-slate-400 py-16 mt-6">
                <x-icon name="inbox" class="w-12 h-12 mx-auto mb-3 opacity-40" />
                <p>เลือกวิชาและชิ้นงานเพื่อดูว่าใครส่งแล้ว / ยังไม่ส่ง</p>
            </div>
        @endif
    </x-admin-shell>
</div>
