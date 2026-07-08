<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

// §17.24 — Cipta / naik taraf pengguna kepada superadmin.
class MakeSuperadmin extends Command
{
    protected $signature = 'diwan:make-superadmin {email} {--name=Superadmin} {--password=}';

    protected $description = 'Cipta atau naik taraf pengguna kepada superadmin platform';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->option('password');

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $user->name ?: $this->option('name');
        $user->is_superadmin = true;
        $user->is_active = true;
        if ($password) {
            $user->password = Hash::make($password);
        }
        $user->save();

        $this->info("Pengguna {$email} kini superadmin.");

        return self::SUCCESS;
    }
}
