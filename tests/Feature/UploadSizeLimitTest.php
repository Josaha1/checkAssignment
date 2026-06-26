<?php

use App\Livewire\Student\Submit;

// กัน regression: Livewire temp-upload default = 12MB (12288) ซึ่งต่ำกว่าเพดานที่หน้าส่งงานโชว์
// ทำให้ไฟล์ใหญ่โดนปัด ("must not be greater than 12288 kilobytes") ก่อนถึง validate ของ app
it('เพดานอัปโหลด = 40MB และ Livewire temp-upload ตรงกับ Submit::MAX_KB', function () {
    $rules = config('livewire.temporary_file_upload.rules');

    expect(Submit::MAX_KB)->toBe(40960)                    // 40MB ตามที่ตั้งไว้
        ->and($rules)->toBeArray()
        ->and($rules)->toContain('max:' . Submit::MAX_KB)  // temp-upload ต้องตรงกับ validate ของ app
        ->and($rules)->not->toContain('max:12288');         // ต้องไม่ใช่ default 12MB อีกต่อไป
});
