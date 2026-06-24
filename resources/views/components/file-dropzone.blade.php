@props([
    'model',                 // ชื่อ property ที่ wire:model ผูก (uploads / file)
    'multiple' => false,
    'accept' => '',
    'accent' => 'brand',     // brand (นักศึกษา) | emerald (แอดมิน)
    'hint' => '',
    'title' => 'คลิกเพื่อเลือกไฟล์ หรือ ลากไฟล์มาวาง',
])
@php
    $tones = [
        'brand'   => ['icon' => 'bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300',     'hover' => 'hover:border-brand-400',   'drag' => 'border-brand-500 bg-brand-50/70 dark:bg-brand-500/10'],
        'emerald' => ['icon' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400', 'hover' => 'hover:border-emerald-400', 'drag' => 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-500/10'],
    ];
    $tone = $tones[$accent] ?? $tones['brand'];
@endphp
<div x-data="{ over: false, names: [] }"
     x-on:dragover.prevent="over = true"
     x-on:dragleave.prevent="over = false"
     x-on:drop.prevent="over = false; $refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change', { bubbles: true }))">
    {{-- คลิก label เปิด file picker; drag ลงโซนก็ได้ --}}
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
               x-on:change="names = Array.from($refs.input.files).map(f => f.name)"
               class="sr-only">
    </label>

    {{-- ไฟล์ที่เลือก — โชว์ทันทีฝั่ง client --}}
    <template x-if="names.length">
        <ul class="mt-2 space-y-1">
            <template x-for="(n, i) in names" :key="i">
                <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <x-icon name="check" class="w-4 h-4 text-emerald-500 shrink-0" />
                    <span class="truncate" x-text="n"></span>
                </li>
            </template>
        </ul>
    </template>
</div>
