<?php

namespace Database\Seeders;

use App\Models\ClusterModel;
use Illuminate\Database\Seeder;

class ClusterModelsSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            [
                'name' => 'Sylve',
                'slug' => 'sylve',
                'description' => 'Modèle IA local pour l\'analyse audio et la classification d\'espèces',
                'type' => 'local',
                'status' => 'active',
                'requirements' => [
                    'min_cpu_cores' => 4,
                    'min_memory_gb' => 8,
                    'requires_gpu' => false,
                ],
                'config' => [
                    'model_path' => '/models/sylve',
                    'batch_size' => 1,
                    'max_length' => 2048,
                ],
                'fallback_model' => 'claude-opus',
                'is_default' => true,
            ],
            [
                'name' => 'Sylve GPU',
                'slug' => 'sylve-gpu',
                'description' => 'Version GPU de Sylve pour inférence accélérée',
                'type' => 'local',
                'status' => 'active',
                'requirements' => [
                    'min_cpu_cores' => 4,
                    'min_memory_gb' => 16,
                    'requires_gpu' => true,
                ],
                'config' => [
                    'model_path' => '/models/sylve',
                    'batch_size' => 4,
                    'max_length' => 4096,
                    'gpu_layers' => -1,
                ],
                'fallback_model' => 'claude-opus',
                'is_default' => false,
            ],
            [
                'name' => 'Claude Opus',
                'slug' => 'claude-opus',
                'description' => 'Modèle API Claude Opus par Anthropic (fallback)',
                'type' => 'api',
                'status' => 'active',
                'requirements' => [
                    'min_cpu_cores' => 1,
                    'min_memory_gb' => 1,
                    'requires_gpu' => false,
                ],
                'config' => [
                    'api_key' => env('OPENROUTER_API_KEY'),
                    'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
                    'model' => 'anthropic/claude-opus-4',
                    'max_tokens' => 4096,
                ],
                'fallback_model' => null,
                'is_default' => false,
            ],
            [
                'name' => 'BirdNET Cluster',
                'slug' => 'birdnet-cluster',
                'description' => 'Distribution BirdNET sur le cluster pour analyse massive',
                'type' => 'hybrid',
                'status' => 'active',
                'requirements' => [
                    'min_cpu_cores' => 2,
                    'min_memory_gb' => 4,
                    'requires_gpu' => false,
                ],
                'config' => [
                    'confidence_threshold' => 0.3,
                    'overlap' => 1.5,
                    'sensitivity' => 1.25,
                ],
                'fallback_model' => 'claude-opus',
                'is_default' => false,
            ],
        ];

        foreach ($models as $modelData) {
            ClusterModel::updateOrCreate(
                ['slug' => $modelData['slug']],
                $modelData
            );
        }
    }
}