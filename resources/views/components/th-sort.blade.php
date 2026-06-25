@props(['column', 'sortBy' => null, 'sortDir' => 'asc', 'align' => 'left', 'action' => 'sort'])

@php
    // ทิศการจัดเรียงเนื้อหา (ตัวเลขมัก align ขวา)
    $justify = ['left' => 'justify-start', 'center' => 'justify-center', 'right' => 'justify-end'][$align] ?? 'justify-start';
@endphp

{{-- หัวตารางกดเรียงได้ — chevron-down หมุน 180° = น้อย→มาก, จาง = ยังไม่เรียงคอลัมน์นี้ --}}
<th {{ $attributes->merge(['class' => 'th']) }}>
    <button type="button" wire:click="{{ $action }}('{{ $column }}')"
            class="inline-flex w-full items-center gap-1 {{ $justify }} hover:text-slate-700 dark:hover:text-slate-200">
        {{ $slot }}
        <x-icon name="chevron-down"
                class="w-3.5 h-3.5 shrink-0 transition {{ $sortBy === $column ? ($sortDir === 'asc' ? 'rotate-180' : '') : 'opacity-30' }}" />
    </button>
</th>
