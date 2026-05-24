<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    models: {
        type: Array,
        default: () => []
    }
});

const clusterStats = ref({});
const tasks = ref([]);
const loading = ref(false);

const fetchClusterStats = async () => {
    try {
        const response = await fetch('/api/cluster/stats');
        clusterStats.value = await response.json();
    } catch (e) {
        console.error('Failed to fetch cluster stats:', e);
    }
};

const fetchTasks = async () => {
    try {
        const response = await fetch('/api/cluster/tasks?limit=20');
        const data = await response.json();
        tasks.value = data.tasks || [];
    } catch (e) {
        console.error('Failed to fetch tasks:', e);
    }
};

onMounted(() => {
    fetchClusterStats();
    fetchTasks();
    
    // Rafraîchir toutes les 30 secondes
    setInterval(() => {
        fetchClusterStats();
        fetchTasks();
    }, 30000);
});

const totalResources = computed(() => {
    if (!clusterStats.value.resources) return { cpu: 0, ram: 0 };
    return {
        cpu: clusterStats.value.resources.total_cpu_cores || 0,
        ram: clusterStats.value.resources.total_memory_gb || 0,
    };
});

const getStatusColor = (status) => {
    const colors = {
        queued: 'bg-yellow-100 text-yellow-800',
        assigned: 'bg-blue-100 text-blue-800',
        processing: 'bg-purple-100 text-purple-800',
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getModelTypeIcon = (type) => {
    const icons = {
        local: '🖥️',
        api: '☁️',
        hybrid: '🔀',
    };
    return icons[type] || '❓';
};
</script>

<template>
    <Head title="Cluster IA" />
    
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Cluster IA Distribué</h1>
                    <p class="mt-2 text-gray-600">
                        Sylve + Claude Opus (fallback) - Mégacluster CPU/GPU
                    </p>
                </div>

                <!-- Stats Globales -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Workers</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">
                            {{ clusterStats.workers?.available || 0 }}/{{ clusterStats.workers?.total || 0 }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ clusterStats.workers?.gpu_enabled || 0 }} GPU</div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">CPU Cores</div>
                        <div class="mt-2 text-3xl font-bold text-blue-600">{{ totalResources.cpu }}</div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">RAM Total</div>
                        <div class="mt-2 text-3xl font-bold text-green-600">{{ totalResources.ram }} GB</div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Compute Score</div>
                        <div class="mt-2 text-3xl font-bold text-purple-600">{{ clusterStats.resources?.compute_score || 0 }}</div>
                    </div>
                </div>

                <!-- Modèles -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Modèles IA</h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modèle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tâches Actives</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fallback</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="model in models" :key="model.id">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <span class="text-2xl mr-3">{{ getModelTypeIcon(model.type) }}</span>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ model.name }}</div>
                                                <div class="text-sm text-gray-500">{{ model.slug }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ model.type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', model.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                                            {{ model.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ model.active_tasks || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ model.fallback_model || 'Aucun' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tâches Récents -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Tâches Récentes</h2>
                    </div>
                    
                    <div v-if="tasks.length === 0" class="p-12 text-center text-gray-500">
                        Aucune tâche récente
                    </div>
                    
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modèle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Temps</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="task in tasks" :key="task.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ task.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ task.model }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ task.type }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusColor(task.status)]">
                                            {{ task.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ task.processing_time ? task.processing_time + 's' : '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>