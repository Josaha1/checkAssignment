<div class="min-h-screen bg-slate-50">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">{{ $student->student_code }} · {{ $student->room?->name ?? '—' }}</p>
                <h1 class="text-lg font-semibold text-slate-900">{{ $student->full_name }}</h1>
            </div>
            <form method="POST" action="{{ route('student.logout') }}">
                @csrf
                <button class="text-sm text-slate-500 hover:text-rose-600">ออกจากระบบ</button>
            </form>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6 space-y-6">
        <div class="rounded-xl bg-indigo-600 text-white px-5 py-4 flex items-center justify-between">
            <span class="text-sm">งานที่ยังไม่ได้ส่งทั้งหมด</span>
            <span class="text-2xl font-bold">{{ $pendingTotal }}</span>
        </div>

        @forelse ($subjects as $row)
            <section class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
                    <h2 class="font-semibold text-slate-900">
                        {{ $row['subject']->code }} · {{ $row['subject']->name }}
                    </h2>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach ($row['pending'] as $a)
                        <div class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-800 truncate">{{ $a->title }}</p>
                                <p class="text-xs text-slate-400">
                                    @if ($a->due_date) กำหนดส่ง {{ $a->due_date->format('d/m/Y') }} @else ไม่มีกำหนด @endif
                                </p>
                            </div>
                            <a href="{{ route('student.submit', $a) }}"
                               class="shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">
                                ส่งงาน
                            </a>
                        </div>
                    @endforeach

                    @foreach ($row['done'] as $a)
                        <div class="px-5 py-3 flex items-center justify-between gap-3 bg-emerald-50/40">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-500 truncate line-through">{{ $a->title }}</p>
                            </div>
                            <span class="shrink-0 text-emerald-600 text-sm font-medium">✓ ส่งแล้ว</span>
                        </div>
                    @endforeach

                    @if ($row['pending']->isEmpty() && $row['done']->isEmpty())
                        <p class="px-5 py-4 text-sm text-slate-400">ยังไม่มีงานในวิชานี้</p>
                    @endif
                </div>
            </section>
        @empty
            <div class="bg-white rounded-xl border border-slate-200 px-5 py-10 text-center text-slate-400">
                ยังไม่ได้ลงทะเบียนวิชาใด ติดต่ออาจารย์ผู้สอน
            </div>
        @endforelse
    </main>
</div>
