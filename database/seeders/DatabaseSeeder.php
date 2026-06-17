<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // มีแค่บัญชีแอดมินตั้งต้น — ข้อมูลนักศึกษา/วิชา นำเข้าจากใบลงทะเบียน (Excel)
        User::updateOrCreate(
            ['email' => '6750245.st@spu.ac.th'],
            ['name' => 'ผู้ดูแลระบบ', 'password' => Hash::make('1750200051131')],
        );
    }
}
