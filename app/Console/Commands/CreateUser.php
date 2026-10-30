<?php
namespace App\Console\Commands;

use App\User;
use App\Services\UserRoleRules;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateUser extends Command
{
    protected $signature='create:user {--role= : ADMIN, JEFE_UNIDAD, INSPECTOR_NOTIFICADOR o ASESOR_LEGAL}';
    protected $description='Crear una cuenta web desde la consola del servidor';

    public function handle()
    {
        $role=$this->option('role') ?: $this->choice('Perfil',['ASESOR_LEGAL','INSPECTOR_NOTIFICADOR','JEFE_UNIDAD','ADMIN'],0);
        if (!in_array($role,['ADMIN','JEFE_UNIDAD','INSPECTOR_NOTIFICADOR','ASESOR_LEGAL'],true)) {
            $this->error('Perfil no válido.'); return self::FAILURE;
        }
        try {
            UserRoleRules::assertAssignment(User::get(),$role);
            $name=$this->ask('Nombre:');
            $username=$this->ask('Usuario:');
            $password=$this->secret('Password:');
            DB::transaction(function () use ($role,$name,$username,$password) {
                UserRoleRules::assertAssignment(UserRoleRules::lockUsers(),$role);
                $user=new User();
                $user->roles=$role; $user->name=$name; $user->username=$username;
                $user->password=Hash::make($password); $user->save();
            },3);
            $this->info('Creado con exito!!!');
            return self::SUCCESS;
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->first());
        } catch (\Exception $exception) {
            $this->error('No se pudo crear el usuario.');
        }
        return self::FAILURE;
    }
}
