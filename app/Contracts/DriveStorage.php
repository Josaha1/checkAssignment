<?php

namespace App\Contracts;

interface DriveStorage
{
    /** เชื่อมต่อ Google Drive แล้วหรือยัง */
    public function isConnected(): bool;

    /** อีเมลบัญชีที่เชื่อมไว้ */
    public function connectedEmail(): ?string;

    /** สร้าง/หาโฟลเดอร์ตาม path (เช่น [วิชา, ห้อง, งาน, รหัสนศ.]) คืน folder id ปลายทาง */
    public function ensureFolderPath(array $names): string;

    /** อัปโหลดไฟล์เข้าโฟลเดอร์ คืน ['id' => ..., 'url' => webViewLink] */
    public function upload(string $localPath, string $name, string $mime, string $parentId): array;

    /** ลบไฟล์บน Drive (best-effort) */
    public function delete(string $fileId): void;
}
