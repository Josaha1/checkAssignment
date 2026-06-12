<div>
    <x-admin-shell title="เชื่อมต่อ Google Drive">
        <div class="max-w-2xl space-y-6">
            <div class="card card-pad">
                <div class="flex items-center gap-3">
                    <span class="grid place-items-center w-11 h-11 rounded-xl {{ $connected ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800' }}">
                        <x-icon name="{{ $connected ? 'circle-check' : 'link' }}" class="w-5 h-5" />
                    </span>
                    <div class="flex-1">
                        <h2 class="font-semibold text-slate-900 dark:text-white">สถานะการเชื่อมต่อ</h2>
                        @if ($connected)
                            <p class="text-sm text-emerald-600">เชื่อมต่อแล้ว · {{ $email ?? 'Google Drive' }}</p>
                        @else
                            <p class="text-sm text-slate-500">ยังไม่ได้เชื่อมต่อ — นักศึกษาจะอัปโหลดไฟล์ไม่ได้</p>
                        @endif
                    </div>
                </div>

                <div class="mt-5 flex gap-2">
                    @if (! $configured)
                        <div class="badge-amber"><x-icon name="alert" class="w-4 h-4" /> ยังไม่ตั้ง GOOGLE_CLIENT_ID / SECRET ใน .env</div>
                    @elseif ($connected)
                        <a href="{{ route('admin.google.connect') }}" class="btn-ghost"><x-icon name="link" class="w-4 h-4" /> เชื่อมบัญชีใหม่</a>
                        <form method="POST" action="{{ route('admin.google.disconnect') }}">
                            @csrf
                            <button class="btn-danger" wire:confirm="ตัดการเชื่อมต่อ?"><x-icon name="x" class="w-4 h-4" /> ตัดการเชื่อมต่อ</button>
                        </form>
                    @else
                        <a href="{{ route('admin.google.connect') }}" class="btn-primary"><x-icon name="link" class="w-4 h-4" /> เชื่อมต่อ Google Drive</a>
                    @endif
                </div>
            </div>

            <div class="card card-pad text-sm text-slate-600 dark:text-slate-300 space-y-2">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">วิธีตั้งค่า (ครั้งเดียว)</h3>
                <ol class="list-decimal ml-5 space-y-1.5">
                    <li>Google Cloud Console → สร้าง OAuth Client (Web application)</li>
                    <li>ใส่ Authorized redirect URI: <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 break-all">{{ $redirect }}</code></li>
                    <li>เปิดใช้ <b>Google Drive API</b> ในโปรเจค</li>
                    <li>ตั้ง <code class="px-1 rounded bg-slate-100 dark:bg-slate-800">GOOGLE_CLIENT_ID</code>, <code class="px-1 rounded bg-slate-100 dark:bg-slate-800">GOOGLE_CLIENT_SECRET</code> ใน .env</li>
                    <li>กดปุ่ม "เชื่อมต่อ" แล้ว login ด้วยบัญชีมหาลัย (ที่มี 1TB)</li>
                </ol>
                <p class="text-xs text-slate-400">ไฟล์ทั้งหมดจะถูกเก็บใน Drive ของบัญชีที่เชื่อม · โครงสร้าง: วิชา / ห้อง / งาน / รหัสนักศึกษา</p>
            </div>
        </div>
    </x-admin-shell>
</div>
