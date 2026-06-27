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
<div x-data="{
        over: false,
        names: [],
        multiple: @js($multiple),
        init() {
            // truth = DataTransfer ผูกกับ DOM node — รอด Livewire morph + กรณี Livewire เคลียร์ input หลัง upload (กัน FileList ถูกแทนทั้งก้อน)
            if (! this.$root._dt) this.$root._dt = new DataTransfer();
        },
        merge(incoming) {
            if (! this.$root._dt) this.$root._dt = new DataTransfer();
            const dt = this.multiple ? this.$root._dt : new DataTransfer(); // เลือกหลายไฟล์ = สะสม / ไฟล์เดียว = แทนที่
            const seen = new Set(Array.from(dt.files).map(f => f.name + ':' + f.size));
            incoming.forEach(f => {
                const key = f.name + ':' + f.size;
                if (! seen.has(key)) { dt.items.add(f); seen.add(key); } // กันไฟล์ซ้ำ
            });
            this.$root._dt = dt;
            this.flush();
        },
        removeAt(i) {
            const ndt = new DataTransfer();
            Array.from(this.$root._dt.files).forEach((f, idx) => { if (idx !== i) ndt.items.add(f); });
            this.$root._dt = ndt;
            this.flush();
        },
        flush() {
            this.$refs.sink.files = this.$root._dt.files;                          // ป้อนชุดสะสมเข้า input ที่มี wire:model
            this.names = Array.from(this.$root._dt.files).map(f => f.name);
            this.$refs.sink.dispatchEvent(new Event('change', { bubbles: true })); // ให้ Livewire อัปโหลดชุดล่าสุด
        }
     }"
     x-on:dragover.prevent="over = true"
     x-on:dragleave.prevent="over = false"
     x-on:drop.prevent="over = false; merge(Array.from($event.dataTransfer.files))">
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
        {{-- picker: ไม่มี wire:model — อ่านไฟล์ที่เลือกแล้วส่งให้ merge() เท่านั้น --}}
        <input x-ref="picker" type="file"
               @if ($multiple) multiple @endif @if ($accept) accept="{{ $accept }}" @endif
               x-on:change="merge(Array.from($refs.picker.files)); $refs.picker.value = ''"
               class="sr-only">
    </label>

    {{-- sink: input ที่ผูก wire:model จริง — รับเฉพาะชุดสะสมที่ merge()/removeAt() dispatch (กัน race กับ native pick) --}}
    <input x-ref="sink" type="file" wire:model="{{ $model }}" @if ($multiple) multiple @endif class="hidden">

    {{-- ไฟล์ที่เลือก — โชว์ทุกไฟล์ที่สะสม + ลบรายตัวได้ --}}
    <template x-if="names.length">
        <ul class="mt-3 space-y-2">
            <template x-for="(n, i) in names" :key="n + ':' + i">
                <li class="flex items-center gap-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/40 px-3 py-2">
                    <x-icon name="check" class="w-4 h-4 text-emerald-500 shrink-0" />
                    <span class="flex-1 text-sm text-slate-700 dark:text-slate-300 truncate" x-text="n"></span>
                    <button type="button" @click="removeAt(i)" :aria-label="'ลบ ' + n"
                            class="text-slate-400 hover:text-rose-500 transition shrink-0"><x-icon name="x" class="w-4 h-4" /></button>
                </li>
            </template>
        </ul>
    </template>
</div>
