@props([
    'model',                 // ชื่อ property ที่ wire:model ผูก (uploads / file)
    'multiple' => false,
    'accept' => '',
    'accent' => 'brand',     // brand (นักศึกษา) | emerald (แอดมิน)
    'hint' => '',
    'title' => 'คลิกเพื่อเลือกไฟล์ หรือ ลากไฟล์มาวาง',
    'files' => [],           // ไฟล์ที่อัปโหลดแล้วจาก server ($uploads / [$file]) — TemporaryUploadedFile[]
])
@php
    $tones = [
        'brand'   => ['icon' => 'bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300',     'hover' => 'hover:border-brand-400',   'drag' => 'border-brand-500 bg-brand-50/70 dark:bg-brand-500/10'],
        'emerald' => ['icon' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400', 'hover' => 'hover:border-emerald-400', 'drag' => 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-500/10'],
    ];
    $tone = $tones[$accent] ?? $tones['brand'];
@endphp
<div x-data="{ over: false }"
     x-on:dragover.prevent="over = true"
     x-on:dragleave.prevent="over = false"
     x-on:drop.prevent="over = false; $refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change', { bubbles: true }))">
    {{-- คลิก label เปิด file picker; drag ลงโซนก็ได้ — Livewire append ไฟล์ที่เลือกแต่ละครั้งเข้า $uploads (เลือกทีละไฟล์ก็สะสมครบ) --}}
    <label :class="over ? '{{ $tone['drag'] }}' : 'border-slate-300 dark:border-slate-600 bg-slate-50/60 dark:bg-slate-800/30 {{ $tone['hover'] }}'"
           class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-6 py-8 text-center cursor-pointer transition">
        <span class="grid place-items-center w-12 h-12 rounded-full {{ $tone['icon'] }}">
            <x-icon name="upload" class="w-6 h-6" />
        </span>
        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $title }}</p>
        @if ($hint)
            <p class="text-xs text-slate-400">{{ $hint }}</p>
        @endif
        <input x-ref="input" type="file" wire:model="{{ $model }}"
               @if ($multiple) multiple @endif @if ($accept) accept="{{ $accept }}" @endif
               class="sr-only">
    </label>

    {{-- ไฟล์ที่อัปโหลดแล้ว — render จาก $uploads ฝั่ง server (โชว์ครบทุกไฟล์ที่จะส่งจริง) + ลบรายตัว --}}
    @if (count($files))
        <ul class="mt-3 space-y-2">
            @foreach ($files as $f)
                <li wire:key="up-{{ $f->getFilename() }}" class="flex items-center gap-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/40 px-3 py-2">
                    <x-icon name="check" class="w-4 h-4 text-emerald-500 shrink-0" />
                    <span class="flex-1 text-sm text-slate-700 dark:text-slate-300 truncate">{{ $f->getClientOriginalName() }}</span>
                    {{-- ลบราย temp file ด้วย $removeUpload (built-in) — ลบจาก $uploads ฝั่ง server จริง กันไฟล์ที่ลบหลุดไปตอนส่ง --}}
                    <button type="button" x-on:click="$wire.$removeUpload('{{ $model }}', '{{ $f->getFilename() }}')"
                            aria-label="ลบ {{ $f->getClientOriginalName() }}"
                            class="text-slate-400 hover:text-rose-500 transition shrink-0"><x-icon name="x" class="w-4 h-4" /></button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
