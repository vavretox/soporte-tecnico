@extends('layouts.app')

@section('title', $article->title)

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-6 border-b pb-4">
            <div class="text-xs text-gray-500 mb-2">{{ $article->department?->name ?? 'General' }}</div>
            <h1 class="text-2xl font-bold">{{ $article->title }}</h1>
            @if($article->summary)
                <p class="text-gray-600 mt-2">{{ $article->summary }}</p>
            @endif
        </div>
        <div class="prose max-w-none whitespace-pre-line text-gray-800">{{ $article->content }}</div>
    </div>
    <div class="text-center mt-6">
        <a href="{{ route('knowledge.index') }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-2"></i>Volver
        </a>
    </div>
</div>
@endsection
