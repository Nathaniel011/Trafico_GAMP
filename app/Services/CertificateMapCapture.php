<?php
namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Facades\Cache;

class CertificateMapCapture
{
    public function version(Certificate $certificate): string
    {
        return hash('sha256', json_encode([$certificate->id, $certificate->lat, $certificate->lng, (string) $certificate->updated_at]));
    }

    private function key(Certificate $certificate): string
    {
        $user = auth()->user();
        return 'certificate-map:'.hash('sha256', get_class($user).':'.$user->getAuthIdentifier()).':'.$this->version($certificate);
    }

    public function get(Certificate $certificate): ?string
    {
        return Cache::get($this->key($certificate));
    }

    public function put(Certificate $certificate, string $image): void
    {
        Cache::put($this->key($certificate), $image, now()->addHour());
    }
}
