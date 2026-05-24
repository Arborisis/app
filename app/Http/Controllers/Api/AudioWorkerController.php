<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AudioWorker;
use App\Models\AudioWorkerAssignment;
use App\Models\SoundAnalysis;
use App\Services\AudioWorkerDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AudioWorkerController extends Controller
{
    public function __construct(
        private AudioWorkerDispatchService $dispatchService
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:audio_workers,name',
            'hostname' => 'nullable|string|max:255',
            'cpu_cores' => 'required|integer|min:1|max:128',
            'memory_gb' => 'required|integer|min:1|max:512',
            'has_gpu' => 'boolean',
            'gpu_model' => 'nullable|string|max:255',
            'os' => 'nullable|string|max:255',
            'capabilities' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $worker = AudioWorker::create([
            'user_id' => $request->user()->id,
            'name' => $request->input('name'),
            'hostname' => $request->input('hostname'),
            'token' => hash('sha256', Str::random(64)),
            'status' => 'pending',
            'cpu_cores' => $request->input('cpu_cores'),
            'memory_gb' => $request->input('memory_gb'),
            'has_gpu' => $request->input('has_gpu', false),
            'gpu_model' => $request->input('gpu_model'),
            'os' => $request->input('os'),
            'capabilities' => $request->input('capabilities', []),
        ]);

        return response()->json([
            'worker' => $worker,
            'token' => $worker->token,
            'setup_command' => $this->getSetupCommand($worker),
        ], 201);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $worker = AudioWorker::where('token', $request->bearerToken())->first();

        if (!$worker) {
            return response()->json(['error' => 'Worker not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cpu_usage' => 'nullable|numeric|min:0|max:100',
            'memory_usage' => 'nullable|numeric|min:0|max:100',
            'current_jobs' => 'nullable|integer|min:0',
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = $request->input('current_jobs', 0) > 0 ? 'busy' : 'online';
        
        $worker->update([
            'status' => $status,
            'last_seen_at' => now(),
            'ip_address' => $request->input('ip_address', $worker->ip_address),
            'port' => $request->input('port', $worker->port),
        ]);

        $pendingJobs = AudioWorkerAssignment::where('audio_worker_id', $worker->id)
            ->where('status', 'assigned')
            ->with('soundAnalysis')
            ->get();

        return response()->json([
            'status' => 'ok',
            'pending_jobs' => $pendingJobs
                ->filter(function ($assignment) {
                    return $assignment->soundAnalysis !== null;
                })
                ->map(function ($assignment) {
                    return [
                        'assignment_id' => $assignment->id,
                        'analysis_id' => $assignment->sound_analysis_id,
                        'r2_key' => $assignment->soundAnalysis->original_r2_key,
                        'parameters' => $assignment->soundAnalysis->parameters_json,
                    ];
                })
                ->values(),
        ]);
    }

    public function requestJob(Request $request): JsonResponse
    {
        $worker = AudioWorker::where('token', $request->bearerToken())->first();

        if (!$worker || !$worker->isAvailable()) {
            return response()->json(['error' => 'Worker not available'], 403);
        }

        $job = $this->dispatchService->assignNextJob($worker);

        if (!$job || !$job->soundAnalysis) {
            return response()->json(['job' => null], 204);
        }

        return response()->json([
            'job' => [
                'assignment_id' => $job->id,
                'analysis_id' => $job->sound_analysis_id,
                'r2_key' => $job->soundAnalysis->original_r2_key,
                'parameters' => $job->soundAnalysis->parameters_json,
            ],
        ]);
    }

    public function submitResult(Request $request, int $assignmentId): JsonResponse
    {
        $worker = AudioWorker::where('token', $request->bearerToken())->first();

        if (!$worker) {
            return response()->json(['error' => 'Worker not found'], 404);
        }

        $assignment = AudioWorkerAssignment::where('id', $assignmentId)
            ->where('audio_worker_id', $worker->id)
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:completed,failed',
            'processing_time_seconds' => 'required|integer|min:0',
            'results' => 'nullable|array',
            'error_message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->input('status') === 'completed') {
            $assignment->markCompleted($request->input('processing_time_seconds'));
            
            $worker->increment('total_jobs_completed');
            $worker->update([
                'avg_processing_time' => ($worker->avg_processing_time * $worker->total_jobs_completed + $request->input('processing_time_seconds')) / ($worker->total_jobs_completed + 1),
            ]);

            $analysis = $assignment->soundAnalysis;
            if ($analysis) {
                $analysis->markCompleted();
            }
        } else {
            $assignment->markFailed($request->input('error_message', 'Unknown error'));
            $worker->increment('total_jobs_failed');

            $analysis = $assignment->soundAnalysis;
            if ($analysis) {
                $analysis->markFailed('worker_error', $request->input('error_message'));
            }
        }

        return response()->json(['status' => 'ok']);
    }

    public function list(Request $request): JsonResponse
    {
        $workers = AudioWorker::forUser($request->user()->id)
            ->withCount(['assignments' => function ($query) {
                $query->whereIn('status', ['assigned', 'processing']);
            }])
            ->get();

        return response()->json($workers);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $worker = AudioWorker::forUser($request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$worker) {
            return response()->json(['error' => 'Worker not found'], 404);
        }

        $worker->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function getSetupScript(Request $request): \Illuminate\Http\Response
    {
        // Support both Bearer token and query parameter
        $token = $request->bearerToken() ?? $request->query('token');
        
        if (!$token) {
            return response("# Error: No token provided\n# Usage: curl -fsSL 'https://arborisis.com/api/audio-workers/setup-script?token=YOUR_TOKEN' | bash\n", 401)
                ->header('Content-Type', 'text/plain');
        }

        $worker = AudioWorker::where('token', $token)->first();

        if (!$worker) {
            return response("# Error: Worker not found\n# Please register a worker first at https://arborisis.com/audio-workers\n", 404)
                ->header('Content-Type', 'text/plain');
        }

        $apiUrl = config('app.url');
        $workerToken = $worker->token;
        $workerName = $worker->name;
        $r2Endpoint = config('filesystems.disks.r2.endpoint', '');
        $r2Key = env('R2_ACCESS_KEY_ID', '');
        $r2Secret = env('R2_SECRET_ACCESS_KEY', '');
        $r2Bucket = env('R2_BUCKET', '');

        $script = <<<SCRIPT
#!/bin/bash
set -e

echo "=== Arborisis AI/LLM Worker Setup ==="
echo "Worker: {$workerName}"
echo ""

# Check requirements
command -v docker >/dev/null 2>&1 || { echo "Docker is required but not installed."; exit 1; }

# Create worker directory
WORKER_DIR="\$HOME/.arborisis-worker"
mkdir -p "\$WORKER_DIR"
cd "\$WORKER_DIR"

# Download worker files from GitHub
echo "Downloading worker files..."
GITHUB_RAW="https://raw.githubusercontent.com/Arborisis/workers/main/arborisis-worker"

curl -fsSL "\$GITHUB_RAW/Dockerfile" -o Dockerfile
curl -fsSL "\$GITHUB_RAW/worker.py" -o worker.py
curl -fsSL "\$GITHUB_RAW/audio_analyzer.py" -o audio_analyzer.py
curl -fsSL "\$GITHUB_RAW/config.py" -o config.py
curl -fsSL "\$GITHUB_RAW/infrastructure.py" -o infrastructure.py
curl -fsSL "\$GITHUB_RAW/cluster_tasks.py" -o cluster_tasks.py
curl -fsSL "\$GITHUB_RAW/auto_updater.py" -o auto_updater.py
curl -fsSL "\$GITHUB_RAW/model_manager.py" -o model_manager.py
curl -fsSL "\$GITHUB_RAW/requirements.txt" -o requirements.txt

# Create directories
mkdir -p models logs tmp

# Create config
cat > .env << EOF
WORKER_TOKEN={$workerToken}
API_URL={$apiUrl}
WORKER_NAME={$workerName}
WORKER_ID={$worker->id}
MODELS_DIR=/models
DOWNLOAD_MODELS=gemma-4,gemma-4-mini
INSTALL_GPU_DRIVERS=true
R2_ENDPOINT={$r2Endpoint}
R2_ACCESS_KEY_ID={$r2Key}
R2_SECRET_ACCESS_KEY={$r2Secret}
R2_BUCKET_NAME={$r2Bucket}
EOF

# Detect GPU
GPU_FLAGS=""
if command -v nvidia-smi &> /dev/null; then
    echo "✅ NVIDIA GPU detected: $(nvidia-smi --query-gpu=name --format=csv,noheader | head -1)"
    GPU_FLAGS="
    deploy:
      resources:
        reservations:
          devices:
            - driver: nvidia
              count: all
              capabilities: [gpu]"
else
    echo "ℹ️  No GPU detected - will run in CPU mode"
fi

# Build image locally
echo "Building worker image..."
docker build -t arborisis/audio-worker:latest .

# Create docker-compose for auto-restart
cat > docker-compose.yml << EOF
services:
  audio-worker:
    image: arborisis/audio-worker:latest
    restart: unless-stopped
    env_file:
      - .env
    environment:
      - NVIDIA_VISIBLE_DEVICES=all
      - NVIDIA_DRIVER_CAPABILITIES=compute,utility
    volumes:
      - ./tmp:/tmp/worker
      - ./logs:/app/logs
      - ./models:/models
    logging:
      driver: json-file
      options:
        max-size: "10m"
        max-file: "3"\${GPU_FLAGS}
EOF

echo ""
echo "✅ Setup complete!"
echo ""
echo "Models will be downloaded automatically on first start:"
echo "  - Gemma 4 (~4GB)"
echo "  - Gemma 4 Mini (~2.5GB)"
echo ""
echo "To start the worker:"
echo "  cd \$WORKER_DIR && docker compose up -d"
echo ""
echo "To view logs:"
echo "  docker compose logs -f"
echo ""
echo "To stop:"
echo "  docker compose down"
SCRIPT;

        return response($script)
            ->header('Content-Type', 'text/plain');
    }
}