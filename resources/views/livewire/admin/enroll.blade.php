<div>
    <x-admin-shell title="ลงทะเบียน — {{ $subject->code }} {{ $subject->name }}">
        <a href="{{ route('admin.subjects') }}" class="text-sm text-slate-500 hover:text-slate-700">← กลับรายวิชา</a>

        <div class="mt-3 bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex flex-wrap items-center gap-3">
                <span class="text-sm text-slate-600">ลงทะเบียนแล้ว <strong>{{ $enrolledCount }}</strong> คน</span>
                <div class="flex items-center gap-2 ml-auto">
                    <select wire:model.live="roomFilter" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none">
                        <option value="">ทุกห้อง</option>
                        @foreach ($rooms as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                    @if ($roomFilter)
                        <button wire:click="enrollRoom" class="bg-sky-600 hover:bg-sky-700 text-white text-sm px-3 py-1.5 rounded-lg">
                            ลงทะเบียนทั้งห้อง
                        </button>
                    @endif
                </div>
            </div>

            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-2 w-12"></th>
                        <th class="text-left px-5 py-2 font-medium">รหัส</th>
                        <th class="text-left px-5 py-2 font-medium">ชื่อ-สกุล</th>
                        <th class="text-left px-5 py-2 font-medium">ห้อง</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $s)
                        <tr class="{{ isset($enrolled[$s->id]) ? 'bg-indigo-50/40' : '' }}">
                            <td class="px-5 py-2 text-center">
                                <input type="checkbox" wire:click="toggle({{ $s->id }})"
                                       @checked(isset($enrolled[$s->id]))
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            </td>
                            <td class="px-5 py-2 font-medium text-slate-700">{{ $s->student_code }}</td>
                            <td class="px-5 py-2 text-slate-600">{{ $s->full_name }}</td>
                            <td class="px-5 py-2 text-slate-500">{{ $s->room?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400">ไม่มีนักศึกษา</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin-shell>
</div>
