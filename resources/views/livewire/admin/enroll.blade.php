<div>
    <x-admin-shell title="ลงทะเบียน · {{ $subject->code }}">
        <a href="{{ route('admin.subjects') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-600 mb-4">
            <x-icon name="arrow-left" class="w-4 h-4" /> กลับรายวิชา
        </a>

        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center gap-3">
                <span class="badge-brand"><x-icon name="users" class="w-3.5 h-3.5" /> ลงทะเบียนแล้ว {{ $enrolledCount }} คน</span>
                <div class="flex items-center gap-2 ml-auto">
                    <select wire:model.live="groupFilter" class="select !w-auto">
                        <option value="">ทุกกลุ่ม</option>
                        @foreach ($groups as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach
                    </select>
                    @if ($groupFilter)
                        <button wire:click="enrollGroup" class="btn-primary btn-sm"><x-icon name="plus" class="w-4 h-4" /> ลงทะเบียนทั้งกลุ่ม</button>
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="th w-12"></th>
                            <x-th-sort column="student_code" :sort-by="$sortBy" :sort-dir="$sortDir">รหัส</x-th-sort>
                            <x-th-sort column="full_name" :sort-by="$sortBy" :sort-dir="$sortDir">ชื่อ-นามสกุล</x-th-sort>
                            <x-th-sort column="study_group" :sort-by="$sortBy" :sort-dir="$sortDir">กลุ่ม</x-th-sort>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($students as $s)
                            <tr class="{{ isset($enrolled[$s->id]) ? 'bg-brand-50/60 dark:bg-brand-500/5' : 'hover:bg-slate-50 dark:hover:bg-slate-800/40' }}">
                                <td class="td">
                                    <input type="checkbox" wire:click="toggle({{ $s->id }})" @checked(isset($enrolled[$s->id]))
                                           class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:bg-slate-700 dark:border-slate-600">
                                </td>
                                <td class="td font-medium text-slate-900 dark:text-white">{{ $s->student_code }}</td>
                                <td class="td">{{ $s->full_name }}</td>
                                <td class="td"><span class="badge-slate">{{ $s->study_group ?? '—' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="td text-center text-slate-400 py-10">ไม่มีนักศึกษา</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-admin-shell>
</div>
