import './bootstrap';

let unreadMessages = 0;
const originalTitle = document.title;
let audioContext = null;
let originalFaviconHref = null;
let pendingFaviconTimer = null;
let pendingTabLabel = 'Pendiente';

const pendingFaviconSvg = encodeURIComponent(`
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
    <rect width="64" height="64" rx="14" fill="#111827"/>
    <circle cx="32" cy="32" r="24" fill="#ef4444"/>
    <circle cx="32" cy="32" r="12" fill="#ffffff"/>
</svg>
`);
const pendingFaviconHref = `data:image/svg+xml,${pendingFaviconSvg}`;

function getFaviconElement() {
    let icon = document.querySelector('link[rel~="icon"]');

    if (!icon) {
        icon = document.createElement('link');
        icon.rel = 'icon';
        document.head.appendChild(icon);
    }

    if (originalFaviconHref === null) {
        originalFaviconHref = icon.href || '/favicon.ico';
    }

    return icon;
}

function setPendingTabState(label = 'Pendiente') {
    const icon = getFaviconElement();
    pendingTabLabel = label;
    icon.href = pendingFaviconHref;
    document.title = `* (${unreadMessages}) ${pendingTabLabel} - ${originalTitle}`;

    if (pendingFaviconTimer) {
        return;
    }

    pendingFaviconTimer = setInterval(() => {
        icon.href = icon.href === pendingFaviconHref ? originalFaviconHref : pendingFaviconHref;
        document.title = document.title.startsWith('*')
            ? `(${unreadMessages}) ${originalTitle}`
            : `* (${unreadMessages}) ${pendingTabLabel} - ${originalTitle}`;
    }, 1200);
}

function clearPendingTabState() {
    const icon = getFaviconElement();

    if (pendingFaviconTimer) {
        clearInterval(pendingFaviconTimer);
        pendingFaviconTimer = null;
    }

    icon.href = originalFaviconHref;
}

function getTicketUrl(message) {
    const user = window.helpdeskUser;
    const routes = window.helpdeskRoutes || {};
    const prefix = user?.isAgent ? (routes.adminTickets || '/admin/tickets') : (routes.tickets || '/tickets');

    return `${prefix}/${message.ticket_id}`;
}

function getToastContainer() {
    let container = document.getElementById('message-toast-container');

    if (!container) {
        container = document.createElement('div');
        container.id = 'message-toast-container';
        container.style.position = 'fixed';
        container.style.right = '20px';
        container.style.bottom = '20px';
        container.style.zIndex = '99999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '12px';
        container.style.width = 'min(380px, calc(100vw - 40px))';
        container.style.pointerEvents = 'none';
        document.body.appendChild(container);
    }

    return container;
}

function showIncomingMessageToast(message) {
    const container = getToastContainer();
    const toast = document.createElement('div');
    const preview = message.message || 'Envió una imagen adjunta';
    const ticketLabel = message.ticket_number ? `Ticket ${message.ticket_number}` : 'Ticket';

    toast.style.background = '#ffffff';
    toast.style.border = '1px solid #bbf7d0';
    toast.style.borderRadius = '10px';
    toast.style.boxShadow = '0 18px 45px rgba(15, 23, 42, 0.18)';
    toast.style.padding = '16px';
    toast.style.transform = 'translateY(12px)';
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 240ms ease, transform 240ms ease';
    toast.style.pointerEvents = 'auto';
    toast.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                <i class="fas fa-comment-dots"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Nuevo mensaje</p>
                        <p class="text-xs text-gray-500">${escapeHtml(ticketLabel)}</p>
                    </div>
                    <button type="button" class="close-toast text-gray-400 hover:text-gray-600" title="Cerrar aviso">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="mt-2 text-sm text-gray-700">
                    <span class="font-medium">${escapeHtml(message.user_name)}</span>:
                    ${escapeHtml(preview).slice(0, 120)}
                </p>
                <p class="mt-1 truncate text-xs text-gray-500">${escapeHtml(message.ticket_subject || '')}</p>
                <div class="mt-3 flex justify-end">
                    <a href="${getTicketUrl(message)}" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">
                        Abrir conversación
                    </a>
                </div>
            </div>
        </div>
    `;

    const close = () => {
        toast.style.transform = 'translateY(12px)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 250);
    };

    toast.querySelector('.close-toast')?.addEventListener('click', close);
    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    });

    setTimeout(close, 9000);
}

function showTicketCreatedToast(ticket) {
    const container = getToastContainer();
    const toast = document.createElement('div');
    const preview = ticket.message || 'Se creo un nuevo ticket';
    const ticketLabel = ticket.ticket_number ? `Ticket ${ticket.ticket_number}` : 'Nuevo ticket';

    toast.style.background = '#ffffff';
    toast.style.border = '1px solid #bbf7d0';
    toast.style.borderRadius = '10px';
    toast.style.boxShadow = '0 18px 45px rgba(15, 23, 42, 0.18)';
    toast.style.padding = '16px';
    toast.style.transform = 'translateY(12px)';
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 240ms ease, transform 240ms ease';
    toast.style.pointerEvents = 'auto';
    toast.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                <i class="fas fa-ticket"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Nuevo ticket creado</p>
                        <p class="text-xs text-gray-500">${escapeHtml(ticketLabel)}</p>
                    </div>
                    <button type="button" class="close-toast text-gray-400 hover:text-gray-600" title="Cerrar aviso">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="mt-2 text-sm text-gray-700">
                    <span class="font-medium">${escapeHtml(ticket.user_name)}</span>:
                    ${escapeHtml(preview).slice(0, 120)}
                </p>
                <p class="mt-1 truncate text-xs text-gray-500">${escapeHtml(ticket.ticket_subject || '')}</p>
                <div class="mt-3 flex justify-end">
                    <a href="${getTicketUrl(ticket)}" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">
                        Abrir ticket
                    </a>
                </div>
            </div>
        </div>
    `;

    const close = () => {
        toast.style.transform = 'translateY(12px)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 250);
    };

    toast.querySelector('.close-toast')?.addEventListener('click', close);
    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    });

    setTimeout(close, 9000);
}

