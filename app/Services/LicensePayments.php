<?php
namespace App\Services;

use App\Models\Caseta;
use App\Models\CasetaLicense;
use App\Models\LicensePaymentCheck;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LicensePayments
{
    public const COLUMNS = ['caseta', 'licencia', 'gestion', 'estado', 'fuente', 'referencia', 'verificado_el', 'observaciones'];

    public static function validate(array $row): array
    {
        $data = Validator::make($row, [
            'caseta' => 'required|string|max:80', 'licencia' => 'required|string|max:80', 'gestion' => 'required|integer|between:2000,2200',
            'estado' => 'required|in:PAID,PENDING,EXEMPT,UNVERIFIED', 'fuente' => 'required|string|max:255',
            'referencia' => 'required|string|max:120', 'verificado_el' => 'required|date_format:Y-m-d H:i:s', 'observaciones' => 'nullable|string|max:2000',
        ])->validate();
        $date = Carbon::createFromFormat('!Y-m-d H:i:s', $data['verificado_el'], 'America/La_Paz')->utc();
        if ($date->gt(now('UTC'))) { throw ValidationException::withMessages(['verificado_el' => 'La verificación no puede tener fecha futura.']); }
        $license = CasetaLicense::whereHas('caseta', fn ($q) => $q->where('number', $data['caseta']))
            ->where('number', $data['licencia'])->where('year', $data['gestion'])->first();
        if (!$license) { throw ValidationException::withMessages(['licencia' => 'No existe la combinación de caseta, licencia y gestión. Registre y revise la licencia antes de importar.']); }
        return ['caseta_license_id' => $license->id, 'status' => $data['estado'], 'source' => $data['fuente'],
            'reference' => $data['referencia'], 'checked_at' => $date->format('Y-m-d H:i:s'), 'observations' => ($data['observaciones'] ?? '') === '' ? null : $data['observaciones']];
    }

    public static function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        try {
            $header = fgetcsv($handle, 0, ',', '"', '');
            if ($header) { $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]); }
            if ($header !== self::COLUMNS) { throw ValidationException::withMessages(['file' => 'Cabecera incorrecta. Use la plantilla CSV UTF-8 con comas.']); }
            $rows = [];
            $line = 1;
            while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $line++;
                if ($values === [null]) { continue; }
                if (count($rows) >= 1000) { throw ValidationException::withMessages(['file' => 'El máximo es 1000 filas por importación.']); }
                if (count($values) !== count(self::COLUMNS)) { throw ValidationException::withMessages(['file' => 'Número de columnas incorrecto en la fila '.$line.'.']); }
                foreach ($values as $value) {
                    if (!mb_check_encoding($value, 'UTF-8')) { throw ValidationException::withMessages(['file' => 'La fila '.$line.' no está codificada en UTF-8.']); }
                }
                try {
                    $input = array_map(fn ($value) => trim($value), array_combine(self::COLUMNS, $values));
                    $rows[] = ['input' => $input, 'data' => self::validate($input)];
                } catch (ValidationException $e) {
                    throw ValidationException::withMessages(['file' => 'Fila '.$line.': '.implode(' ', $e->validator->errors()->all())]);
                }
            }
            if (!$rows) { throw ValidationException::withMessages(['file' => 'El archivo no contiene verificaciones.']); }
            return $rows;
        } finally { fclose($handle); }
    }

    public static function record(array $data): bool
    {
        return DB::transaction(function () use ($data) {
            $license = CasetaLicense::findOrFail($data['caseta_license_id']);
            Caseta::lockForUpdate()->findOrFail($license->caseta_id);
            $license = CasetaLicense::lockForUpdate()->findOrFail($license->id);
            ksort($data);
            $fingerprint = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
            if (LicensePaymentCheck::where('fingerprint', $fingerprint)->exists()) { return false; }
            $actor = auth()->user();
            $check = LicensePaymentCheck::create($data + ['fingerprint' => $fingerprint, 'created_at' => now(),
                'actor_type' => $actor ? get_class($actor) : null, 'actor_id' => $actor?->getAuthIdentifier()]);
            $latest = $license->paymentChecks()->first();
            $license->update(['payment_status' => $latest->status]);
            CasetaAudit::record($license->caseta_id, 'PAYMENT_CHECK', 'Verificación de Recaudaciones: '.$check->label(),
                ['license_id' => $license->id, 'license_number' => $license->number, 'year' => $license->year,
                    'status' => $check->status, 'source' => $check->source, 'reference' => $check->reference,
                    'checked_at' => $check->checked_at->toIso8601String(), 'observations' => $check->observations]);
            return true;
        }, 3);
    }
}
