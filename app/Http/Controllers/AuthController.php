<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas o el usuario está inactivo.',
            ]);
        }

        $request->session()->regenerate();

        if (auth()->user()->must_change_password) {
            return redirect()->route('password.change');
        }

        return auth()->user()->isAgent()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('tickets.index');
    }

    public function showRegister(): View
    {
        $offices = Office::where('is_active', true)->orderBy('name')->get();

        return view('auth.register', compact('offices'));
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'email_prefix' => strtolower(trim((string) $request->input('email_prefix'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email_prefix' => ['required', 'string', 'max:150', 'regex:/^[a-z]+\\.[a-z]+$/'],
            'office_id' => ['required', 'exists:offices,id'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'name.required' => 'Ingresa tu nombre completo.',
            'email_prefix.required' => 'Ingresa tu correo institucional con el formato nombre.apellido.',
            'email_prefix.regex' => 'El correo debe usar tu primer nombre y primer apellido en el formato nombre.apellido@tarija.gob.bo. Ejemplo: juan.perez@tarija.gob.bo.',
            'office_id.required' => 'Selecciona la oficina a la que perteneces.',
            'office_id.exists' => 'La oficina seleccionada no es valida.',
            'password.required' => 'Ingresa una contrasena.',
            'password.confirmed' => 'La contrasena y su confirmacion no coinciden.',
            'password.min' => 'La contrasena debe tener al menos 8 caracteres.',
        ]);

        $email = $data['email_prefix'].'@tarija.gob.bo';

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email_prefix' => 'Ya existe una cuenta registrada con el correo '.$email.'.',
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'password' => Hash::make($data['password']),
            'office_id' => $data['office_id'],
            'must_change_password' => false,
            'password_changed_at' => now(),
            'role' => 'user',
            'is_active' => true,
        ]);

        Auth::login($user);

        return redirect()->route('tickets.index')->with('success', 'Cuenta creada correctamente.');
    }

    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'current_password.required' => 'Ingresa tu contrasena actual.',
            'current_password.current_password' => 'La contrasena actual no es correcta.',
            'password.required' => 'Ingresa una nueva contrasena.',
            'password.confirmed' => 'La nueva contrasena y su confirmacion no coinciden.',
            'password.min' => 'La nueva contrasena debe tener al menos 8 caracteres.',
            'password.mixed' => 'La nueva contrasena debe incluir mayusculas y minusculas.',
            'password.numbers' => 'La nueva contrasena debe incluir al menos un numero.',
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        return $user->isAgent()
            ? redirect()->route('admin.dashboard')->with('success', 'Contrasena actualizada correctamente.')
            : redirect()->route('tickets.index')->with('success', 'Contrasena actualizada correctamente.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'avatar_icon' => ['required', 'in:user,headset,laptop,shield,briefcase,seedling'],
            'avatar_color' => ['required', 'in:green,amber,orange,teal,slate,rose'],
        ], [
            'avatar_icon.required' => 'Selecciona un avatar.',
            'avatar_icon.in' => 'El avatar seleccionado no es valido.',
            'avatar_color.required' => 'Selecciona un color.',
            'avatar_color.in' => 'El color seleccionado no es valido.',
        ]);

        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update([
            ...$data,
            'avatar_path' => null,
        ]);

        return back()->with('success', 'Avatar actualizado.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
