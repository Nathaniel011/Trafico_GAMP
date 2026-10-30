<?php
namespace App\Providers;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];
    public function boot()
    {
        $this->registerPolicies();
        $has = static function ($user, array $allowed): bool {
            if ($user instanceof \App\Models\Inspector) {
                return (bool) $user->enabled && in_array('INSPECTOR_NOTIFICADOR', $allowed, true);
            }
            return count(array_intersect($allowed, preg_split('/[\s,;|]+/', (string) $user->roles))) > 0;
        };
        Gate::define('edit-certificates', fn ($user) => $has($user, ['ADMIN', 'JEFE_UNIDAD']));
        Gate::define('field-work', fn ($user) => $has($user, ['ADMIN', 'JEFE_UNIDAD', 'INSPECTOR_NOTIFICADOR']));
        Gate::define('manage-system', fn ($user) => $has($user, ['ADMIN', 'JEFE_UNIDAD']));
    }
}
