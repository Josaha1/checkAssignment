<div>
    <x-admin-shell title="นักศึกษา">
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="space-y-6 lg:sticky lg:top-24 h-fit">
                <form wire:submit="save" class="card card-pad space-y-4">
                    <div class="flex items-center gap-2">
                        <x-icon name="users" class="w-5 h-5 text-brand-600" />
                        <h2 class="font-semibold text-slate-800 dark:text-slate-100">{{ $editingId ? 'แก้ไขนักศึกษา' : 'เพิ่มนักศึกษา' }}</h2>
                    </div>
                    <div>
                        <label class="label">รหัสนักศึกษา</label>
                        <input type="text" wire:model="student_code" class="input">
                        @error('student_code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">ชื่อ-นามสกุล</label>
                        <input type="text" wire:model="full_name" class="input">
                        @error('full_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">อีเมลสถาบัน</label>
                        <input type="email" wire:model="email" class="input" placeholder="name.abc@spulive.net">
                        @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">รหัสผ่าน <span class="text-slate-400 font-normal">({{ $editingId ? 'เว้นว่าง = คงเดิม' : 'เว้นว่าง = รหัสนักศึกษา' }})</span></label>
                        <input type="text" wire:model="password" class="input" placeholder="••••••">
                        @error('password') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">คณะ</label>
                            <input type="text" wire:model="faculty" class="input">
                        </div>
                        <div>
                            <label class="label">สาขา</label>
                            <input type="text" wire:model="major" class="input">
                        </div>
                        <div>
                            <label class="label">รอบ/หลักสูตร</label>
                            <input type="text" wire:model="program" class="input">
                        </div>
                        <div>
                            <label class="label">กลุ่มเรียน</label>
                            <input type="text" wire:model="study_group" class="input">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button class="btn-primary flex-1"><x-icon name="check" class="w-4 h-4" /> บันทึก</button>
                        @if ($editingId)<button type="button" wire:click="cancel" class="btn-ghost">ยกเลิก</button>@endif
                    </div>
                </form>

                <form wire:submit="import" class="card card-pad space-y-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="upload" class="w-5 h-5 text-emerald-600" />
                        <h2 class="font-semibold text-slate-800 dark:text-slate-100">นำเข้าใบลงทะเบียน (Excel)</h2>
                    </div>
                    <p class="text-xs text-slate-500">ไฟล์ .xlsx 1 ไฟล์ = 1 วิชา — ระบบอ่านชื่อวิชาจากหัวกระดาษ สร้างวิชาและลงทะเบียนนักศึกษาให้อัตโนมัติ (รหัสผ่านตั้งต้น = รหัสนักศึกษา)</p>
                    <x-file-dropzone model="file" accent="emerald" accept=".xlsx,.xls" hint="ไฟล์ .xlsx หรือ .xls (1 ไฟล์ = 1 วิชา)" />
                    @error('file') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    <button class="btn-success w-full" wire:loading.attr="disabled" wire:target="import,file">
                        <span wire:loading.remove wire:target="import"><x-icon name="upload" class="w-4 h-4" /> นำเข้า</span>
                        <span wire:loading wire:target="import">กำลังนำเข้า...</span>
                    </button>
                    @if ($importResult)
                        <p class="text-xs text-emerald-600">วิชา {{ $importResult['subject'] }} — เพิ่ม {{ $importResult['created'] }} · แก้ไข {{ $importResult['updated'] }} · ผิดพลาด {{ $importResult['failed'] }}</p>
                    @endif
                </form>
            </div>

            <div class="lg:col-span-2 card overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="relative">
                        <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="ค้นหารหัส / ชื่อ / อีเมล" class="input pl-9">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <x-th-sort column="student_code" :sort-by="$sortBy" :sort-dir="$sortDir">รหัส</x-th-sort>
                                <x-th-sort column="full_name" :sort-by="$sortBy" :sort-dir="$sortDir">ชื่อ-นามสกุล</x-th-sort>
                                <x-th-sort column="study_group" :sort-by="$sortBy" :sort-dir="$sortDir">กลุ่ม</x-th-sort>
                                <th class="th text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($students as $s)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="td font-medium text-slate-900 dark:text-white">{{ $s->student_code }}</td>
                                    <td class="td">
                                        <p>{{ $s->full_name }}</p>
                                        <p class="text-xs text-slate-400">{{ $s->email }}</p>
                                    </td>
                                    <td class="td"><span class="badge-slate">{{ $s->study_group ?? '—' }}</span></td>
                                    <td class="td text-right whitespace-nowrap">
                                        <button wire:click="edit({{ $s->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="w-4 h-4" /></button>
                                        <button wire:click="delete({{ $s->id }})" wire:confirm="ลบนักศึกษาคนนี้?" class="btn-danger btn-sm"><x-icon name="trash" class="w-4 h-4" /></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="td text-center text-slate-400 py-10">ไม่พบนักศึกษา</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $students->links() }}</div>
            </div>
        </div>
    </x-admin-shell>
</div>
