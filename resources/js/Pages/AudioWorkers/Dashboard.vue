<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    workers: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    flash: {
        type: Object,
        default: () => ({})
    }
});

const showForm = ref(false);
const showSetupModal = ref(false);
const selectedWorker = ref(null);
const copying = ref(false);

const form = ref({
    name: '',
    hostname: '',
    cpu_cores: 4,
    memory_gb: 8,
    has_gpu: false,
    gpu_model: ''
});

const submitting = ref(false);

const statusColors = {
    online: 'bg-green-100 text-green-800',
    offline: 'bg-gray-100 text-gray-800',
    busy: 'bg-yellow-100 text-yellow-800',
    pending: 'bg-blue-100 text-blue-800',
    error: 'bg-red-100 text-red-800'
};

const statusLabels = {
    online: 'En ligne',
    offline: 'Hors ligne',
    busy: 'Occupé',
    pending: 'En attente',
    error: 'Erreur'
};

const submitForm = () => {
    submitting.value = true;
    router.post('/audio-workers', form.value, {
        onSuccess: () => {
            showForm.value = false;
            form.value = {
                name: '',
                hostname: '',
                cpu_cores: 4,
                memory_gb: 8,
                has_gpu: false,
                gpu_model: ''
            };
        },
        onFinish: () => {
            submitting.value = false;
        }
    });
};

const deleteWorker = (id) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette machine ?')) {
        router.delete(`/audio-workers/${id}`);
    }
};

const openSetupModal = (worker) => {
    selectedWorker.value = worker;
    showSetupModal.value = true;
};

const getSetupCommand = (worker) => {
    return `curl -fsSL ${window.location.origin}/api/audio-workers/setup-script | WORKER_TOKEN=${worker.token} bash`;
};

const copyToClipboard = async (text) => {
    copying.value = true;
    await navigator.clipboard.writeText(text);
    setTimeout(() => {
        copying.value = false;
    }, 2000);
};

const totalWorkers = computed(() => props.workers.length);
const onlineWorkers = computed(() => props.workers.filter(w => w.status === 'online').length);
const busyWorkers = computed(() => props.workers.filter(w => w.status === 'busy').length);
</script>

<template>
    <Head title="Mes Machines Audio" />
    
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Mes Machines Audio</h1>
                    <p class="mt-2 text-gray-600">Gérez vos machines de traitement audio et contribuez à l'analyse des sons.</p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Total machines</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ totalWorkers }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">En ligne</div>
                        <div class="mt-2 text-3xl font-bold text-green-600">{{ onlineWorkers }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Occupées</div>
                        <div class="mt-2 text-3xl font-bold text-yellow-600">{{ busyWorkers }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Jobs aujourd'hui</div>
                        <div class="mt-2 text-3xl font-bold text-blue-600">{{ stats.completed_today || 0 }}</div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mb-6">
                    <button
                        @click="showForm = !showForm"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        {{ showForm ? 'Annuler' : 'Ajouter une machine' }}
                    </button>
                </div>

                <!-- Form -->
                <div v-if="showForm" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Nouvelle machine</h2>
                    <form @submit.prevent="submitForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nom</label>
                                <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Hostname</label>
                                <input v-model="form.hostname" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cœurs CPU</label>
                                <input v-model.number="form.cpu_cores" type="number" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mémoire (GB)</label>
                                <input v-model.number="form.memory_gb" type="number" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="flex items-center">
                                <input v-model="form.has_gpu" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label class="ml-2 block text-sm font-medium text-gray-700">GPU disponible</label>
                            </div>
                            <div v-if="form.has_gpu">
                                <label class="block text-sm font-medium text-gray-700">Modèle GPU</label>
                                <input v-model="form.gpu_model" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="mt-6">
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                {{ submitting ? 'Enregistrement...' : 'Enregistrer la machine' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Workers List -->
                <div v-if="workers.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    <div class="text-gray-500">
                        <p class="text-lg font-medium">Aucune machine enregistrée</p>
                        <p class="mt-2">Ajoutez votre première machine pour commencer à contribuer au traitement audio.</p>
                    </div>
                </div>

                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specs</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière activité</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="worker in workers" :key="worker.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ worker.name }}</div>
                                        <div class="text-sm text-gray-500">{{ worker.hostname }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', statusColors[worker.status]]">
                                            {{ statusLabels[worker.status] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ worker.cpu_cores }} cœurs</div>
                                        <div class="text-sm text-gray-500">{{ worker.memory_gb }} GB RAM</div>
                                        <div v-if="worker.has_gpu" class="text-sm text-green-600">{{ worker.gpu_model || 'GPU' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ worker.total_jobs_completed }} jobs</div>
                                        <div class="text-sm text-gray-500">{{ worker.avg_processing_time ? worker.avg_processing_time.toFixed(1) + 's' : 'N/A' }} moy.</div>
                                        <div class="text-sm text-gray-500">{{ worker.total_jobs_failed }} échecs</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ worker.last_seen_at ? new Date(worker.last_seen_at).toLocaleString() : 'Jamais' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            @click="openSetupModal(worker)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            Setup
                                        </button>
                                        <button
                                            @click="deleteWorker(worker.id)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Setup Modal -->
                <div v-if="showSetupModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuration de {{ selectedWorker?.name }}</h3>
                        <p class="text-sm text-gray-600 mb-4">Exécutez cette commande sur votre machine pour la connecter automatiquement :</p>
                        <div class="bg-gray-900 rounded-lg p-4 mb-4 relative">
                            <code class="text-green-400 text-sm break-all">{{ getSetupCommand(selectedWorker) }}</code>
                            <button
                                @click="copyToClipboard(getSetupCommand(selectedWorker))"
                                class="absolute top-2 right-2 text-gray-400 hover:text-white"
                            >
                                {{ copying ? 'Copié !' : 'Copier' }}
                            </button>
                        </div>
                        <div class="text-sm text-gray-600 mb-4">
                            <p class="font-medium">Prérequis :</p>
                            <ul class="list-disc list-inside mt-2">
                                <li>Docker installé</li>
                                <li>Connexion Internet stable</li>
                                <li>Port 8080 disponible (modifiable)</li>
                            </ul>
                        </div>
                        <div class="flex justify-end">
                            <button
                                @click="showSetupModal = false"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
                            >
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>