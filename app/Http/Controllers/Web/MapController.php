<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Map/Index', [
            'categories' => Category::orderBy('order')->get(['id', 'name', 'slug']),
        ]);
    }
}
