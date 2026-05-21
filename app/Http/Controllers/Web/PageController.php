<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function transparency(): Response
    {
        return Inertia::render('Transparency');
    }

    public function echoInfo(): Response
    {
        return Inertia::render('Echo/Info');
    }

    public function mission(): Response
    {
        return Inertia::render('Mission');
    }

    public function charte(): Response
    {
        return Inertia::render('Charte');
    }

    public function privacy(): Response
    {
        return Inertia::render('Privacy');
    }

    public function offline(): \Illuminate\Http\Response
    {
        return response()->view('offline');
    }
}
