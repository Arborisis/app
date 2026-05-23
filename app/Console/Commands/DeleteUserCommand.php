<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class DeleteUserCommand extends Command
{
    protected $signature = 'user:delete 
                            {email : Email de l\'utilisateur à supprimer}
                            {--force : Supprimer sans confirmation}';

    protected $description = 'Supprimer un utilisateur par son email';

    public function handle(): int
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Utilisateur avec l'email {$email} non trouvé.");
            return 1;
        }
        
        $this->info("Utilisateur trouvé :");
        $this->info("  ID: {$user->id}");
        $this->info("  Nom: {$user->name}");
        $this->info("  Email: {$user->email}");
        $this->info("  Créé le: {$user->created_at}");
        
        if (!$this->option('force')) {
            if (!$this->confirm('Voulez-vous vraiment supprimer cet utilisateur ?')) {
                $this->info('Suppression annulée.');
                return 0;
            }
        }
        
        try {
            $user->delete();
            $this->info("✅ Utilisateur {$email} supprimé avec succès.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Erreur lors de la suppression : {$e->getMessage()}");
            return 1;
        }
    }
}
