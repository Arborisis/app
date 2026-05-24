<?php

namespace Database\Seeders;

use App\Models\LlmModel;
use Illuminate\Database\Seeder;

class LlmModelsSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            [
                'name' => 'Sylve',
                'slug' => 'sylve',
                'description' => 'Modèle LLM local optimisé pour les conversations naturelles et l\'analyse audio',
                'type' => 'local',
                'status' => 'active',
                'requirements' => [
                    'min_cpu_cores' => 4,
                    'min_memory_gb' => 8,
                    'requires_gpu' => false,
                ],
                'config' => [
                    'model_path' => '/models/sylve.gguf',
                    'context_length' => 8192,
                    'quantization' => 'Q4_K_M',
                ],
                'fallback_model' => 'claude-opus',
                'is_default' => true,
            ],
            [
                'name' => 'Sylve GPU',
                'slug' => 'sylve-gpu',
                'description' => 'Version GPU de Sylve avec accélération CUDA',
                'type' => 'local',
                'status' => 'active',
                'requirements' => [
                    'min_cpu_cores' => 4,
                    'min_memory_gb' => 16,
                    'requires_gpu' => true,
                    'min_vram_gb' => 8,
                ],
                'config' => [
                    'model_path' => '/models/sylve.gguf',
                    'context_length' => 32768,
                    'quantization' => 'Q5_K_M',
                    'gpu_layers' => -1,
                ],
                'fallback_model' => 'sylve',
                'is_default' => false,
            ],
            [
                'name' => 'Sylve Mini',
                'slug' => 'sylve-mini',
                'description' => 'Version légère de Sylve pour machines modestes',
                'type' => 'local',
                'status' => 'active',
                'requirements' => [
                    'min_cpu_cores' => 2,
                    'min_memory_gb' => 4,
                    'requires_gpu' => false,
                ],
                'config' => [
                    'model_path' => '/models/sylve-mini.gguf',
                    'context_length' => 4096,
                    'quantization' => 'Q3_K_S',
                ],
                'fallback_model' => 'sylve',
                'is_default' => false,
            ],
            [
                'name' => 'Claude Opus',
                'slug' => 'claude-opus',
                'description' => 'Claude Opus via OpenRouter (fallback API)',
                'type' => 'api',
                'status' => 'active',
                'requirements' => [
                    'min_cpu_cores' => 1,
                    'min_memory_gb' => 1,
                    'requires_gpu' => false,
                ],
                'config' => [
                    'api_key_env' => 'OPENROUTER_API_KEY',
                    'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
                    'model' => 'anthropic/claude-opus-4',
                    'max_tokens' => 4096,
                ],
                'fallback_model' => null,
                'is_default' => false,
            ],
        ];

        foreach ($models as $modelData) {
            LlmModel::updateOrCreate(
                ['slug' => $modelData['slug']],
                $modelData
            );
        }
    }
}