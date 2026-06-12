<?php

namespace App\Services;

use App\Contracts\DriveStorage;
use App\Models\AppSetting;
use App\Models\DriveFolder;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Oauth2;

class GoogleDriveService implements DriveStorage
{
    private const SCOPES = [Drive::DRIVE_FILE, 'openid', 'email'];

    // ---------- OAuth ----------

    private function baseClient(): Client
    {
        $c = new Client();
        $c->setClientId(config('services.google_drive.client_id'));
        $c->setClientSecret(config('services.google_drive.client_secret'));
        $c->setRedirectUri(config('services.google_drive.redirect'));
        $c->setScopes(self::SCOPES);
        $c->setAccessType('offline');
        $c->setPrompt('consent'); // บังคับให้ได้ refresh token
        return $c;
    }

    public function authUrl(): string
    {
        return $this->baseClient()->createAuthUrl();
    }

    public function handleCallback(string $code): void
    {
        $client = $this->baseClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            throw new \RuntimeException('เชื่อมต่อ Google ไม่สำเร็จ: ' . $token['error']);
        }
        AppSetting::put('google_token', json_encode($token));

        // ดึงอีเมลบัญชีที่เชื่อม
        $client->setAccessToken($token);
        try {
            $email = (new Oauth2($client))->userinfo->get()->getEmail();
            AppSetting::put('google_email', $email);
        } catch (\Throwable) {
            // ไม่เป็นไรถ้าดึงอีเมลไม่ได้
        }
        // reset cache โฟลเดอร์ root เผื่อเปลี่ยนบัญชี
        AppSetting::forget('google_root_folder_id');
    }

    public function disconnect(): void
    {
        AppSetting::forget('google_token');
        AppSetting::forget('google_email');
        AppSetting::forget('google_root_folder_id');
        DriveFolder::query()->delete();
    }

    public function isConnected(): bool
    {
        return ! empty(AppSetting::get('google_token'));
    }

    public function connectedEmail(): ?string
    {
        return AppSetting::get('google_email');
    }

    // ---------- authorized client ----------

    private function authorizedClient(): Client
    {
        $raw = AppSetting::get('google_token');
        if (! $raw) {
            throw new \RuntimeException('ยังไม่ได้เชื่อมต่อ Google Drive');
        }
        $client = $this->baseClient();
        $token = json_decode($raw, true);
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $refresh = $client->getRefreshToken();
            if (! $refresh) {
                throw new \RuntimeException('Google token หมดอายุและไม่มี refresh token — เชื่อมต่อใหม่');
            }
            $new = $client->fetchAccessTokenWithRefreshToken($refresh);
            if (isset($new['error'])) {
                throw new \RuntimeException('ต่ออายุ token ไม่สำเร็จ: ' . $new['error']);
            }
            // เก็บ refresh token เดิมไว้ (บางครั้ง response ใหม่ไม่ส่งมาด้วย)
            $merged = array_merge($token, $new);
            AppSetting::put('google_token', json_encode($merged));
            $client->setAccessToken($merged);
        }

        return $client;
    }

    private function drive(): Drive
    {
        return new Drive($this->authorizedClient());
    }

    // ---------- folders ----------

    private function rootFolderId(): string
    {
        $cached = AppSetting::get('google_root_folder_id');
        if ($cached) {
            return $cached;
        }
        $id = $this->ensureFolder(config('services.google_drive.root_folder_name'), 'root');
        AppSetting::put('google_root_folder_id', $id);
        return $id;
    }

    public function ensureFolderPath(array $names): string
    {
        $parent = $this->rootFolderId();
        foreach ($names as $name) {
            $parent = $this->ensureFolder((string) $name, $parent);
        }
        return $parent;
    }

    private function ensureFolder(string $name, string $parentId): string
    {
        $key = $parentId . '|' . $name;
        if ($hit = DriveFolder::where('cache_key', $key)->first()) {
            return $hit->folder_id;
        }

        $drive = $this->drive();
        $safe = str_replace("'", "\\'", $name);
        $q = "mimeType='application/vnd.google-apps.folder' and name='{$safe}' "
            . "and '{$parentId}' in parents and trashed=false";
        $found = $drive->files->listFiles([
            'q' => $q, 'fields' => 'files(id)', 'pageSize' => 1,
        ])->getFiles();

        if (count($found) > 0) {
            $id = $found[0]->getId();
        } else {
            $folder = new DriveFile([
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentId],
            ]);
            $id = $drive->files->create($folder, ['fields' => 'id'])->getId();
        }

        DriveFolder::create(['cache_key' => $key, 'folder_id' => $id]);
        return $id;
    }

    // ---------- upload ----------

    public function upload(string $localPath, string $name, string $mime, string $parentId): array
    {
        $drive = $this->drive();
        $file = new DriveFile(['name' => $name, 'parents' => [$parentId]]);
        $created = $drive->files->create($file, [
            'data' => file_get_contents($localPath),
            'mimeType' => $mime,
            'uploadType' => 'multipart',
            'fields' => 'id,webViewLink',
        ]);

        return ['id' => $created->getId(), 'url' => $created->getWebViewLink()];
    }

    public function delete(string $fileId): void
    {
        try {
            $this->drive()->files->delete($fileId);
        } catch (\Throwable) {
            // best-effort
        }
    }
}
