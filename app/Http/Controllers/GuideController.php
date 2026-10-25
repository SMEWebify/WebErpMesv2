<?php

namespace App\Http\Controllers;

use App\Services\Guides\GuideLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuideController extends Controller
{
    public function __construct(private readonly GuideLibrary $guides)
    {
    }

    public function index(Request $request): View
    {
        return view('guides.index', [
            'categories' => $this->guides->visibleTo($request->user())->groupBy('category'),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $guide = $this->guides->find($slug, $request->user());
        abort_unless($guide, 404);

        return view('guides.show', ['guide' => $guide] + $this->guides->render($guide));
    }

    public function media(Request $request, string $slug, string $file): BinaryFileResponse
    {
        $guide = $this->guides->find($slug, $request->user());
        $path = $guide ? $this->guides->mediaPath($guide, $file) : null;
        abort_unless($path, 404);

        return response()->file($path, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
