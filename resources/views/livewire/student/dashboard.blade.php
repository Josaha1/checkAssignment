<div>
    <x-student-shell>
        {{-- hero --}}
        <div class="rounded-2xl bg-gradient-to-br from-brand-600 to-violet-700 text-white p-6 flex items-center justify-between mb-6 relative overflow-hidden">
            <div class="absolute -top-10 -right-6 w-40 h-40 rounded-full bg-white/10"></div>
            <div class="relative">
                <p class="text-brand-100 text-sm">งานที่ยังไม่ได้ส่ง</p>
                <p class="text-4xl font-bold mt-1">{{ $pendingTotal }} <span class="text-lg font-normal text-brand-100">ชิ้น</span></p>
            </div>
            <x-icon name="clipboard" class="w-16 h-16 text-white/30 relative" />
        </div>

        @forelse ($subjects as $row)
            <section class="card overflow-hidden mb-5">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-center gap-2">
                    <x-icon name="book" class="w-4 h-4 text-brand-600" />
                    <h2 class="font-semibold text-slate-900 dark:text-white text-sm">{{ $row['subject']->code }} · {{ $row['subject']->name }}</h2>
                    @if ($row['pending']->isNotEmpty())
                        <span class="badge-amber ml-auto">ค้าง {{ $row['pending']->count() }}</span>
                    @else
                        <span class="badge-green ml-auto"><x-icon name="check" class="w-3 h-3" /> ครบแล้ว</span>
                    @endif
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($row['pending'] as $a)
                        <div class="px-5 py-3.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-800 dark:text-slate-100 truncate">{{ $a->title }}</p>
                                <p class="text-xs text-slate-400 flex items-center gap-1 mt-0.5">
                                    <x-icon name="calendar" class="w-3 h-3" />
                                    @if ($a->due_date) กำหนดส่ง {{ $a->due_date->format('d/m/Y') }} @else ไม่มีกำหนด @endif
                                </p>
                            </div>
                            <a href="{{ route('student.submit', $a) }}" wire:navigate class="btn-primary btn-sm shrink-0"><x-icon name="send" class="w-4 h-4" /> ส่งงาน</a>
                        </div>
                    @endforeach

                    @foreach ($row['done'] as $a)
                        @php $sub = $subs[$a->id] ?? null; @endphp
                        {{-- กดเข้าไปจัดการไฟล์ (เพิ่ม/ลบ) ได้แม้ส่ง/ตรวจแล้ว --}}
                        <a href="{{ route('student.submit', $a) }}" wire:navigate
                           class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <p class="text-slate-600 dark:text-slate-300 truncate">{{ $a->title }}</p>
                            <span class="flex items-center gap-2 shrink-0">
                                @if ($sub && $sub->isGraded())
                                    <span class="badge-blue"><x-icon name="circle-check" class="w-3 h-3" /> ตรวจแล้ว</span>
                                @else
                                    <span class="badge-green"><x-icon name="check" class="w-3 h-3" /> ส่งแล้ว</span>
                                @endif
                                <x-icon name="chevron-down" class="w-4 h-4 text-slate-300 -rotate-90" />
                            </span>
                        </a>
                    @endforeach

                    @if ($row['pending']->isEmpty() && $row['done']->isEmpty())
                        <p class="px-5 py-4 text-sm text-slate-400">ยังไม่มีงานในวิชานี้</p>
                    @endif
                </div>
            </section>
        @empty
            <div class="card card-pad text-center text-slate-400 py-16">
                <x-icon name="book" class="w-12 h-12 mx-auto mb-3 opacity-40" />
                ยังไม่ได้ลงทะเบียนวิชาใด — ติดต่ออาจารย์ผู้สอน
            </div>
        @endforelse
    </x-student-shell>
</div>
