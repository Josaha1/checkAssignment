<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-600 to-violet-700 px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-white">{{ config('app.name') }}</h1>
            <p class="text-indigo-100 text-sm mt-1">เข้าสู่ระบบสำหรับนักศึกษา</p>
        </div>

        <form wire:submit="login" class="bg-white rounded-2xl shadow-xl p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">รหัสนักศึกษา</label>
                <input type="text" wire:model="student_code" autofocus
                       class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                       placeholder="เช่น 670012345">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">วันเกิด (รหัสผ่าน)</label>
                <input type="date" wire:model="birthdate"
                       class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                <p class="text-xs text-slate-400 mt-1">วัน/เดือน/ปี ค.ศ. ตามทะเบียน</p>
            </div>

            @error('student_code')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
            @error('birthdate')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror

            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 rounded-lg transition">
                <span wire:loading.remove wire:target="login">เข้าสู่ระบบ</span>
                <span wire:loading wire:target="login">กำลังเข้าสู่ระบบ...</span>
            </button>
        </form>

        <p class="text-center text-indigo-200 text-xs mt-4">
            <a href="{{ route('admin.login') }}" class="hover:text-white">สำหรับผู้ดูแลระบบ</a>
        </p>
    </div>
</div>
