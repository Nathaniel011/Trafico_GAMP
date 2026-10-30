<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Notice extends Model
{
    protected $casts = ['notice_date' => 'datetime', 'presentation_date' => 'date', 'deadline_at' => \App\Casts\UtcTimestamp::class, 'retirement' => 'boolean', 'sequence' => 'integer', 'year' => 'integer', 'located_at' => \App\Casts\UtcTimestamp::class];
    public function getNoticeDateAttribute($value)
    {
        return $value === null ? null : \Carbon\Carbon::parse($value, !empty($this->attributes['year']) ? 'UTC' : config('app.timezone'));
    }
    public function caseta() { return $this->belongsTo(\App\Models\Caseta::class); }
    public function controlCase() { return $this->belongsTo(\App\Models\ControlCase::class); }
    public function controlState(): array
    {
        if (!$this->sequence) { return ['color' => 'neutral', 'label' => 'Histórico sin secuencia verificada', 'sequence' => 0]; }
        if ($this->sequence === 3 && $this->deadline_at && now()->greaterThanOrEqualTo($this->deadline_at)) {
            return ['color' => 'red', 'label' => 'Vencido · seguimiento de retiro/sanción', 'sequence' => 3];
        }
        return ['color' => [1 => 'yellow', 2 => 'blue', 3 => 'green'][$this->sequence], 'label' => 'Notificación '.$this->sequence, 'sequence' => $this->sequence];
    }
    public function getFormattedDateAttribute()
    {
        return $this->notice_date?->copy()->timezone($this->year ? 'America/La_Paz' : config('app.timezone'))->format('d/m/Y H:i') ?? '...';
    }
}
