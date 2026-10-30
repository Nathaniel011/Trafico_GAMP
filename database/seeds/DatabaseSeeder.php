<?php

use App\User;
use App\Services\UserRoleRules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $created=DB::transaction(function () {
            // Initial installation only: updates must preserve existing accounts and passwords.
            if (UserRoleRules::lockUsers()->isNotEmpty()) return false;
            $admin=new User();
            $admin->name='Administrador';
            $admin->username='administrador';
            $admin->roles='ADMIN';
            $admin->password=Hash::make('administrador');
            $admin->save();
            return true;
        },3);
        $this->command?->info($created
            ? 'Se creó únicamente la cuenta inicial administrador con rol Administrador.'
            : 'La base ya contiene usuarios. Se conservaron las cuentas y contraseñas existentes.');
    }
}
