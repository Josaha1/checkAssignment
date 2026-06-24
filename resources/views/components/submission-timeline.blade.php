@props(['histories', 'showScore' => false])

{{-- ไทม์ไลน์การกระทำต่อ submission (ล่าสุดบนสุด) — รางแนวตั้ง + จุดสีตามชนิดเหตุการณ์ --}}
@php
    // label graded ต่างกันตามผู้ดู: แอดมินเห็น "ให้คะแนน" + เลข, นักศึกษาเห็นแค่ "ตรวจให้คะแนนแล้ว" (ห้ามโชว์คะแนน)
    $map = [
        'uploaded'      => ['label' => 'อัปโหลดไฟล์',        'icon' => 'upload',       'tone' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400'],
        'graded'        => ['label' => $showScore ? 'ให้คะแนน' : 'ตรวจให้คะแนนแล้ว', 'icon' => 'circle-check', 'tone' => 'bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400'],
        'score_cleared' => ['label' => 'คะแนนถูกล้าง',       'icon' => 'alert',        'tone' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400'],
        'file_deleted'  => ['label' => 'ลบไฟล์',             'icon' => 'trash',        'tone' => 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400'],
    ];
@endphp
<ol class="relative ml-2 border-l border-slate-200 dark:border-slate-700 space-y-3 py-1">
    @foreach ($histories as $h)
        @php $m = $map[$h->action] ?? ['label' => $h->action, 'icon' => 'list', 'tone' => 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-300']; @endphp
        <li class="relative pl-5">
            <span class="absolute -left-[13px] top-0 grid place-items-center w-6 h-6 rounded-full ring-4 ring-white dark:ring-slate-900 {{ $m['tone'] }}">
                <x-icon :name="$m['icon']" class="w-3.5 h-3.5" />
            </span>
            <p class="text-sm font-medium text-slate-800 dark:text-slate-100 leading-tight">
                {{ $m['label'] }}
                @if ($h->detail && ($h->action !== 'graded' || $showScore))
                    <span class="font-normal text-slate-400">· {{ $h->detail }}</span>
                @endif
            </p>
            <p class="text-[11px] text-slate-400 mt-0.5">{{ $h->actor ?? 'ระบบ' }} · {{ $h->created_at?->format('d/m/Y H:i') }}</p>
        </li>
    @endforeach
</ol>
