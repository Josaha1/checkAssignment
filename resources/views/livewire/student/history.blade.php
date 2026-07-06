<div>
    <x-student-shell title="ประวัติการส่งงาน">
        <div class="card overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                <select wire:model.live="subjectId" class="select !w-auto min-w-[200px]">
                    <option value="">ทุกวิชา</option>
                    @foreach ($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->code }} · {{ $sub->name }}</option>@endforeach
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <x-th-sort column="subject" :sort-by="$sortBy" :sort-dir="$sortDir">วิชา · งาน</x-th-sort>
                            <x-th-sort column="submitted_at" :sort-by="$sortBy" :sort-dir="$sortDir">ส่งเมื่อ</x-th-sort>
                            <th class="th">ไฟล์/ลิงก์</th>
                            <x-th-sort column="status" :sort-by="$sortBy" :sort-dir="$sortDir">สถานะ</x-th-sort>
                        </tr>
                    </thead>
                    @forelse ($submissions as $s)
                        <tbody x-data="{ open: false }" class="border-t border-slate-100 dark:border-slate-800">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="td">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $s->assignment?->title }}</p>
                                    <p class="text-xs text-slate-400">{{ $s->assignment?->subject?->code }} · {{ $s->assignment?->subject?->name }}</p>
                                </td>
                                <td class="td text-slate-500">{{ $s->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="td">
                                    @if ($s->files->isNotEmpty())
                                        <div class="flex flex-col gap-0.5">
                                            @foreach ($s->files as $f)
                                                <a href="{{ $f->url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-brand-600 hover:underline max-w-[220px] truncate"><x-icon :name="$f->type === 'link' ? 'link' : 'external'" class="w-4 h-4 shrink-0" /> <span class="truncate">{{ $f->name }}</span></a>
                                            @endforeach
                                        </div>
                                    @else <span class="text-slate-400">—</span> @endif
                                </td>
                                <td class="td">
                                    @if (! $s->submitted_at)
                                        <span class="badge-amber"><x-icon name="clock" class="w-3 h-3" /> ยังไม่ส่ง</span> {{-- ลบไฟล์ครบแล้ว --}}
                                    @elseif ($s->isGraded())
                                        <span class="badge-blue"><x-icon name="circle-check" class="w-3 h-3" /> ตรวจแล้ว</span>
                                    @else
                                        <span class="badge-green"><x-icon name="check" class="w-3 h-3" /> ส่งแล้ว</span>
                                    @endif
                                    @if ($s->histories->isNotEmpty())
                                        <button type="button" @click="open = !open" class="mt-1.5 flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                                            <x-icon name="list" class="w-3.5 h-3.5" />
                                            <span x-text="open ? 'ซ่อนประวัติ' : 'ประวัติ ({{ $s->histories->count() }})'"></span>
                                            <span class="transition-transform" :class="open && 'rotate-180'"><x-icon name="chevron-down" class="w-3 h-3" /></span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @if ($s->histories->isNotEmpty())
                                <tr x-show="open" style="display:none">
                                    <td colspan="4" class="px-5 pb-4 bg-slate-50/60 dark:bg-slate-800/20">
                                        <x-submission-timeline :histories="$s->histories" :show-score="false" />
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    @empty
                        <tbody>
                            <tr><td colspan="4" class="td text-center text-slate-400 py-12">
                                <x-icon name="clock" class="w-10 h-10 mx-auto mb-2 opacity-40" />
                                ยังไม่มีประวัติการส่งงาน
                            </td></tr>
                        </tbody>
                    @endforelse
                </table>
            </div>
        </div>
    </x-student-shell>
</div>
