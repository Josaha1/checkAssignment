<div class="min-h-screen grid lg:grid-cols-2">
    {{-- ฝั่งภาพ/แบรนด์ --}}
    <div class="hidden lg:flex flex-col justify-between p-12 bg-gradient-to-br from-brand-700 via-brand-600 to-violet-700 text-white relative overflow-hidden">
        <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-32 -left-16 w-80 h-80 rounded-full bg-white/10"></div>
        <div class="flex items-center gap-3 relative">
            <span class="grid place-items-center w-11 h-11 rounded-xl bg-white/15"><x-icon name="cap" class="w-6 h-6" /></span>
            <span class="font-bold text-lg">ระบบส่งงานนักศึกษา</span>
        </div>
        <div class="relative">
            <h2 class="text-3xl font-bold leading-snug">จัดการการส่งงาน<br>และให้คะแนน ครบในที่เดียว</h2>
            <p class="mt-3 text-brand-100">ตรวจตามรายวิชาและห้อง · ให้คะแนนรายชิ้นงาน · ส่งออก CSV</p>
        </div>
        <p class="relative text-brand-200 text-sm">สำหรับผู้ดูแลระบบ / อาจารย์</p>
    </div>

    {{-- ฟอร์ม --}}
    <div class="flex items-center justify-center p-6 bg-slate-100 dark:bg-slate-950">
        <div class="w-full max-w-sm">
            <div class="lg:hidden flex items-center gap-2 justify-center mb-6">
                <span class="grid place-items-center w-10 h-10 rounded-xl bg-brand-600 text-white"><x-icon name="cap" class="w-5 h-5" /></span>
                <span class="font-bold text-slate-900 dark:text-white">ระบบส่งงานนักศึกษา</span>
            </div>

            <div class="card card-pad">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">เข้าสู่ระบบผู้ดูแล</h1>
                <p class="text-sm text-slate-500 mt-1 mb-6">กรอกอีเมลและรหัสผ่านของคุณ</p>

                <form wire:submit="login" class="space-y-4">
                    <div>
                        <label class="label">อีเมล</label>
                        <input type="email" wire:model="email" autofocus class="input" placeholder="admin@example.com">
                    </div>
                    <div>
                        <label class="label">รหัสผ่าน</label>
                        <input type="password" wire:model="password" class="input" placeholder="••••••••">
                    </div>
                    @error('email')
                        <p class="flex items-center gap-1.5 text-sm text-rose-600"><x-icon name="alert" class="w-4 h-4" /> {{ $message }}</p>
                    @enderror
                    <button type="submit" class="btn-primary w-full">
                        <span wire:loading.remove wire:target="login">เข้าสู่ระบบ</span>
                        <span wire:loading wire:target="login">กำลังเข้าสู่ระบบ...</span>
                    </button>
                </form>
            </div>

            <p class="text-center text-sm text-slate-500 mt-6">
                <a href="{{ route('student.login') }}" wire:navigate class="font-medium text-brand-600 hover:text-brand-700">เข้าสำหรับนักศึกษา →</a>
            </p>
        </div>
    </div>
</div>
