@extends('layouts.app')

@section('title', 'Base de Conocimiento')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Base de Conocimiento</h1>
            <p class="text-sm text-gray-500 mt-1">Soluciones frecuentes para documentar y reutilizar soporte.</p>
        </div>
        <button onclick="showCreateKnowledgeModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            <i class="fas fa-plus mr-2"></i>Nuevo Articulo
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Articulo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Autor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Visible</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($articles as $article)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium">{{ $article->title }}</div>
                                <div class="text-xs text-gray-500">{{ Str::limit($article->summary, 90) }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $article->department?->name ?? 'General' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $article->author?->name ?? 'No aplica' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $article->is_public ? 'Publico' : 'Solo soporte' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('knowledge.show', $article) }}" target="_blank" class="text-green-600 hover:text-green-800 mr-3" title="Ver"><i class="fas fa-eye"></i></a>
                                <button
                                    data-id="{{ $article->id }}"
                                    data-title="{{ e($article->title) }}"
                                    data-department-id="{{ $article->department_id }}"
                                    data-summary="{{ e($article->summary) }}"
                                    data-content="{{ e($article->content) }}"
                                    data-public="{{ $article->is_public ? '1' : '0' }}"
                                    onclick="editKnowledge(this.dataset)"
                                    class="text-blue-600 hover:text-blue-800 mr-3" title="Editar"><i class="fas fa-edit"></i></button>
                                <form method="POST" action="{{ route('admin.knowledge.delete', $article) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-800" onclick="return confirm('Eliminar este articulo?')" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No hay articulos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">{{ $articles->links() }}</div>
    </div>
</div>

<div id="knowledgeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-8 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="knowledgeModalTitle" class="text-lg font-medium">Nuevo Articulo</h3>
            <button onclick="closeKnowledgeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="knowledgeForm" method="POST">
            @csrf
            <input type="hidden" id="knowledgeMethod" name="_method" value="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input name="title" id="knowledgeTitle" required placeholder="Titulo *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <select name="department_id" id="knowledgeDepartment" class="px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">General</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
                <textarea name="summary" id="knowledgeSummary" rows="2" placeholder="Resumen" class="px-3 py-2 border border-gray-300 rounded-lg md:col-span-2"></textarea>
                <textarea name="content" id="knowledgeContent" required rows="10" placeholder="Solucion / procedimiento *" class="px-3 py-2 border border-gray-300 rounded-lg md:col-span-2"></textarea>
                <label class="flex items-center px-3 py-2"><input type="checkbox" name="is_public" id="knowledgePublic" value="1" class="mr-2">Visible para funcionarios</label>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeKnowledgeModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Guardar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showCreateKnowledgeModal() {
    knowledgeModalTitle.innerText = 'Nuevo Articulo';
    knowledgeForm.action = "{{ route('admin.knowledge.store') }}";
    knowledgeMethod.value = 'POST';
    knowledgeTitle.value = ''; knowledgeDepartment.value = ''; knowledgeSummary.value = ''; knowledgeContent.value = ''; knowledgePublic.checked = true;
    knowledgeModal.classList.remove('hidden');
}
function editKnowledge(article) {
    knowledgeModalTitle.innerText = 'Editar Articulo';
    knowledgeForm.action = `{{ url('/admin/knowledge') }}/${article.id}`;
    knowledgeMethod.value = 'PUT';
    knowledgeTitle.value = article.title || ''; knowledgeDepartment.value = article.departmentId || '';
    knowledgeSummary.value = article.summary || ''; knowledgeContent.value = article.content || '';
    knowledgePublic.checked = article.public === '1';
    knowledgeModal.classList.remove('hidden');
}
function closeKnowledgeModal() { knowledgeModal.classList.add('hidden'); }
</script>
@endpush
@endsection