function setupGlobalMessageAlerts() {
    const user = window.helpdeskUser;

    if (!user?.id) {
        return;
    }

    let lastGlobalMessageId = 0;
    let lastGlobalTicketId = 0;
    const shownMessages = new Set();
    const shownTickets = new Set();

    const handleIncomingMessage = (message) => {
        if (Number(message.user_id) === Number(user.id)) {
            return;
        }

        if (Number(window.helpdeskActiveTicketId) === Number(message.ticket_id)) {
            return;
        }

        const id = String(message.id);
        if (shownMessages.has(id)) {
            return;
        }

        shownMessages.add(id);
        lastGlobalMessageId = Math.max(lastGlobalMessageId, Number(message.id) || 0);
        notifyIncomingMessage(message, { currentUserId: user.id });
        showIncomingMessageToast(message);
    };

    const handleTicketCreated = (ticket) => {
        if (!user.isAgent || Number(ticket.user_id) === Number(user.id)) {
            return;
        }

        const id = String(ticket.id);
        if (shownTickets.has(id)) {
            return;
        }

        shownTickets.add(id);
        lastGlobalTicketId = Math.max(lastGlobalTicketId, Number(ticket.id) || 0);
        notifyIncomingTicket(ticket);
        showTicketCreatedToast(ticket);
    };

    const pollMessages = async () => {
        try {
            const response = await window.axios.get(window.helpdeskRoutes?.notifications || '/notifications/messages', {
                params: {
                    after_id: lastGlobalMessageId,
                    after_ticket_id: lastGlobalTicketId,
                },
            });

            if (!lastGlobalMessageId && !lastGlobalTicketId) {
                lastGlobalMessageId = Number(response.data.latest_message_id) || 0;
                lastGlobalTicketId = Number(response.data.latest_ticket_id) || 0;
                return;
            }

            (response.data.messages || []).forEach(handleIncomingMessage);
            (response.data.tickets || []).forEach(handleTicketCreated);

            if (response.data.latest_message_id) {
                lastGlobalMessageId = Math.max(lastGlobalMessageId, Number(response.data.latest_message_id) || 0);
            }

            if (response.data.latest_ticket_id) {
                lastGlobalTicketId = Math.max(lastGlobalTicketId, Number(response.data.latest_ticket_id) || 0);
            }
        } catch (error) {
            console.warn('No se pudieron consultar avisos de mensajes.', error);
        }
    };

    const subscribe = () => {
        if (!window.Echo) {
            setTimeout(subscribe, 100);
            return;
        }

        window.Echo
            .private(`user.${user.id}`)
            .listen('.ticket.message.sent', handleIncomingMessage)
            .listen('.ticket.created', handleTicketCreated);
    };

    pollMessages();
    setInterval(pollMessages, 3000);
    subscribe();
}

