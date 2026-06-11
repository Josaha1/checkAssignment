<div class="min-h-screen flex items-center justify-center bg-slate-900 px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-white">{{ config('app.name') }}</h1>
            <p class="text-slate-400 text-sm mt-1">ผู้ดูแลระบบ</p>
        </div>

        <form wire:submit="login" class="bg-white rounded-2xl shadow-xl p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">อีเมล</label>
                <input type="email" wire:model="email" autofocus
                       class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่าน</label>
                <input type="password" wire:model="password"
                       class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            @error('email') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror

            <button type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 rounded-lg transition">
                เข้าสู่ระบบ
            </button>
        </form>
        <p class="text-center text-slate-500 text-xs mt-4">
            <a href="{{ route('student.login') }}" class="hover:text-slate-300">สำหรับนักศึกษา</a>
        </p>
    </div>
</div>
