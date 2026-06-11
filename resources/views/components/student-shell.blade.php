@props(['title' => null])

@php
    $student = auth('student')->user();
    $tabs = [
        ['student.dashboard', 'หน้าหลัก', 'dashboard'],
        ['student.subjects',  'วิชาของฉัน', 'book'],
        ['student.history',   'ประวัติการส่ง', 'clock'],
    ];
@endphp

<div x-data class="min-h-screen">
    <header class="sticky top-0 z-20 bg-white/85 dark:bg-slate-900/85 backdrop-blur border-b border-slate-200 dark:border-slate-800">
        <div class="max-w-5xl mx-auto px-4">
            <div class="h-16 flex items-center gap-3">
                <span class="grid place-items-center w-9 h-9 rounded-xl bg-brand-600 text-white"><x-icon name="cap" class="w-5 h-5" /></span>
                <div class="leading-tight">
                    <p class="font-bold text-slate-900 dark:text-white text-sm">{{ $student->full_name }}</p>
                    <p class="text-[11px] text-slate-400">{{ $student->student_code }} · {{ $student->room?->name ?? '—' }}</p>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <button @click="document.documentElement.classList.toggle('dark'); localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light'"
                            class="btn-ghost btn-sm !p-2" title="โหมดมืด/สว่าง">
                        <x-icon name="sun" class="w-5 h-5 hidden dark:block" />
                        <x-icon name="moon" class="w-5 h-5 dark:hidden" />
                    </button>
                    <form method="POST" action="{{ route('student.logout') }}">
                        @csrf
                        <button class="btn-ghost btn-sm"><x-icon name="logout" class="w-4 h-4" /> <span class="hidden sm:inline">ออกจากระบบ</span></button>
                    </form>
                </div>
            </div>

            {{-- tabs --}}
            <nav class="flex gap-1 -mb-px overflow-x-auto">
                @foreach ($tabs as [$route, $text, $icon])
                    @php $active = request()->routeIs($route); @endphp
                    <a href="{{ route($route) }}" wire:navigate
                       class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition
                              {{ $active
                                  ? 'border-brand-600 text-brand-600 dark:text-brand-400'
                                  : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        <x-icon name="{{ $icon }}" class="w-4 h-4" /> {{ $text }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    @if (session('ok'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(()=>show=false, 4000)"
             class="max-w-5xl mx-auto px-4 mt-4">
            <div class="flex items-center gap-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400">
                <x-icon name="circle-check" class="w-5 h-5 shrink-0" /> <span class="flex-1">{{ session('ok') }}</span>
            </div>
        </div>
    @endif

    <main class="max-w-5xl mx-auto px-4 py-6">
        @if ($title)<h1 class="text-xl font-bold text-slate-900 dark:text-white mb-4">{{ $title }}</h1>@endif
        {{ $slot }}
    </main>
</div>
