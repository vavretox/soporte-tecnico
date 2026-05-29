<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TelegramController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $this->ensureLinkCode($user);

        return view('telegram.index', [
            'user' => $user->fresh(),
            'botUsername' => config('services.telegram.bot_username'),
            'telegramEnabled' => filled(config('services.telegram.bot_token')),
        ]);
    }

    public function regenerate(): RedirectResponse
    {
        $user = Auth::user();
        $user->update(['telegram_link_code' => $this->newCode()]);

        return back()->with('success', 'Codigo Telegram regenerado.');
    }

    public function sync(TelegramNotifier $telegram): RedirectResponse
    {
        $linked = $telegram->syncPendingLinks();

        return back()->with(
            $linked > 0 ? 'success' : 'error',
            $linked > 0 ? "Vinculaciones actualizadas: {$linked}." : 'No encontre mensajes con codigos pendientes.'
        );
    }

    public function disconnect(): RedirectResponse
    {
        Auth::user()->update([
            'telegram_chat_id' => null,
            'telegram_linked_at' => null,
            'telegram_link_code' => $this->newCode(),
        ]);

        return back()->with('success', 'Telegram desvinculado.');
    }

    private function ensureLinkCode(User $user): void
    {
        if (! $user->telegram_link_code) {
            $user->update(['telegram_link_code' => $this->newCode()]);
        }
    }

    private function newCode(): string
    {
        do {
            $code = 'HD-'.Str::upper(Str::random(8));
        } while (User::where('telegram_link_code', $code)->exists());

        return $code;
    }
}
