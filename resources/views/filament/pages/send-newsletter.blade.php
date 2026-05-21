<x-filament-panels::page>
    <x-filament-panels::form wire:submit="send">
        {{ $this->form }}
    </x-filament-panels::form>

    <div class="mt-6 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Conseils de rédaction</h3>
        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
            <li>Gardez votre sujet concis et évocateur (moins de 60 caractères idéalement)</li>
            <li>Utilisez des paragraphes courts pour une meilleure lisibilité sur mobile</li>
            <li>N'oubliez pas d'inclure un appel à l'action clair</li>
            <li>Chaque e-mail inclut automatiquement un lien de désinscription</li>
        </ul>
    </div>
</x-filament-panels::page>
