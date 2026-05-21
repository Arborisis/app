<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] },
});

const search = ref('');
const sortKey = ref('recorded_at');
const sortAsc = ref(false);

const filtered = computed(() => {
    let rows = props.data;
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        rows = rows.filter(r =>
            (r.title?.toLowerCase().includes(q)) ||
            (r.category?.toLowerCase().includes(q)) ||
            (r.location_name?.toLowerCase().includes(q)) ||
            (r.equipment?.toLowerCase().includes(q))
        );
    }
    return [...rows].sort((a, b) => {
        const va = a[sortKey.value] ?? '';
        const vb = b[sortKey.value] ?? '';
        if (typeof va === 'number' && typeof vb === 'number') {
            return sortAsc.value ? va - vb : vb - va;
        }
        return sortAsc.value ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va));
    });
});

function toggleSort(key) {
    if (sortKey.value === key) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortKey.value = key;
        sortAsc.value = true;
    }
}

function formatDuration(s) {
    if (!s) return '-';
    const m = Math.floor(s / 60);
    const sec = Math.round(s % 60);
    return `${m}m ${sec.toString().padStart(2, '0')}s`;
}

function downloadCsv() {
    const headers = ['ID', 'Titre', 'Durée', 'Catégorie', 'Environnement', 'Lieu', 'Lat', 'Lng', 'Équipement', 'Plays', 'Likes', 'Enregistré'];
    const rows = filtered.value.map(r => [
        r.id, `"${(r.title || '').replace(/"/g, '""')}"`, r.duration, r.category, r.environment,
        r.location_name, r.latitude, r.longitude, r.equipment, r.play_count, r.like_count, r.recorded_at,
    ]);
    const csv = [headers.join(';'), ...rows.map(r => r.join(';'))].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'arborisis_data_sample.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <div class="glass-card p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <h3 class="font-display text-xl font-semibold text-arbor-cream">Échantillon de données</h3>
            <div class="flex items-center gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher..."
                    class="bg-arbor-deep border border-arbor-glass-border rounded-lg px-3 py-1.5 text-base text-arbor-cream placeholder-arbor-sage/50 focus:outline-none focus:border-arbor-emerald/50"
                />
                <button
                    @click="downloadCsv"
                    class="btn-primary text-sm px-4 py-1.5 rounded-lg"
                >
                    CSV
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-arbor-sage uppercase border-b border-arbor-glass-border">
                    <tr>
                        <th @click="toggleSort('title')" class="px-3 py-2 cursor-pointer hover:text-arbor-cream select-none">Titre ↕</th>
                        <th @click="toggleSort('duration')" class="px-3 py-2 cursor-pointer hover:text-arbor-cream select-none">Durée ↕</th>
                        <th @click="toggleSort('category')" class="px-3 py-2 cursor-pointer hover:text-arbor-cream select-none">Catégorie ↕</th>
                        <th @click="toggleSort('location_name')" class="px-3 py-2 cursor-pointer hover:text-arbor-cream select-none">Lieu ↕</th>
                        <th @click="toggleSort('recorded_at')" class="px-3 py-2 cursor-pointer hover:text-arbor-cream select-none">Date ↕</th>
                        <th class="px-3 py-2">Coords</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in filtered" :key="row.id" class="border-b border-arbor-glass-border/50 hover:bg-arbor-glass/30 transition-colors">
                        <td class="px-3 py-2 text-arbor-cream font-medium truncate max-w-[200px]">{{ row.title }}</td>
                        <td class="px-3 py-2 text-arbor-sage">{{ formatDuration(row.duration) }}</td>
                        <td class="px-3 py-2 text-arbor-sage">{{ row.category ?? '-' }}</td>
                        <td class="px-3 py-2 text-arbor-sage">{{ row.location_name ?? '-' }}</td>
                        <td class="px-3 py-2 text-arbor-sage">{{ row.recorded_at ? new Date(row.recorded_at).toLocaleDateString('fr-FR') : '-' }}</td>
                        <td class="px-3 py-2 text-arbor-sage font-mono text-xs">{{ row.latitude?.toFixed(2) }}, {{ row.longitude?.toFixed(2) }}</td>
                    </tr>
                    <tr v-if="filtered.length === 0">
                        <td colspan="6" class="px-3 py-6 text-center text-arbor-sage">Aucun résultat.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
