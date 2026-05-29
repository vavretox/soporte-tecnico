@extends('layouts.app')

@section('title', 'Base de Conocimiento')

@section('content')
<div class="max-w-5xl mx-auto px-4">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Base de Conocimiento</h1>
        <p class="text-sm text-gray-500 mt-1">Consulta descripcion, acciones realizadas y resultado de bitacoras, junto con articulos gestionados.</p>
    </div>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 flex gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Buscar solucion..." class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Buscar</button>
    </form>

    @if($search !== '')
        <div class="bg-white rounded-lg shadow p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Bitacoras encontradas</h2>
                    <span class="text-xs text-gray-500">Descripcion, acciones realizadas y resultado para "{{ $search }}"</span>
            </div>

            <div class="space-y-4">
                @forelse($bitacoraCases as $bitacora)
                    <div class="rounded-lg border border-green-100 bg-green-50 p-4">
                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <div class="text-xs font-semibold text-green-700">Bitacora {{ $bitacora->ticket?->ticket_id ? 'del ticket '.$bitacora->ticket->ticket_id : '' }}</div>
                                <h3 class="font-semibold text-gray-900">{{ $bitacora->title }}</h3>
                                <p class="mt-1 text-sm text-gray-600"><strong>Descripcion:</strong> {{ Str::limit($bitacora->description, 180) }}</p>
                                @if($bitacora->actions_taken)
                                    <p class="mt-2 text-sm text-gray-700"><strong>Acciones realizadas:</strong> {{ Str::limit($bitacora->actions_taken, 220) }}</p>
                                @endif
                                @if($bitacora->result)
                                    <p class="mt-1 text-sm text-gray-700"><strong>Resultado:</strong> {{ Str::limit($bitacora->result, 160) }}</p>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 md:text-right">
                                <div>{{ $bitacora->department?->name ?? 'Sin tipo' }}</div>
                                <div>{{ $bitacora->technician?->name ?? 'Sin soporte' }}</div>
                                <div>{{ $bitacora->reported_at?->format('d/m/Y H:i') ?? $bitacora->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                        No hay bitacoras relacionadas.
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    <h2 class="text-lg font-semibold mb-3">Gestionar conocimiento</h2>
    <div class="space-y-4">
        @forelse($articles as $article)
            <a href="{{ route('knowledge.show', $article) }}" class="block bg-white rounded-lg shadow p-5 hover:shadow-md">
                <div class="flex justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-lg">{{ $article->title }}</h2>
                        <p class="text-sm text-gray-500 mt-1">{{ $article->summary ?: Str::limit($article->content, 160) }}</p>
                    </div>
                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ $article->department?->name ?? 'General' }}</span>
                </div>
            </a>
        @empty
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">No hay articulos disponibles.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $articles->appends(request()->query())->links() }}</div>
</div>
@endsection
