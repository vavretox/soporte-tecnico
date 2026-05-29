@extends('layouts.app')

@section('title', 'Gestionar Usuarios')

@section('content')
@php($avatarOptions = [
    ['icon' => 'user', 'color' => 'green', 'class' => 'fa-user bg-green-100 text-green-700'],
    ['icon' => 'headset', 'color' => 'teal', 'class' => 'fa-headset bg-teal-100 text-teal-700'],
    ['icon' => 'laptop', 'color' => 'amber', 'class' => 'fa-laptop-code bg-amber-100 text-amber-700'],
    ['icon' => 'shield', 'color' => 'slate', 'class' => 'fa-shield-halved bg-slate-100 text-slate-700'],
    ['icon' => 'briefcase', 'color' => 'orange', 'class' => 'fa-briefcase bg-orange-100 text-orange-700'],
    ['icon' => 'seedling', 'color' => 'rose', 'class' => 'fa-seedling bg-rose-100 text-rose-700'],
])
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Gestionar Usuarios</h1>
        <button onclick="showCreateUserModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            <i class="fas fa-user-plus mr-2"></i>Nuevo Usuario
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Oficina</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo asignado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telegram</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contrasena</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actividad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($users as $user)
                    <tr>
                        <td class="px-6 py-4 font-medium">
                            <div class="flex items-center gap-3">
                                @if($user->avatarUrl())
                                    <img src="{{ $user->avatarUrl() }}" alt="Avatar de {{ $user->name }}" class="h-10 w-10 rounded-full object-cover">
                                @else
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full {{ $user->avatarColorClasses() }}">
                                        <i class="fas {{ $user->avatarIconClass() }}"></i>
                                    </span>
                                @endif
                                <span>{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full
                                @if($user->role === 'admin') bg-red-100 text-red-800
                                @elseif($user->role === 'support') bg-blue-100 text-blue-800
                                @elseif($user->role === 'secretary_dti') bg-indigo-100 text-indigo-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $roles[$user->role] ?? ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $user->office?->name ?? 'Sin oficina' }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if($user->isAdmin())
                                Todos
                            @elseif($user->supportDepartments->isNotEmpty())
                                {{ $user->supportDepartments->pluck('name')->join(', ') }}
                            @else
                                Sin asignar
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($user->telegram_chat_id)
                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2 py-1 text-xs font-medium text-sky-800">
                                    <i class="fab fa-telegram-plane"></i> Activo
                                </span>
                            @else
                                <span class="text-xs text-gray-400">Sin vincular</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($user->must_change_password)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">
                                    <i class="fas fa-key"></i> Temporal
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                    <i class="fas fa-check"></i> Propia
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            Tickets: {{ $user->tickets_count }}<br>
                            Asignados: {{ $user->assigned_tickets_count }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button
                                data-id="{{ $user->id }}"
                                data-name="{{ e($user->name) }}"
                                data-email="{{ e($user->email) }}"
                                data-role="{{ $user->role }}"
                                data-avatar-icon="{{ $user->avatar_icon ?? 'user' }}"
                                data-avatar-color="{{ $user->avatar_color ?? 'green' }}"
                                data-avatar-url="{{ $user->avatarUrl() }}"
                                data-office-id="{{ $user->office_id }}"
                                data-department-ids='@json($user->supportDepartmentIds())'
                                data-telegram-chat-id="{{ e($user->telegram_chat_id) }}"
                                data-active="{{ $user->is_active ? '1' : '0' }}"
                                onclick="editUser(this.dataset)"
                                class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.users.delete', $user) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Eliminar este usuario?')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="userModalTitle" class="text-lg font-medium">Nuevo Usuario</h3>
            <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600" title="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="userForm" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="userMethod" name="_method" value="POST">
            <input type="hidden" name="avatar_icon" id="userAvatarIcon" value="user">
            <input type="hidden" name="avatar_color" id="userAvatarColor" value="green">
            <input type="hidden" name="remove_avatar_image" id="removeAvatarImage" value="0">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2 rounded-lg border border-gray-200 p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Avatar</label>
                    <div class="flex flex-col gap-4 md:flex-row md:items-start">
                        <div class="flex items-center gap-3">
                            <div id="userAvatarPreview" class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-green-100 text-green-700">
                                <i class="fas fa-user text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">Icono o imagen del usuario</p>
                                <p class="text-xs text-gray-500">Puedes elegir un avatar o subir una foto.</p>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($avatarOptions as $option)
                                    <button type="button"
                                        onclick="selectUserAvatar('{{ $option['icon'] }}', '{{ $option['color'] }}')"
                                        class="user-avatar-option flex h-12 items-center justify-center rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50"
                                        data-icon="{{ $option['icon'] }}"
                                        data-color="{{ $option['color'] }}">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $option['class'] }}">
                                            <i class="fas {{ Str::before($option['class'], ' ') }}"></i>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-[1fr_auto]">
                                <input type="file" name="avatar_image" id="userAvatarImage" accept="image/*" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                <button type="button" onclick="clearUserAvatarImage()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50">Quitar imagen</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="name" id="userName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo *</label>
                    <input type="email" name="email" id="userCorreo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
                    <select name="role" id="userRole" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="user">Funcionario</option>
                        <option value="secretary_dti">Secretaria DTI</option>
                        <option value="support">Soporte</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Oficina *</label>
                    <select name="office_id" id="userOffice" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecciona...</option>
                        @foreach($offices as $office)
                            <option value="{{ $office->id }}">{{ $office->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipos de soporte asignados</label>
                    <select name="department_ids[]" id="userDepartments" multiple class="w-full px-3 py-2 border border-gray-300 rounded-lg min-h-28">
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Para usuarios con rol soporte, selecciona uno o varios tipos.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Chat ID Telegram</label>
                    <input type="text" name="telegram_chat_id" id="userTelegramChatId" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ej: 123456789">
                    <p class="text-xs text-gray-500 mt-1">Solo administradores y soporte con este dato recibiran avisos.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contrasena *</label>
                    <input type="password" name="password" id="userPassword" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contrasena *</label>
                    <input type="password" name="password_confirmation" id="userPasswordConfirmation" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <p id="passwordHint" class="text-xs text-gray-500 mt-2 hidden">Deja la contrasena vacia si no quieres cambiarla.</p>
            <label class="flex items-center mt-4">
                <input type="checkbox" name="is_active" id="userActive" value="1" class="mr-2">
                <span class="text-sm text-gray-700">Activo</span>
            </label>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeUserModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Guardar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function showCreateUserModal() {
        document.getElementById('userModalTitle').innerText = 'Nuevo Usuario';
        document.getElementById('userForm').action = "{{ route('admin.users.store') }}";
        document.getElementById('userMethod').value = 'POST';
        document.getElementById('userName').value = '';
        document.getElementById('userCorreo').value = '';
        document.getElementById('userRole').value = 'user';
        setUserAvatar('user', 'green', '');
        document.getElementById('userOffice').value = '';
        setUserDepartments([]);
        document.getElementById('userTelegramChatId').value = '';
        document.getElementById('userPassword').value = '';
        document.getElementById('userPasswordConfirmation').value = '';
        document.getElementById('userPassword').required = true;
        document.getElementById('userPasswordConfirmation').required = true;
        document.getElementById('passwordHint').classList.add('hidden');
        document.getElementById('userActive').checked = true;
        syncDepartmentRequirement();
        document.getElementById('userModal').classList.remove('hidden');
    }

    function editUser(user) {
        document.getElementById('userModalTitle').innerText = 'Editar Usuario';
        document.getElementById('userForm').action = `{{ url('/admin/users') }}/${user.id}`;
        document.getElementById('userMethod').value = 'PUT';
        document.getElementById('userName').value = user.name || '';
        document.getElementById('userCorreo').value = user.email || '';
        document.getElementById('userRole').value = user.role || 'user';
        setUserAvatar(user.avatarIcon || 'user', user.avatarColor || 'green', user.avatarUrl || '');
        document.getElementById('userOffice').value = user.officeId || '';
        setUserDepartments(JSON.parse(user.departmentIds || '[]'));
        document.getElementById('userTelegramChatId').value = user.telegramChatId || '';
        document.getElementById('userPassword').value = '';
        document.getElementById('userPasswordConfirmation').value = '';
        document.getElementById('userPassword').required = false;
        document.getElementById('userPasswordConfirmation').required = false;
        document.getElementById('passwordHint').classList.remove('hidden');
        document.getElementById('userActive').checked = user.active === '1';
        syncDepartmentRequirement();
        document.getElementById('userModal').classList.remove('hidden');
    }

    function closeUserModal() {
        document.getElementById('userModal').classList.add('hidden');
    }

    function syncDepartmentRequirement() {
        var departments = document.getElementById('userDepartments');
        departments.required = document.getElementById('userRole').value === 'support';
    }

    function selectUserAvatar(icon, color) {
        setUserAvatar(icon, color, '');
        document.getElementById('removeAvatarImage').value = '1';
        document.getElementById('userAvatarImage').value = '';
    }

    function setUserAvatar(icon, color, imageUrl) {
        document.getElementById('userAvatarIcon').value = icon;
        document.getElementById('userAvatarColor').value = color;
        document.getElementById('removeAvatarImage').value = '0';
        document.getElementById('userAvatarImage').value = '';

        document.querySelectorAll('.user-avatar-option').forEach(function(option) {
            option.classList.toggle('ring-2', option.dataset.icon === icon && option.dataset.color === color);
            option.classList.toggle('ring-blue-500', option.dataset.icon === icon && option.dataset.color === color);
        });

        renderUserAvatarPreview(icon, color, imageUrl);
    }

    function clearUserAvatarImage() {
        document.getElementById('removeAvatarImage').value = '1';
        document.getElementById('userAvatarImage').value = '';
        renderUserAvatarPreview(document.getElementById('userAvatarIcon').value, document.getElementById('userAvatarColor').value, '');
    }

    function renderUserAvatarPreview(icon, color, imageUrl) {
        var preview = document.getElementById('userAvatarPreview');
        var iconClass = {
            headset: 'fa-headset',
            laptop: 'fa-laptop-code',
            shield: 'fa-shield-halved',
            briefcase: 'fa-briefcase',
            seedling: 'fa-seedling',
            user: 'fa-user'
        }[icon] || 'fa-user';
        var colorClass = {
            amber: 'bg-amber-100 text-amber-700',
            orange: 'bg-orange-100 text-orange-700',
            teal: 'bg-teal-100 text-teal-700',
            slate: 'bg-slate-100 text-slate-700',
            rose: 'bg-rose-100 text-rose-700',
            green: 'bg-green-100 text-green-700'
        }[color] || 'bg-green-100 text-green-700';

        preview.className = 'flex h-16 w-16 items-center justify-center overflow-hidden rounded-full ' + colorClass;
        preview.innerHTML = imageUrl
            ? '<img src="' + imageUrl + '" alt="Avatar" class="h-full w-full object-cover">'
            : '<i class="fas ' + iconClass + ' text-xl"></i>';
    }

    function setUserDepartments(ids) {
        var normalized = ids.map(String);
        document.querySelectorAll('#userDepartments option').forEach(function(option) {
            option.selected = normalized.includes(option.value);
        });
    }

    document.getElementById('userRole').addEventListener('change', syncDepartmentRequirement);
    document.getElementById('userAvatarImage').addEventListener('change', function(event) {
        var file = event.target.files[0];
        if (!file) {
            return;
        }

        document.getElementById('removeAvatarImage').value = '0';
        renderUserAvatarPreview(
            document.getElementById('userAvatarIcon').value,
            document.getElementById('userAvatarColor').value,
            URL.createObjectURL(file)
        );
    });
</script>
@endpush
@endsection
