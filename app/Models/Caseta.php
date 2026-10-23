<?php

namespace App\Models;

use App\Notice;
use Illuminate\Database\Eloquent\Model;

class Caseta extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['lat' => 'float', 'lng' => 'float', 'accuracy' => 'float', 'located_at' => \App\Casts\UtcTimestamp::class, 'declared_no_license' => 'boolean', 'pending_retirement' => 'boolean'];

    public function licenses() { return $this->hasMany(CasetaLicense::class)->orderByDesc('expires_on'); }
    public function cases() { return $this->hasMany(ControlCase::class); }
    public function notices() { return $this->hasMany(Notice::class); }
    public function lastNotice() { return $this->hasOne(Notice::class)->ofMany(['notice_date' => 'max', 'id' => 'max']); }
    public function certificates() { return $this->hasMany(Certificate::class); }
    public function events() { return $this->hasMany(CasetaEvent::class)->latest('id'); }

    public function licenseStatus(): string
    {
        $today = today('America/La_Paz')->toDateString();
        $valid = $this->licenses->contains(fn ($license) => $license->issued_on->toDateString() <= $today && $license->expires_on->toDateString() >= $today);
        if ($valid) { return 'VIGENTE'; }
        if ($this->declared_no_license || $this->license_verification === 'NO_LICENSE') { return 'SIN LICENCIA'; }
        if ($this->licenses->isNotEmpty()) { return 'NO VIGENTE'; }
        return 'POR VERIFICAR';
    }

    public function controlState(): array
    {
        $case = $this->cases->where('status', 'OPEN')->sortByDesc('id')->first();
        $notice = $case ? $this->notices->where('control_case_id', $case->id)->sortByDesc('sequence')->first() : null;
        return $notice ? $notice->controlState() : ['color' => 'neutral', 'label' => 'Sin avisos activos', 'sequence' => 0];
    }

    public function licenseColor(): string
    {
        if ($this->pending_retirement || $this->declared_no_license) { return 'red'; }
        return match ($this->licenseStatus()) { 'VIGENTE' => 'white', 'NO VIGENTE', 'SIN LICENCIA' => 'red', default => 'neutral' };
    }
}
