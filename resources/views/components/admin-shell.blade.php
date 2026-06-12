@props(['title' => 'แดชบอร์ด'])

@php
    $ungraded = \App\Models\Submission::whereNull('score')->count();
    $groups = [
        'ภาพรวม' => [
            ['admin.dashboard', 'แดชบอร์ด', 'dashboard'],
        ],
        'งาน' => [
            ['admin.grading',     'ตรวจ / ให้คะแนน', 'check-square'],
            ['admin.submissions', 'สถานะการส่ง', 'inbox'],
            ['admin.reports',     'ศูนย์รายงาน', 'report'],
        ],
        'ข้อมูล' => [
            ['admin.subjects', 'รายวิชา', 'book'],
            ['admin.rooms',    'ห้องเรียน', 'school'],
            ['admin.students', 'นักศึกษา', 'users'],
        ],
        'ระบบ' => [
            ['admin.admins', 'อาจารย์ / ผู้ดูแล', 'user-cog'],
            ['admin.drive', 'เชื่อมต่อ Drive', 'link'],
        ],
    ];
@endphp

<div x-data="{ sidebar: false }">
    {{-- overlay มือถือ --}}
    <div x-show="sidebar" x-transition.opacity @click="sidebar=false"
         class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" style="display:none"></div>

    {{-- Sidebar (fixed — กันพลาด layout collapse) --}}
    <aside :class="{ 'translate-x-0': sidebar }"
           class="fixed inset-y-0 left-0 z-40 w-72 transform -translate-x-full lg:translate-x-0 transition-transform
                  bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col">
        <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-200 dark:border-slate-800">
            <span class="grid place-items-center w-9 h-9 rounded-xl bg-brand-600 text-white">
                <x-icon name="cap" class="w-5 h-5" />
            </span>
            <div class="leading-tight">
                <p class="font-bold text-slate-900 dark:text-white text-sm">ระบบส่งงาน</p>
                <p class="text-[11px] text-slate-400">Student Submission</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5">
            @foreach ($groups as $label => $items)
                <div>
                    <p class="px-3 mb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</p>
                    <div class="space-y-0.5">
                        @foreach ($items as [$route, $text, $icon])
                            @php $active = request()->routeIs($route); @endphp
                            <a href="{{ route($route) }}" wire:navigate
                               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                                      {{ $active
                                          ? 'bg-brand-600 text-white shadow-sm'
                                          : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                <x-icon :name="$icon" class="w-[18px] h-[18px] {{ $active ? '' : 'text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-200' }}" />
                                <span class="flex-1">{{ $text }}</span>
                                @if ($route === 'admin.grading' && $ungraded > 0)
                                    <span class="badge {{ $active ? 'bg-white/20 text-white' : 'badge-amber' }}">{{ $ungraded }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </div>

    {{-- Main — เว้นซ้าย 18rem ให้พ้น fixed sidebar บนจอใหญ่ --}}
    <div class="lg:pl-72 flex flex-col">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 h-16 flex items-center gap-3 px-4 sm:px-6
                       bg-white/80 dark:bg-slate-900/80 backdrop-blur border-b border-slate-200 dark:border-slate-800">
            <button @click="sidebar=true" class="lg:hidden btn-ghost btn-sm !p-2"><x-icon name="menu" class="w-5 h-5" /></button>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white truncate">{{ $title }}</h1>

            <div class="ml-auto flex items-center gap-2">
                {{-- dark toggle --}}
                <button @click="document.documentElement.classList.toggle('dark'); localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light'"
                        class="btn-ghost btn-sm !p-2" title="สลับโหมดมืด/สว่าง">
                    <x-icon name="sun" class="w-5 h-5 hidden dark:block" />
                    <x-icon name="moon" class="w-5 h-5 dark:hidden" />
                </button>

                {{-- bell --}}
                <a href="{{ route('admin.grading') }}" wire:navigate class="relative btn-ghost btn-sm !p-2" title="งานรอตรวจ">
                    <x-icon name="bell" class="w-5 h-5" />
                    @if ($ungraded > 0)
                        <span class="absolute -top-0.5 -right-0.5 grid place-items-center min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold">{{ $ungraded }}</span>
                    @endif
                </a>

                {{-- user dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open=!open" class="flex items-center gap-2 btn-ghost btn-sm">
                        <span class="grid place-items-center w-7 h-7 rounded-full bg-brand-600 text-white text-xs font-bold">
                            {{ mb_substr(auth('web')->user()?->name ?? 'A', 0, 1) }}
                        </span>
                        <span class="hidden sm:block max-w-[120px] truncate">{{ auth('web')->user()?->name }}</span>
                        <x-icon name="chevron-down" class="w-4 h-4" />
                    </button>
                    <div x-show="open" @click.outside="open=false" x-transition style="display:none"
                         class="absolute right-0 mt-2 w-52 card p-1.5 z-30">
                        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate">{{ auth('web')->user()?->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ auth('web')->user()?->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}" class="mt-1">
                            @csrf
                            <button class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                                <x-icon name="logout" class="w-4 h-4" /> ออกจากระบบ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- flash --}}
        @if (session('ok'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(()=>show=false, 4000)"
                 class="mx-4 sm:mx-6 mt-4 flex items-center gap-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400">
                <x-icon name="circle-check" class="w-5 h-5 shrink-0" /> <span class="flex-1">{{ session('ok') }}</span>
                <button @click="show=false"><x-icon name="x" class="w-4 h-4" /></button>
            </div>
        @endif

        <main class="flex-1 p-4 sm:p-6">
            {{ $slot }}
        </main>
    </div>
</div>
