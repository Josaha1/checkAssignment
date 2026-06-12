<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;

class GoogleDriveController extends Controller
{
    public function connect(GoogleDriveService $drive)
    {
        return redirect()->away($drive->authUrl());
    }

    public function callback(Request $request, GoogleDriveService $drive)
    {
        if ($request->filled('error')) {
            return redirect()->route('admin.drive')->with('ok', 'ยกเลิกการเชื่อมต่อ Google');
        }
        try {
            $drive->handleCallback($request->query('code', ''));
            return redirect()->route('admin.drive')->with('ok', 'เชื่อมต่อ Google Drive สำเร็จ');
        } catch (\Throwable $e) {
            return redirect()->route('admin.drive')->with('ok', 'เชื่อมต่อไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function disconnect(GoogleDriveService $drive)
    {
        $drive->disconnect();
        return redirect()->route('admin.drive')->with('ok', 'ตัดการเชื่อมต่อ Google Drive แล้ว');
    }
}
