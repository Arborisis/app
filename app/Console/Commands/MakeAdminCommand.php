<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'user:make-admin {email : Email de l\'utilisateur}';

    protected $description = 'Promouvoir un utilisateur en administrateur';

    public function handle(): int
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Utilisateur avec l'email {$email} non trouvé.");
            return 1;
        }
        
        $this->info("Utilisateur trouvé : {$user->name} ({$user->email})");
        $this->info("Rôle actuel : {$user->role->label()}");
        
        if ($user->role === UserRole::Admin) {
            $this->warn('Cet utilisateur est déjà administrateur !');
            return 0;
        }
        
        $user->role = UserRole::Admin;
        $user->save();
        
        $this->info("✅ {$user->name} est maintenant administrateur !");
        
        return 0;
    }
}
