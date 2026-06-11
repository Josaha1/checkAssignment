<div>
    <x-admin-shell title="อาจารย์ / ผู้ดูแล">
        <div class="grid lg:grid-cols-3 gap-6">
            <form wire:submit="save" class="card card-pad space-y-4 h-fit lg:sticky lg:top-24">
                <div class="flex items-center gap-2">
                    <x-icon name="user-cog" class="w-5 h-5 text-brand-600" />
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100">{{ $editingId ? 'แก้ไขบัญชี' : 'เพิ่มผู้ดูแลใหม่' }}</h2>
                </div>
                <div>
                    <label class="label">ชื่อ</label>
                    <input type="text" wire:model="name" class="input">
                    @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">อีเมล</label>
                    <input type="email" wire:model="email" class="input">
                    @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">รหัสผ่าน @if ($editingId)<span class="text-slate-400 font-normal">(เว้นว่าง = ไม่เปลี่ยน)</span>@endif</label>
                    <input type="password" wire:model="password" class="input" placeholder="••••••••">
                    @error('password') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button class="btn-primary flex-1"><x-icon name="check" class="w-4 h-4" /> บันทึก</button>
                    @if ($editingId)<button type="button" wire:click="cancel" class="btn-ghost">ยกเลิก</button>@endif
                </div>
            </form>

            <div class="lg:col-span-2 card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr><th class="th">ชื่อ</th><th class="th">อีเมล</th><th class="th text-right">จัดการ</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($admins as $u)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="td">
                                        <div class="flex items-center gap-2.5">
                                            <span class="grid place-items-center w-8 h-8 rounded-full bg-brand-600 text-white text-xs font-bold">{{ mb_substr($u->name, 0, 1) }}</span>
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $u->name }}</span>
                                            @if ($u->id === $currentId)<span class="badge-brand">คุณ</span>@endif
                                        </div>
                                    </td>
                                    <td class="td">{{ $u->email }}</td>
                                    <td class="td text-right whitespace-nowrap">
                                        <button wire:click="edit({{ $u->id }})" class="btn-ghost btn-sm"><x-icon name="pencil" class="w-4 h-4" /></button>
                                        @if ($u->id !== $currentId)
                                            <button wire:click="delete({{ $u->id }})" wire:confirm="ลบบัญชีนี้?" class="btn-danger btn-sm"><x-icon name="trash" class="w-4 h-4" /></button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-admin-shell>
</div>
