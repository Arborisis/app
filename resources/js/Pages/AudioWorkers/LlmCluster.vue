<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    }
});

const llmStats = ref({});
const models = ref([]);
const jobs = ref([]);
const loading = ref(false);

const fetchStats = async () => {
    try {
        const response = await fetch('/api/llm/stats');
        llmStats.value = await response.json();
    } catch (e) {
        console.error('Failed to fetch LLM stats:', e);
    }
};

const fetchModels = async () => {
    try {
        const response = await fetch('/api/llm/models');
        const data = await response.json();
        models.value = data.models || [];
    } catch (e) {
        console.error('Failed to fetch models:', e);
    }
};

const fetchJobs = async () => {
    try {
        const response = await fetch('/api/llm/jobs?limit=20');
        const data = await response.json();
        jobs.value = data.jobs || [];
    } catch (e) {
        console.error('Failed to fetch jobs:', e);
    }
};

onMounted(() => {
    fetchStats();
    fetchModels();
    fetchJobs();
    
    // Rafraîchir toutes les 10 secondes
    setInterval(() => {
        fetchStats();
        fetchJobs();
    }, 10000);
});

const getStatusColor = (status) => {
    const colors = {
        queued: 'bg-yellow-100 text-yellow-800',
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
    <Head title="Cluster LLM" />
    
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Cluster LLM</h1>
                    <p class="mt-2 text-gray-600">
                        Sylve distribué sur {{ llmStats.workers?.total || 0 }} machines + Claude Opus fallback
                    </p>
                </div>

                <!-- Stats Globales -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Workers</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">
                            {{ llmStats.workers?.online || 0 }}/{{ llmStats.workers?.total || 0 }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ llmStats.workers?.gpu_enabled || 0 }} GPU</div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Jobs en attente</div>
                        <div class="mt-2 text-3xl font-bold text-yellow-600">{{ llmStats.jobs?.queued || 0 }}</div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Tokens aujourd'hui</div>
                        <div class="mt-2 text-3xl font-bold text-green-600">{{ llmStats.performance?.today_tokens?.toLocaleString() || 0 }}</div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Tokens/sec moy.</div>
                        <div class="mt-2 text-3xl font-bold text-blue-600">{{ llmStats.performance?.avg_tokens_per_second || 0 }}</div>
                    </div>
                </div>

                <!-- Modèles -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Modèles LLM</h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modèle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requirements</th>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ model.requirements?.min_cpu_cores }} CPU / 
                                        {{ model.requirements?.min_memory_gb }}GB RAM
                                        <span v-if="model.requirements?.requires_gpu" class="text-purple-600 ml-1">+ GPU</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ model.fallback_model || 'Aucun' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Jobs Récents -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Jobs Récentes</h2>
                    </div>
                    
                    <div v-if="jobs.length === 0" class="p-12 text-center text-gray-500">
                        Aucune inférence récente
                    </div>
                    
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modèle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tokens</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Temps</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="job in jobs" :key="job.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ job.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ job.model }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusColor(job.status)]">
                                            {{ job.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ job.input_tokens || 0 }} → {{ job.output_tokens || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ job.processing_time_ms ? (job.processing_time_ms/1000).toFixed(2) + 's' : '-' }}
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