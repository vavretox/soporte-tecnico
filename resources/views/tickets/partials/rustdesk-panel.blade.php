@php
    $currentUser = auth()->user();
    $targetUser = $ticket->user;
    $canRequest = $ticket->status !== 'closed'
        && ! $rustDeskActiveSession
        && ($currentUser->isAgent() || (int) $ticket->user_id === (int) $currentUser->id);
@endphp

<div class="mb-6 rounded-lg border border-emerald-100 bg-emerald-50 p-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h3 class="font-semibold text-emerald-950">
                <i class="fas fa-desktop mr-2"></i>Asistencia remota RustDesk
            </h3>
            <p class="mt-1 text-sm text-emerald-900">
                Servidor: {{ config('rustdesk.id_server') }}.
                @if($targetUser?->rustdesk_id)
                    ID registrado: <span class="font-semibold">{{ $targetUser->rustdesk_id }}</span>
                    @if($targetUser->rustdesk_alias)
                        <span class="text-emerald-800">({{ $targetUser->rustdesk_alias }})</span>
                    @endif
                @else
                    <span class="font-semibold text-amber-800">El funcionario aun no tiene ID RustDesk registrado.</span>
                @endif
            </p>
        </div>
        @if($targetUser?->rustdesk_id)
            <button type="button" onclick="navigator.clipboard?.writeText('{{ $targetUser->rustdesk_id }}')"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-100">
                <i class="fas fa-copy"></i>Copiar ID
            </button>
        @endif
    </div>

    @if($rustDeskActiveSession)
        <div class="mt-4 rounded-lg border border-white bg-white p-3 text-sm">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="font-medium text-gray-900">{{ $rustDeskActiveSession->statusLabel() }}</div>
                    <div class="text-gray-600">
                        Solicitada por {{ $rustDeskActiveSession->requester?->name ?? 'Usuario' }}
                        @if($rustDeskActiveSession->technician)
                            - Tecnico: {{ $rustDeskActiveSession->technician->name }}
                        @endif
                    </div>
                    @if($rustDeskActiveSession->reason)
                        <div class="mt-1 text-gray-600">{{ $rustDeskActiveSession->reason }}</div>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @if(
                        $rustDeskActiveSession->status === 'requested'
                        && (
                            ($rustDeskActiveSession->direction === 'user_to_support' && $currentUser->isAgent())
                            || ($rustDeskActiveSession->direction === 'support_to_user' && (int) $rustDeskActiveSession->target_user_id === (int) $currentUser->id)
                        )
                    )
                        <form method="POST" action="{{ route('rustdesk.sessions.accept', [$ticket, $rustDeskActiveSession]) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                <i class="fas fa-check mr-1"></i>Aceptar
                            </button>
                        </form>
                    @endif

                    @if($currentUser->isAgent() && in_array($rustDeskActiveSession->status, ['accepted', 'started'], true))
                        @if($rustDeskActiveSession->status === 'accepted')
                            <form method="POST" action="{{ route('rustdesk.sessions.start', [$ticket, $rustDeskActiveSession]) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                    <i class="fas fa-play mr-1"></i>Iniciar
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('rustdesk.sessions.complete', [$ticket, $rustDeskActiveSession]) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-gray-800 px-3 py-2 text-sm font-medium text-white hover:bg-gray-900">
                                <i class="fas fa-flag-checkered mr-1"></i>Completar
                            </button>
                        </form>
                    @endif

                    @if($currentUser->isAgent() || (int) $rustDeskActiveSession->requester_id === (int) $currentUser->id || (int) $rustDeskActiveSession->target_user_id === (int) $currentUser->id)
                        <form method="POST" action="{{ route('rustdesk.sessions.cancel', [$ticket, $rustDeskActiveSession]) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                                <i class="fas fa-ban mr-1"></i>Cancelar
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @elseif($canRequest)
        <form method="POST" action="{{ route('rustdesk.sessions.store', $ticket) }}" class="mt-4 rounded-lg border border-white bg-white p-3">
            @csrf
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $currentUser->isAgent() ? 'Solicitar permiso al funcionario' : 'Pedir apoyo remoto a soporte' }}
            </label>
            <textarea name="reason" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                placeholder="Motivo breve de la asistencia remota"></textarea>
            <div class="mt-2 flex justify-end">
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                    @disabled(! $targetUser?->rustdesk_id)>
                    <i class="fas fa-headset mr-2"></i>{{ $currentUser->isAgent() ? 'Solicitar acceso' : 'Pedir apoyo remoto' }}
                </button>
            </div>
        </form>
    @endif

    @if($rustDeskSessions->whereNotIn('status', ['requested', 'accepted', 'started'])->isNotEmpty())
        <div class="mt-4 border-t border-emerald-100 pt-3 text-xs text-emerald-900">
            Ultimas sesiones:
            @foreach($rustDeskSessions->whereNotIn('status', ['requested', 'accepted', 'started'])->take(3) as $session)
                <span class="ml-2 inline-flex rounded-full bg-white px-2 py-1">{{ $session->statusLabel() }} - {{ $session->created_at->format('d/m/Y H:i') }}</span>
            @endforeach
        </div>
    @endif
</div>
