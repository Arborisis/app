<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AudioWorker;
use App\Services\AudioWorkerDispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AudioWorkerDashboardController extends Controller
{
    public function __construct(
        private AudioWorkerDispatchService $dispatchService
    ) {}

    public function index(Request $request)
    {
        $workers = AudioWorker::forUser($request->user()->id)
            ->withCount(['assignments' => function ($query) {
                $query->whereIn('status', ['assigned', 'processing']);
            }])
            ->orderByDesc('created_at')
            ->get();

        $stats = $this->dispatchService->getWorkerStats();

        return Inertia::render('AudioWorkers/Dashboard', [
            'workers' => $workers,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:audio_workers,name',
            'hostname' => 'nullable|string|max:255',
            'cpu_cores' => 'required|integer|min:1|max:128',
            'memory_gb' => 'required|integer|min:1|max:512',
            'has_gpu' => 'boolean',
            'gpu_model' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }

        $worker = AudioWorker::create([
            'user_id' => $request->user()->id,
            'name' => $request->input('name'),
            'hostname' => $request->input('hostname'),
            'token' => hash('sha256', \Illuminate\Support\Str::random(64)),
            'status' => 'pending',
            'cpu_cores' => $request->input('cpu_cores'),
            'memory_gb' => $request->input('memory_gb'),
            'has_gpu' => $request->input('has_gpu', false),
            'gpu_model' => $request->input('gpu_model'),
        ]);

        return back()->with('success', 'Machine enregistrée avec succès.')->with('newWorker', $worker);
    }

    public function destroy(Request $request, int $id)
    {
        $worker = AudioWorker::forUser($request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$worker) {
            return back()->with('error', 'Machine non trouvée.');
        }

        $worker->delete();

        return back()->with('success', 'Machine supprimée avec succès.');
    }

    public function getSetupScript(int $id)
    {
        $worker = AudioWorker::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $apiUrl = config('app.url');
        
        $script = <<<SCRIPT
#!/bin/bash
set -e

echo "=== Arborisis Audio Worker Setup ==="
echo "Worker: {$worker->name}"
echo ""

# Check requirements
command -v docker >/dev/null 2>&1 || { echo "Docker est requis mais n'est pas installé."; exit 1; }

# Create worker directory
WORKER_DIR="$HOME/.arborisis-worker"
mkdir -p "$WORKER_DIR"
cd "$WORKER_DIR"

# Download worker image
docker pull arborisis/audio-worker:latest

# Create config
cat > .env << EOF
WORKER_TOKEN={$worker->token}
API_URL={$apiUrl}
WORKER_NAME={$worker->name}
WORKER_ID={$worker->id}
EOF

# Create docker-compose for auto-restart
cat > docker-compose.yml << 'EOF'
services:
  audio-worker:
    image: arborisis/audio-worker:latest
    restart: unless-stopped
    env_file:
      - .env
    volumes:
      - ./tmp:/tmp/worker
    logging:
      driver: json-file
      options:
        max-size: "10m"
        max-file: "3"
EOF

echo ""
echo "Setup terminé!"
echo "Pour démarrer le worker:"
echo "  cd $WORKER_DIR && docker compose up -d"
echo ""
echo "Pour voir les logs:"
echo "  docker compose logs -f"
SCRIPT;

        return response($script)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="setup-worker-' . $worker->id . '.sh"');
    }
}