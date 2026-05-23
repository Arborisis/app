<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsersCommand extends Command
{
    protected $signature = 'user:list 
                            {--limit=20 : Nombre max d\'utilisateurs}';

    protected $description = 'Lister les utilisateurs';

    public function handle(): int
    {
        $users = User::select('id', 'name', 'email', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($this->option('limit'))
            ->get();
        
        if ($users->isEmpty()) {
            $this->warn('Aucun utilisateur trouvé.');
            return 0;
        }
        
        $this->table(
            ['ID', 'Nom', 'Email', 'Créé le'],
            $users->map(fn($u) => [
                $u->id,
                $u->name,
                $u->email,
                $u->created_at->format('Y-m-d H:i'),
            ])->toArray()
        );
        
        return 0;
    }
}
