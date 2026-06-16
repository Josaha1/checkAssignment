<div class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-brand-600 via-brand-600 to-violet-700 relative overflow-hidden">
    <div class="absolute -top-32 -right-20 w-96 h-96 rounded-full bg-white/10"></div>
    <div class="absolute -bottom-40 -left-24 w-[28rem] h-[28rem] rounded-full bg-white/10"></div>

    <div class="w-full max-w-sm relative">
        <div class="text-center mb-6">
            <span class="inline-grid place-items-center w-14 h-14 rounded-2xl bg-white/15 text-white mb-3"><x-icon name="cap" class="w-7 h-7" /></span>
            <h1 class="text-2xl font-bold text-white">ระบบส่งงานนักศึกษา</h1>
            <p class="text-brand-100 text-sm mt-1">เข้าสู่ระบบด้วยอีเมลสถาบันและรหัสผ่าน</p>
        </div>

        <form wire:submit="login" class="card card-pad space-y-4">
            <div>
                <label class="label">อีเมลสถาบัน</label>
                <div class="relative">
                    <x-icon name="users" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input type="email" wire:model="email" autofocus class="input pl-9" placeholder="เช่น name.abc@spulive.net">
                </div>
            </div>
            <div>
                <label class="label">รหัสผ่าน</label>
                <input type="password" wire:model="password" class="input" placeholder="รหัสผ่านตั้งต้น = รหัสนักศึกษา">
                <p class="text-xs text-slate-400 mt-1">ครั้งแรกใช้รหัสนักศึกษาเป็นรหัสผ่าน</p>
            </div>

            @error('email')<p class="flex items-center gap-1.5 text-sm text-rose-600"><x-icon name="alert" class="w-4 h-4" /> {{ $message }}</p>@enderror
            @error('password')<p class="flex items-center gap-1.5 text-sm text-rose-600"><x-icon name="alert" class="w-4 h-4" /> {{ $message }}</p>@enderror

            <button type="submit" class="btn-primary w-full">
                <span wire:loading.remove wire:target="login">เข้าสู่ระบบ</span>
                <span wire:loading wire:target="login">กำลังเข้าสู่ระบบ...</span>
            </button>
        </form>

        <p class="text-center text-brand-100 text-sm mt-5">
            <a href="{{ route('admin.login') }}" wire:navigate class="hover:text-white">สำหรับผู้ดูแลระบบ →</a>
        </p>
    </div>
</div>
