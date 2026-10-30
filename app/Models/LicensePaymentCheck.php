<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicensePaymentCheck extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['checked_at' => \App\Casts\UtcTimestamp::class, 'created_at' => 'datetime'];

    public function label(): string
    {
        return match ($this->status) {
            'PAID' => 'Pagado', 'PENDING' => 'Pago pendiente', 'EXEMPT' => 'Exento', default => 'Por verificar',
        };
    }
}
