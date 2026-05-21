<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Logs standard (out.log)
            </x-slot>

            <div class="max-h-96 overflow-y-auto rounded bg-gray-900 p-4 font-mono text-xs text-green-400">
                @forelse ($logs as $line)
                    <div>{{ trim($line) }}</div>
                @empty
                    <div class="text-gray-500">Aucun log disponible.</div>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Erreurs (err.log)
            </x-slot>

            <div class="max-h-96 overflow-y-auto rounded bg-gray-900 p-4 font-mono text-xs text-red-400">
                @forelse ($errors as $line)
                    <div>{{ trim($line) }}</div>
                @empty
                    <div class="text-gray-500">Aucune erreur disponible.</div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
