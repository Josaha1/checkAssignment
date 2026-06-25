<div>
    <x-admin-shell title="แดชบอร์ด">
        {{-- stat cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            @php
                $cards = [
                    ['นักศึกษา', $stats['students'], 'users', 'brand'],
                    ['รายวิชา', $stats['subjects'], 'book', 'violet'],
                    ['กลุ่มเรียน', $stats['groups'], 'school', 'sky'],
                    ['ชิ้นงาน', $stats['assignments'], 'clipboard', 'emerald'],
                    ['ส่งแล้ว', $stats['submissions'], 'inbox', 'teal'],
                    ['รอตรวจ', $stats['ungraded'], 'clock', 'amber'],
                ];
            @endphp
            @foreach ($cards as [$label, $value, $icon, $color])
                <div class="card card-pad flex items-center gap-4">
                    <span class="grid place-items-center w-11 h-11 rounded-xl bg-{{ $color }}-100 text-{{ $color }}-600 dark:bg-{{ $color }}-500/15 dark:text-{{ $color }}-400">
                        <x-icon name="{{ $icon }}" class="w-5 h-5" />
                    </span>
                    <div>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none">{{ $value }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ $label }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid lg:grid-cols-3 gap-6 mt-6">
            {{-- bar chart --}}
            <div class="lg:col-span-2 card card-pad"
                 x-data
                 x-init="
                    new ApexCharts($refs.bar, {
                        chart: { type: 'bar', height: 280, fontFamily: 'inherit', toolbar: { show: false } },
                        series: [{ name: 'ส่งงาน', data: {{ $chartDays['data']->toJson() }} }],
                        xaxis: { categories: {{ $chartDays['labels']->toJson() }} },
                        colors: ['#4f46e5'],
                        plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
                        dataLabels: { enabled: false },
                        grid: { borderColor: 'rgba(148,163,184,0.2)' },
                        tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
                    }).render()
                 ">
                <h2 class="font-semibold text-slate-800 dark:text-slate-100 mb-1">การส่งงาน 7 วันล่าสุด</h2>
                <div x-ref="bar"></div>
            </div>

            {{-- donut --}}
            <div class="card card-pad"
                 x-data
                 x-init="
                    new ApexCharts($refs.donut, {
                        chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
                        series: [{{ $stats['submissions'] - $stats['ungraded'] }}, {{ $stats['ungraded'] }}],
                        labels: ['ตรวจแล้ว', 'รอตรวจ'],
                        colors: ['#10b981', '#f59e0b'],
                        legend: { position: 'bottom' },
                        stroke: { width: 0 },
                        plotOptions: { pie: { donut: { size: '68%' } } }
                    }).render()
                 ">
                <h2 class="font-semibold text-slate-800 dark:text-slate-100 mb-1">สถานะการตรวจ</h2>
                <div x-ref="donut"></div>
            </div>
        </div>

        {{-- recent --}}
        <div class="card mt-6 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                <x-icon name="clock" class="w-5 h-5 text-slate-400" />
                <h2 class="font-semibold text-slate-800 dark:text-slate-100">การส่งงานล่าสุด</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <x-th-sort column="student_code" :sort-by="$sortBy" :sort-dir="$sortDir">รหัสนักศึกษา</x-th-sort>
                            <x-th-sort column="subject" :sort-by="$sortBy" :sort-dir="$sortDir">วิชา · งาน</x-th-sort>
                            <x-th-sort column="submitted_at" :sort-by="$sortBy" :sort-dir="$sortDir">เวลา</x-th-sort>
                            <x-th-sort column="status" :sort-by="$sortBy" :sort-dir="$sortDir">สถานะ</x-th-sort>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($recent as $s)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="td font-medium text-slate-900 dark:text-white">{{ $s->student?->student_code }}</td>
                                <td class="td">{{ $s->assignment?->subject?->code }} · {{ $s->assignment?->title }}</td>
                                <td class="td text-slate-400">{{ $s->submitted_at?->format('d/m/Y H:i') }}</td>
                                <td class="td">
                                    @if ($s->score !== null)
                                        <span class="badge-green"><x-icon name="check" class="w-3 h-3" /> ตรวจแล้ว</span>
                                    @else
                                        <span class="badge-amber"><x-icon name="clock" class="w-3 h-3" /> รอตรวจ</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="td text-center text-slate-400 py-10">ยังไม่มีการส่งงาน</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-admin-shell>
</div>
