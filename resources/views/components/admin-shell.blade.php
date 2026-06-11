@props(['title' => 'แดชบอร์ด'])

@php
    $nav = [
        ['route' => 'admin.dashboard', 'label' => 'แดชบอร์ด', 'icon' => '📊'],
        ['route' => 'admin.grading',   'label' => 'ตรวจ/ให้คะแนน', 'icon' => '✓'],
        ['route' => 'admin.subjects',  'label' => 'รายวิชา', 'icon' => '📚'],
        ['route' => 'admin.rooms',     'label' => 'ห้องเรียน', 'icon' => '🏫'],
        ['route' => 'admin.students',  'label' => 'นักศึกษา', 'icon' => '👤'],
    ];
@endphp

<div class="min-h-full flex">
    <aside class="hidden md:flex md:w-64 flex-col bg-slate-900 text-slate-300">
        <div class="px-6 py-5 text-white font-semibold text-lg border-b border-slate-800">
            {{ config('app.name') }}
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs($item['route']) ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <span>{{ $item['icon'] }}</span>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="px-3 py-4 border-t border-slate-800">
            <div class="px-3 pb-2 text-xs text-slate-500">{{ auth('web')->user()?->name }}</div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-slate-800 hover:text-white">
                    ออกจากระบบ
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold text-slate-900">{{ $title }}</h1>
            <a href="{{ route('admin.dashboard') }}" class="md:hidden text-sm text-indigo-600">เมนู</a>
        </header>

        @if (session('ok'))
            <div class="mx-6 mt-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif

        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </div>
</div>