function setupNotificationButton() {
    const button = document.getElementById('enableNotifications');

    if (!button || !('Notification' in window)) {
        return;
    }

    const showSystemToast = (subject, message) => {
        showIncomingMessageToast({
            id: `notification-system-${Date.now()}`,
            ticket_id: 0,
            ticket_number: 'Alertas',
            ticket_subject: subject,
            user_id: 0,
            user_name: 'Sistema',
            message,
        });
    };

    const canUseBrowserNotifications = () => {
        return window.isSecureContext || ['localhost', '127.0.0.1'].includes(window.location.hostname);
    };

    const syncButton = () => {
        if (!canUseBrowserNotifications()) {
            button.innerHTML = '<i class="fas fa-lock w-5"></i><span>Sin alertas del navegador</span>';
            button.classList.add('text-amber-600');
            button.classList.remove('text-green-600', 'text-red-600');
            button.classList.remove('hidden');
            return;
        }

        if (Notification.permission === 'granted') {
            button.innerHTML = '<i class="fas fa-bell w-5"></i><span>Alertas activas</span>';
            button.classList.add('text-green-600');
            button.classList.remove('text-red-600', 'text-amber-600');
            button.classList.remove('hidden');
        } else if (Notification.permission === 'denied') {
            button.innerHTML = '<i class="fas fa-bell-slash w-5"></i><span>Alertas bloqueadas</span>';
            button.classList.add('text-red-600');
            button.classList.remove('text-green-600', 'text-amber-600');
            button.classList.remove('hidden');
        } else {
            button.innerHTML = '<i class="fas fa-bell w-5"></i><span>Activar alertas</span>';
            button.classList.remove('text-green-600', 'text-red-600', 'text-amber-600');
            button.classList.remove('hidden');
        }
    };

    syncButton();

    button.addEventListener('click', async () => {
        if (!canUseBrowserNotifications()) {
            showSystemToast(
                'Sitio sin HTTPS',
                'Las alertas del navegador requieren HTTPS o localhost. Las alertas visuales dentro del sistema seguiran funcionando.'
            );
            syncButton();
            return;
        }

        if (Notification.permission === 'default') {
            await Notification.requestPermission();
        }

        if (Notification.permission === 'denied') {
            showSystemToast(
                'Permiso bloqueado',
                'Las alertas estan bloqueadas. Habilita los permisos del sitio en el navegador y vuelve a intentar.'
            );
            syncButton();
            return;
        }

        try {
            audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();
            if (audioContext.state === 'suspended') {
                await audioContext.resume();
            }
            playNotificationSound();
        } catch (error) {
            console.warn('No se pudo preparar el sonido de alerta.', error);
        }

        showIncomingMessageToast({
            id: `notification-test-${Date.now()}`,
            ticket_id: 0,
            ticket_number: 'Prueba',
            ticket_subject: 'Alertas activadas',
            user_id: 0,
            user_name: 'Sistema',
            message: 'Las alertas visuales y de sonido estan activas.',
        });

        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Alertas activadas', {
                body: 'Recibiras avisos cuando llegue un ticket o mensaje nuevo.',
                icon: '/favicon.ico',
                tag: 'helpdesk-alerts-enabled',
            });
        }

        syncButton();
    });
}

function playNotificationSound() {
    const AudioCtor = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtor) {
        return;
    }

    audioContext = audioContext || new AudioCtor();
    const masterGain = audioContext.createGain();
    masterGain.gain.setValueAtTime(0.22, audioContext.currentTime);
    masterGain.connect(audioContext.destination);

    const notes = [
        { frequency: 659.25, start: 0, duration: 0.22 },
        { frequency: 783.99, start: 0.24, duration: 0.22 },
        { frequency: 987.77, start: 0.48, duration: 0.28 },
        { frequency: 783.99, start: 0.84, duration: 0.2 },
        { frequency: 880, start: 1.08, duration: 0.22 },
        { frequency: 1174.66, start: 1.34, duration: 0.42 },
    ];

    notes.forEach((note) => {
        const now = audioContext.currentTime + note.start;
        const oscillator = audioContext.createOscillator();
        const gain = audioContext.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(note.frequency, now);
        gain.gain.setValueAtTime(0.001, now);
        gain.gain.exponentialRampToValueAtTime(0.2, now + 0.03);
        gain.gain.exponentialRampToValueAtTime(0.001, now + note.duration);

        oscillator.connect(gain);
        gain.connect(masterGain);
        oscillator.start(now);
        oscillator.stop(now + note.duration + 0.04);
    });

    masterGain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 2);
    setTimeout(() => masterGain.disconnect(), 2200);
}

function notifyIncomingMessage(message, config) {
    if (Number(message.user_id) === Number(config.currentUserId)) {
        return;
    }

    unreadMessages += 1;
    setPendingTabState('Nuevo mensaje');

    playNotificationSound();

    if ('Notification' in window && Notification.permission === 'granted') {
        const notification = new Notification('Nuevo mensaje de soporte', {
            body: `${message.user_name}: ${message.message || 'Envió una imagen'}`,
            icon: '/favicon.ico',
            tag: `ticket-${message.ticket_id}`,
        });

        notification.onclick = () => {
            window.focus();
            notification.close();
        };
    }
}

