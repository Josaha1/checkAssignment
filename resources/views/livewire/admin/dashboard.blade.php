<div>
    <x-admin-shell title="แดชบอร์ด">
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            @php
                $cards = [
                    ['ภาพรวมนักศึกษา', $stats['students'], 'indigo'],
                    ['รายวิชา', $stats['subjects'], 'violet'],
                    ['ห้องเรียน', $stats['rooms'], 'sky'],
                    ['ชิ้นงานทั้งหมด', $stats['assignments'], 'emerald'],
                    ['ส่งงานแล้ว', $stats['submissions'], 'teal'],
                    ['รอตรวจ', $stats['ungraded'], 'amber'],
                ];
            @endphp
            @foreach ($cards as [$label, $value, $color])
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">{{ $label }}</p>
                    <p class="text-3xl font-bold text-{{ $color }}-600 mt-1">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 font-semibold text-slate-800">การส่งงานล่าสุด</div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left px-5 py-2 font-medium">รหัสนักศึกษา</th>
                        <th class="text-left px-5 py-2 font-medium">วิชา · งาน</th>
                        <th class="text-left px-5 py-2 font-medium">เวลา</th>
                        <th class="text-left px-5 py-2 font-medium">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recent as $s)
                        <tr>
                            <td class="px-5 py-2 font-medium text-slate-700">{{ $s->student?->student_code }}</td>
                            <td class="px-5 py-2 text-slate-600">{{ $s->assignment?->subject?->code }} · {{ $s->assignment?->title }}</td>
                            <td class="px-5 py-2 text-slate-400">{{ $s->submitted_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-2">
                                @if ($s->score !== null)
                                    <span class="text-emerald-600">ตรวจแล้ว</span>
                                @else
                                    <span class="text-amber-600">รอตรวจ</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400">ยังไม่มีการส่งงาน</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin-shell>
</div>
