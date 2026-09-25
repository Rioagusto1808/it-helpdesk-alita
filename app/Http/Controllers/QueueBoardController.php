<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\QueueBoard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class QueueBoardController extends Controller
{
    public function index(Request $request): View
    {
        return view('queue.index', [
            'board' => QueueBoard::snapshot($this->category($request)),
            'category' => $this->category($request),
            'search' => $request->string('cari')->trim()->upper()->toString(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => QueueBoard::snapshot($this->category($request)),
            'meta' => ['updated_at' => now()->toIso8601String()],
        ]);
    }

    /** Hanya kode kategori yang dikenal; nilai lain = semua kategori. */
    private function category(Request $request): ?string
    {
        $code = $request->string('kategori')->upper()->toString();

        return in_array($code, [Category::ITAPPS, Category::ITINFRA], true) ? $code : null;
    }
}