function notifyIncomingTicket(ticket) {
    unreadMessages += 1;
    setPendingTabState('Nuevo ticket');

    playNotificationSound();

    if ('Notification' in window && Notification.permission === 'granted') {
        const notification = new Notification('Nuevo ticket creado', {
            body: `${ticket.user_name}: ${ticket.ticket_subject || ticket.message || 'Nuevo ticket'}`,
            icon: '/favicon.ico',
            tag: `ticket-created-${ticket.ticket_id}`,
        });

        notification.onclick = () => {
            window.focus();
            notification.close();
        };
    }
}

function bootHelpdeskUi() {
    setupNotificationButton();
    setupGlobalMessageAlerts();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootHelpdeskUi);
} else {
    bootHelpdeskUi();
}

window.addEventListener('focus', () => {
    unreadMessages = 0;
    document.title = originalTitle;
    clearPendingTabState();
});

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function renderMessage(message, currentUserId) {
    const mine = Number(message.user_id) === Number(currentUserId);
    const wrapperClass = mine ? 'flex-row-reverse space-x-reverse' : '';
    const bubbleClass = mine ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-800';
    const icon = mine ? 'fa-user' : (message.user_role === 'user' ? 'fa-user' : 'fa-headset');
    const text = escapeHtml(message.message).replaceAll('\n', '<br>');
    const image = message.image_url
        ? `<a href="${escapeHtml(message.image_url)}" target="_blank" class="block mt-3">
                <img src="${escapeHtml(message.image_url)}" alt="Imagen adjunta" class="max-h-56 rounded-lg border border-white/30 shadow-sm">
           </a>`
        : '';

    return `
        <div class="flex items-start space-x-3 ${wrapperClass}" data-message-id="${message.id}">
            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas ${icon} text-gray-500 text-sm"></i>
            </div>
            <div class="flex-1 max-w-[75%]">
                <div class="${bubbleClass} rounded-lg p-3">
                    <div class="text-sm">${text}</div>
                    ${image}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    ${escapeHtml(message.user_name)} &bull; ${escapeHtml(message.created_at)}
                </div>
            </div>
        </div>
    `;
}

window.setupTicketRealtime = function setupTicketRealtime(config) {
    window.helpdeskActiveTicketId = Number(config.ticketId);

    const container = document.getElementById(config.messagesContainerId);
    const form = document.getElementById(config.formId);
    const textarea = document.getElementById(config.textareaId);
    const imageInput = form?.querySelector('input[type="file"]');

    if (!container || !form || !textarea) {
        return;
    }

    const seen = new Set(
        Array.from(container.querySelectorAll('[data-message-id]')).map((node) => String(node.dataset.messageId))
    );
    let lastMessageId = Math.max(
        0,
        ...Array.from(container.querySelectorAll('[data-message-id]')).map((node) => Number(node.dataset.messageId) || 0)
    );

    const appendMessage = (message, shouldNotify = true) => {
        const id = String(message.id);
        if (seen.has(id)) {
            return;
        }

        seen.add(id);
        lastMessageId = Math.max(lastMessageId, Number(message.id) || 0);
        container.insertAdjacentHTML('beforeend', renderMessage(message, config.currentUserId));
        container.scrollTop = container.scrollHeight;

        if (shouldNotify) {
            notifyIncomingMessage(message, config);
        }
    };

    const fetchMissingMessages = async () => {
        const url = form.action.replace(/\/message$/, '/messages');

        try {
            const response = await window.axios.get(url, {
                params: { after_id: lastMessageId },
            });

            response.data.messages.forEach((message) => appendMessage(message, true));
        } catch (error) {
            console.warn('No se pudieron consultar mensajes nuevos.', error);
        }
    };

    const subscribe = () => {
        if (!window.Echo) {
            setTimeout(subscribe, 100);
            return;
        }

        window.Echo
            .private(`ticket.${config.ticketId}`)
            .listen('.ticket.message.sent', appendMessage);
    };

    subscribe();
    fetchMissingMessages();
    setInterval(fetchMissingMessages, config.pollInterval || 2000);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const content = textarea.value.trim();
        const hasImage = imageInput?.files?.length > 0;
        if (!content && !hasImage) {
            return;
        }

        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('opacity-60', 'cursor-not-allowed');
        }

        try {
            const response = await window.axios.post(form.action, new FormData(form));
            textarea.value = '';
            if (imageInput) {
                imageInput.value = '';
            }
            appendMessage(response.data.message, false);
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        }
    });

    container.scrollTop = container.scrollHeight;
};
