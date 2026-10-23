<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:user {--role= : ADMIN, JEFE_UNIDAD, INSPECTOR_NOTIFICADOR o ASESOR_LEGAL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for user creation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $role = $this->option('role') ?: $this->choice('Perfil', ['ASESOR_LEGAL', 'INSPECTOR_NOTIFICADOR', 'JEFE_UNIDAD', 'ADMIN'], 0);
        if (!in_array($role, ['ADMIN', 'JEFE_UNIDAD', 'INSPECTOR_NOTIFICADOR', 'ASESOR_LEGAL'], true)) {
            $this->error('Perfil no válido.');
            return self::FAILURE;
        }
        $name = $this->ask('Nombre:');
        $username = $this->ask('Usuario:');
        $password = $this->secret('Password:');
        try{
            DB::table('users')->insert([
                'roles' => $role,
                'name' => $name,
                'username' => $username,
                'password' => Hash::make($password),
            ]);
            $this->info('Creado con exito!!!');
        }catch (\Exception $e){
            $this->error('No se pudo crear el usuario');
        }
    }
}
