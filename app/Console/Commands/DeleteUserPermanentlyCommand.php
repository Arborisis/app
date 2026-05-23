<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeleteUserPermanentlyCommand extends Command
{
    protected $signature = 'user:delete-permanently 
                            {email : Email de l\'utilisateur à supprimer définitivement}
                            {--force : Supprimer sans confirmation}';

    protected $description = 'Supprimer définitivement un utilisateur (hard delete)';

    public function handle(): int
    {
        $email = $this->argument('email');
        
        $user = User::withTrashed()->where('email', $email)->first();
        
        if (!$user) {
            $this->error("Utilisateur avec l'email {$email} non trouvé.");
            return 1;
        }
        
        $this->info("Utilisateur trouvé :");
        $this->info("  ID: {$user->id}");
        $this->info("  Nom: {$user->name}");
        $this->info("  Email: {$user->email}");
        $this->info("  Créé le: {$user->created_at}");
        
        if ($user->trashed()) {
            $this->warn("  ⚠️  Cet utilisateur est déjà soft-deleted");
        }
        
        if (!$this->option('force')) {
            if (!$this->confirm('Voulez-vous vraiment supprimer DÉFINITIVEMENT cet utilisateur ? Cette action est irréversible !')) {
                $this->info('Suppression annulée.');
                return 0;
            }
        }
        
        try {
            $user->forceDelete();
            $this->info("✅ Utilisateur {$email} supprimé définitivement.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Erreur lors de la suppression : {$e->getMessage()}");
            return 1;
        }
    }
}
