<?php

namespace App\Services;

use App\Contracts\DriveStorage;

/** ใช้ใน test — ไม่เรียก Google จริง */
class FakeDriveStorage implements DriveStorage
{
    public array $uploaded = [];
    public array $paths = [];

    public function isConnected(): bool
    {
        return true;
    }

    public function connectedEmail(): ?string
    {
        return 'test@example.com';
    }

    public function ensureFolderPath(array $names): string
    {
        $path = implode('/', $names);
        $this->paths[] = $path;
        return 'folder-' . md5($path);
    }

    public function upload(string $localPath, string $name, string $mime, string $parentId): array
    {
        $id = 'file-' . md5($parentId . $name . count($this->uploaded));
        $this->uploaded[] = compact('name', 'mime', 'parentId') + ['id' => $id];
        return ['id' => $id, 'url' => 'https://drive.google.com/file/d/' . $id . '/view'];
    }

    public function delete(string $fileId): void
    {
        //
    }
}
