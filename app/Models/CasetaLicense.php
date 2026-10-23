<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CasetaLicense extends Model
{
    public function caseta() { return $this->belongsTo(Caseta::class); }
    public function paymentChecks() { return $this->hasMany(LicensePaymentCheck::class)->orderByDesc('checked_at')->orderByDesc('id'); }
    protected $guarded = ['id'];
    protected $casts = ['issued_on' => 'date', 'expires_on' => 'date', 'caseta_id' => 'integer', 'year' => 'integer'];
}
