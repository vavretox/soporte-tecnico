<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeArticle;
use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $user = Auth::user();

        $articles = KnowledgeArticle::query()
            ->with(['department', 'author'])
            ->when(! $user?->isAgent(), fn ($query) => $query->where('is_public', true))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($scope) use ($search) {
                    $scope->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('is_public', 'like', "%{$search}%")
                        ->orWhereHas('department', function ($departmentQuery) use ($search) {
                            $departmentQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        })
                        ->orWhereHas('author', function ($authorQuery) use ($search) {
                            $authorQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(12);

        $bitacoraCases = collect();

        if ($search !== '') {
            $bitacoraCases = Bitacora::query()
                ->with(['department', 'user', 'technician', 'ticket'])
                ->visibleTo($user)
                ->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('actions_taken', 'like', "%{$search}%")
                        ->orWhere('result', 'like', "%{$search}%");
                })
                ->latest('reported_at')
                ->limit(12)
                ->get();
        }

        return view('knowledge.index', compact('articles', 'bitacoraCases', 'search'));
    }

    public function show(KnowledgeArticle $article): View
    {
        abort_unless($article->is_public || auth()->user()?->isAgent(), 403);

        $article->load(['department', 'author']);

        return view('knowledge.show', compact('article'));
    }
}
