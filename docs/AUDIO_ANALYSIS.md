# Documentation Analyse Audio — Arborisis

## Architecture

```
Upload Sound
    │
    ▼
SoundUploadService::upload()
    │
    ▼
ProcessAudioAnalysis (Job Queue)
    │
    ▼
Téléchargement S3 → Fichier temporaire
    │
    ▼
Pipeline Python (cli.py)
    │
    ├── Chargement audio (librosa)
    ├── Prétraitement (filtrage, normalisation, VAD)
    ├── Segmentation
    ├── Extraction features (temporelles, spectrales, cepstrales)
    ├── Génération visualisations (spectrogrammes, heatmaps)
    └── Export JSON + PNG
    │
    ▼
Parse JSON de sortie
    │
    ▼
Stockage DB (sound_analyses, sound_visualizations)
    │
    ▼
Upload PNG vers S3 (disk 'audio')
    │
    ▼
Nettoyage fichiers temporaires
```

## Format JSON Features

```json
{
  "temporal": {
    "zcr": {
      "values": [0.1, 0.12, ...],
      "stats": { "mean": 0.11, "std": 0.02, "min": 0.05, "max": 0.2, "median": 0.1 }
    },
    "rms": {
      "values": [0.5, 0.48, ...],
      "stats": { "mean": 0.5, "std": 0.1, "min": 0.1, "max": 0.9, "median": 0.52 }
    },
    "envelope": { "values": [...], "stats": {...} },
    "duration_seconds": 120.5
  },
  "spectral": {
    "centroid": { "values": [...], "stats": {...} },
    "bandwidth": { "values": [...], "stats": {...} },
    "rolloff": { "values": [...], "stats": {...} },
    "flatness": { "values": [...], "stats": {...} },
    "contrast": { "values": [...], "stats": { "band_0": {...}, ... } }
  },
  "cepstral": {
    "mfcc": { "values": [...], "stats": { "mfcc_0": {...}, ... } },
    "delta": { "values": [...] },
    "delta2": { "values": [...] }
  }
}
```

## Description des Features

| Feature | Description | Unité |
|---------|-------------|-------|
| **ZCR** | Taux de passage par zéro — mesure la "brillance" du son | ratio |
| **RMS** | Énergie moyenne quadratique — loudness perçue | amplitude |
| **Spectral Centroid** | "Centre de gravité" du spectre — brilliance | Hz |
| **Spectral Bandwidth** | Largeur du spectre autour du centroïde | Hz |
| **Spectral Rolloff** | Fréquence sous laquelle X% de l'énergie se concentre | Hz |
| **Spectral Flatness** | Rapport géométrique/arithmétique — caractère tonal/bruit | ratio |
| **Spectral Contrast** | Différence entre pics et vallées du spectre par bande | dB |
| **MFCC** | Coefficients cepstraux — représentation compacte du timbre | - |

## Guide : Ajouter une Visualisation

1. **Python** : Ajouter une fonction dans `visualizations/`
2. **Pipeline** : Appeler la fonction dans `pipeline.py`
3. **Enum** : Ajouter le type dans `SpectrogramType`
4. **Service** : Mettre à jour `AudioAnalysisService::storeVisualizations()`
5. **Frontend** : Ajouter un composant/onglet pour afficher la viz

## API Endpoints

```
GET  /sounds/{sound}/analysis          → Dashboard Inertia / JSON
POST /sounds/{sound}/analysis          → Déclenche analyse (auth)
GET  /sounds/{sound}/analysis/export   → Export JSON/CSV (auth)
GET  /api/sounds/{sound}/analysis      → JSON publique (aperçu limité)
GET  /api/sounds/{sound}/analysis/realtime → Données temps réel
```

## Configuration

`config/services.php` (à créer si besoin) :
```php
'python' => [
    'path' => env('PYTHON_PATH', 'python3'),
    'timeout' => 300,
],
'audio' => [
    'max_duration' => 600, // secondes
],
```

## Exemple curl

```bash
# Déclencher une analyse
curl -X POST https://arborisis.com/sounds/123/analysis \
  -H "Content-Type: application/json" \
  -d '{"n_fft": 2048, "frequency_scale": "mel"}'

# Récupérer l'aperçu public
curl https://arborisis.com/api/sounds/123/analysis
```
