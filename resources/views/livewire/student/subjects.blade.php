<div>
    <x-student-shell title="วิชาของฉัน">
        <div class="grid sm:grid-cols-2 gap-5">
            @forelse ($subjects as $row)
                <section class="card overflow-hidden">
                    <div class="card-pad pb-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $row['subject']->code }}</p>
                                <p class="text-sm text-slate-500">{{ $row['subject']->name }}</p>
                            </div>
                            <span class="badge-brand">{{ $row['done'] }}/{{ $row['total'] }}</span>
                        </div>
                        {{-- progress --}}
                        <div class="mt-3 h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full bg-brand-600 transition-all" style="width: {{ $row['percent'] }}%"></div>
                        </div>
                        <p class="text-xs text-slate-400 mt-1.5">ส่งแล้ว {{ $row['percent'] }}%</p>
                    </div>

                    <div class="border-t border-slate-100 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($row['assignments'] as $a)
                            @php $sub = $subs[$a->id] ?? null; @endphp
                            {{-- ทั้งแถวกดเข้าไปจัดการไฟล์ (เพิ่ม/ลบ) ได้ทุกสถานะ --}}
                            <a href="{{ route('student.submit', $a) }}" wire:navigate
                               class="px-5 py-2.5 flex items-center justify-between gap-2 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <span class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ $a->title }}</span>
                                @if ($sub && $sub->isGraded())
                                    <span class="badge-blue shrink-0"><x-icon name="circle-check" class="w-3 h-3" /> ตรวจแล้ว</span>
                                @elseif ($sub)
                                    <span class="badge-green shrink-0"><x-icon name="check" class="w-3 h-3" /> ส่งแล้ว</span>
                                @else
                                    <span class="badge-amber shrink-0"><x-icon name="send" class="w-3 h-3" /> ส่งงาน</span>
                                @endif
                            </a>
                        @empty
                            <p class="px-5 py-3 text-sm text-slate-400">ยังไม่มีงาน</p>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="card card-pad text-center text-slate-400 py-16 sm:col-span-2">
                    <x-icon name="book" class="w-12 h-12 mx-auto mb-3 opacity-40" />
                    ยังไม่ได้ลงทะเบียนวิชาใด
                </div>
            @endforelse
        </div>
    </x-student-shell>
</div>
