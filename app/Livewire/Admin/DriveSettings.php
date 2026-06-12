<?php

namespace App\Livewire\Admin;

use App\Contracts\DriveStorage;
use Livewire\Component;

class DriveSettings extends Component
{
    public function render()
    {
        $drive = app(DriveStorage::class);

        return view('livewire.admin.drive-settings', [
            'configured' => filled(config('services.google_drive.client_id'))
                && filled(config('services.google_drive.client_secret')),
            'connected' => $drive->isConnected(),
            'email' => $drive->connectedEmail(),
            'redirect' => config('services.google_drive.redirect'),
        ]);
    }
}
