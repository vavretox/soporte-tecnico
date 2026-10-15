<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CiudadaniaDigitalAuth
{
    public function authorizationUrl(Request $request): string
    {
        $this->ensureConfigured();

        $state = Str::random(48);
        $nonce = Str::random(48);

        $request->session()->put('ciudadania_digital.state', $state);
        $request->session()->put('ciudadania_digital.nonce', $nonce);

        $query = [
            'response_type' => 'code',
            'client_id' => config('services.ciudadania_digital.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'scope' => config('services.ciudadania_digital.scope'),
            'state' => $state,
            'nonce' => $nonce,
        ];

        if ($prompt = config('services.ciudadania_digital.prompt')) {
            $query['prompt'] = $prompt;
        }

        return $this->endpoint('authorization_endpoint', '/auth').'?'.Arr::query($query);
    }

    public function loginFromCallback(Request $request): User
    {
        $this->ensureConfigured();

        if ($request->filled('error')) {
            throw ValidationException::withMessages([
                'ciudadania_digital' => $request->input('error_description', 'Ciudadania Digital no autorizo el ingreso.'),
            ]);
        }

        $state = $request->session()->pull('ciudadania_digital.state');
        $nonce = $request->session()->pull('ciudadania_digital.nonce');

        if (! $state || ! hash_equals($state, (string) $request->query('state'))) {
            throw ValidationException::withMessages([
                'ciudadania_digital' => 'La respuesta de Ciudadania Digital no coincide con la sesion iniciada.',
            ]);
        }

        if ($request->query('nonce') && $nonce && ! hash_equals($nonce, (string) $request->query('nonce'))) {
            throw ValidationException::withMessages([
                'ciudadania_digital' => 'El codigo nonce de Ciudadania Digital no coincide con la sesion iniciada.',
            ]);
        }

        $code = (string) $request->query('code');

        if ($code === '') {
            throw ValidationException::withMessages([
                'ciudadania_digital' => 'Ciudadania Digital no devolvio un codigo de autorizacion.',
            ]);
        }

        $tokens = $this->exchangeCode($code);
        $claims = array_merge(
            $this->fetchUserInfo((string) ($tokens['access_token'] ?? '')),
            $this->validateIdToken((string) ($tokens['id_token'] ?? ''), $nonce),
        );
        $user = $this->syncUser($claims);

        Auth::login($user, true);
        $request->session()->regenerate();

        return $user;
    }

    private function exchangeCode(string $code): array
    {
        try {
            $response = Http::timeout((int) config('services.ciudadania_digital.timeout'))
                ->asForm()
                ->withBasicAuth(
                    (string) config('services.ciudadania_digital.client_id'),
                    (string) config('services.ciudadania_digital.client_secret'),
                )
                ->post($this->endpoint('token_endpoint', '/token'), [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri(),
                ]);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'ciudadania_digital' => 'No se pudo conectar con Ciudadania Digital para validar el ingreso.',
            ]);
        }

        if (! $response->successful()) {
            report(new RuntimeException('Ciudadania Digital token endpoint failed: '.$response->status().' '.$response->body()));

            throw ValidationException::withMessages([
                'ciudadania_digital' => 'No se pudo completar la autenticacion con Ciudadania Digital.',
            ]);
        }

        return $response->json();
    }

    private function fetchUserInfo(string $accessToken): array
    {
        if ($accessToken === '') {
            return [];
        }

        $userinfoEndpoint = $this->providerMetadata()['userinfo_endpoint'] ?? null;

        if (! $userinfoEndpoint) {
            return [];
        }

        try {
            $response = Http::timeout((int) config('services.ciudadania_digital.timeout'))
                ->withToken($accessToken)
                ->get($userinfoEndpoint);
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }

        return $response->successful() ? $response->json() : [];
    }

    private function validateIdToken(string $idToken, ?string $nonce): array
    {
        [$header, $claims, $signedPayload, $signature] = $this->decodeJwt($idToken);

        if (($header['alg'] ?? null) !== 'RS256') {
            throw ValidationException::withMessages([
                'ciudadania_digital' => 'El token de identidad usa un algoritmo no soportado.',
            ]);
        }

        $key = $this->publicKeyFor($header);

        if (! openssl_verify($signedPayload, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw ValidationException::withMessages([
                'ciudadania_digital' => 'No se pudo verificar la firma del token de identidad.',
            ]);
        }

        $issuer = rtrim((string) config('services.ciudadania_digital.issuer'), '/');
        $audience = (string) config('services.ciudadania_digital.client_id');
        $now = time();

        if (($claims['iss'] ?? null) !== $issuer) {
            throw ValidationException::withMessages(['ciudadania_digital' => 'El emisor del token no es valido.']);
        }

        $audiences = Arr::wrap($claims['aud'] ?? []);

        if (! in_array($audience, $audiences, true)) {
            throw ValidationException::withMessages(['ciudadania_digital' => 'La audiencia del token no es valida.']);
        }

        if (isset($claims['exp']) && (int) $claims['exp'] < $now) {
            throw ValidationException::withMessages(['ciudadania_digital' => 'El token de identidad expiro.']);
        }

        if (isset($claims['nbf']) && (int) $claims['nbf'] > $now + 60) {
            throw ValidationException::withMessages(['ciudadania_digital' => 'El token de identidad aun no es valido.']);
        }

        if ($nonce && isset($claims['nonce']) && ! hash_equals($nonce, (string) $claims['nonce'])) {
            throw ValidationException::withMessages(['ciudadania_digital' => 'El nonce del token no coincide.']);
        }

        return $claims;
    }

    private function syncUser(array $claims): User
    {
        $subject = trim((string) ($claims['sub'] ?? ''));
        $document = trim((string) ($claims['numero_documento'] ?? $claims['documento_identidad'] ?? ''));
        $email = strtolower(trim((string) ($claims['email'] ?? $claims['correo'] ?? '')));

        if ($subject === '' && $document !== '') {
            $subject = 'documento:'.$document;
        }

        if ($subject === '') {
            throw ValidationException::withMessages([
                'ciudadania_digital' => 'Ciudadania Digital no devolvio un identificador del ciudadano.',
            ]);
        }

        if ($email === '') {
            $email = 'ciudadania-'.sha1($subject).'@ciudadania.local';
        }

        $user = User::where('ciudadania_digital_sub', $subject)->first()
            ?: User::where('email', $email)->first();

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([
                'ciudadania_digital' => 'Tu usuario existe, pero se encuentra inactivo. Solicita su habilitacion a soporte.',
            ]);
        }

        $name = trim((string) ($claims['name'] ?? $claims['nombre'] ?? ''))
            ?: trim(implode(' ', array_filter([
                $claims['given_name'] ?? $claims['nombres'] ?? null,
                $claims['family_name'] ?? null,
                $claims['primer_apellido'] ?? null,
                $claims['segundo_apellido'] ?? null,
            ])))
            ?: Str::of($email)->before('@')->replace('.', ' ')->title()->toString();

        if (! $user) {
            $user = new User([
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'role' => 'user',
                'is_active' => true,
                'must_change_password' => false,
                'password_changed_at' => now(),
            ]);
        }

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'ciudadania_digital_sub' => $subject,
            'ciudadania_digital_last_login_at' => now(),
            'ciudadania_digital_claims' => Arr::only($claims, [
                'sub',
                'name',
                'given_name',
                'family_name',
                'nombre',
                'nombres',
                'primer_apellido',
                'segundo_apellido',
                'email',
                'correo',
                'email_verified',
                'celular',
                'phone_number',
                'fecha_nacimiento',
                'birthdate',
                'documento_identidad',
                'numero_documento',
                'tipo_documento',
            ]),
        ])->save();

        return $user;
    }

    private function endpoint(string $metadataKey, string $fallbackPath): string
    {
        $metadata = $this->providerMetadata();

        return (string) ($metadata[$metadataKey] ?? rtrim((string) config('services.ciudadania_digital.issuer'), '/').$fallbackPath);
    }

    private function providerMetadata(): array
    {
        $issuer = rtrim((string) config('services.ciudadania_digital.issuer'), '/');

        return Cache::remember('ciudadania-digital.metadata.'.sha1($issuer), now()->addHours(6), function () use ($issuer) {
            try {
                $response = Http::timeout((int) config('services.ciudadania_digital.timeout'))
                    ->get($issuer.'/.well-known/openid-configuration');
            } catch (Throwable) {
                return [];
            }

            return $response->successful() ? $response->json() : [];
        });
    }

    private function publicKeyFor(array $header): string
    {
        $jwksUri = $this->providerMetadata()['jwks_uri'] ?? null;

        if (! $jwksUri) {
            throw ValidationException::withMessages([
                'ciudadania_digital' => 'El proveedor no publico la URL de llaves JWKS.',
            ]);
        }

        $jwks = Cache::remember('ciudadania-digital.jwks.'.sha1($jwksUri), now()->addHours(6), function () use ($jwksUri) {
            try {
                $response = Http::timeout((int) config('services.ciudadania_digital.timeout'))->get($jwksUri);
            } catch (Throwable) {
                return [];
            }

            if (! $response->successful()) {
                return [];
            }

            return $response->json('keys', []);
        });

        $key = collect($jwks)->first(fn (array $key) => ($key['kid'] ?? null) === ($header['kid'] ?? null))
            ?: collect($jwks)->first();

        if (! $key || ($key['kty'] ?? null) !== 'RSA') {
            throw ValidationException::withMessages([
                'ciudadania_digital' => 'No se encontro una llave publica valida para Ciudadania Digital.',
            ]);
        }

        return $this->rsaPublicKeyFromJwk($key);
    }

    private function rsaPublicKeyFromJwk(array $jwk): string
    {
        $modulus = $this->base64UrlDecode((string) $jwk['n']);
        $exponent = $this->base64UrlDecode((string) $jwk['e']);

        $components = $this->asn1Sequence(
            $this->asn1Integer($modulus).
            $this->asn1Integer($exponent)
        );

        $bitString = "\x00".$components;
        $publicKey = $this->asn1Sequence(
            $this->asn1Sequence(
                $this->asn1ObjectIdentifier("\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01").
                $this->asn1Null()
            ).
            $this->asn1BitString($bitString)
        );

        return "-----BEGIN PUBLIC KEY-----\n".
            chunk_split(base64_encode($publicKey), 64, "\n").
            "-----END PUBLIC KEY-----\n";
    }

    private function decodeJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            throw ValidationException::withMessages(['ciudadania_digital' => 'El token de identidad no tiene formato JWT.']);
        }

        [$encodedHeader, $encodedClaims, $encodedSignature] = $parts;
        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        $claims = json_decode($this->base64UrlDecode($encodedClaims), true);

        if (! is_array($header) || ! is_array($claims)) {
            throw ValidationException::withMessages(['ciudadania_digital' => 'El token de identidad no pudo ser interpretado.']);
        }

        return [$header, $claims, $encodedHeader.'.'.$encodedClaims, $this->base64UrlDecode($encodedSignature)];
    }

    private function redirectUri(): string
    {
        return config('services.ciudadania_digital.redirect_uri') ?: route('ciudadania.callback');
    }

    private function ensureConfigured(): void
    {
        if (
            ! config('services.ciudadania_digital.enabled')
            || ! config('services.ciudadania_digital.issuer')
            || ! config('services.ciudadania_digital.client_id')
            || ! config('services.ciudadania_digital.client_secret')
        ) {
            throw ValidationException::withMessages([
                'ciudadania_digital' => 'La autenticacion con Ciudadania Digital aun no esta configurada.',
            ]);
        }
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url value.');
        }

        return $decoded;
    }

    private function asn1Length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $bytes = ltrim(pack('N', $length), "\x00");

        return chr(0x80 | strlen($bytes)).$bytes;
    }

    private function asn1Sequence(string $value): string
    {
        return "\x30".$this->asn1Length(strlen($value)).$value;
    }

    private function asn1Integer(string $value): string
    {
        $value = ltrim($value, "\x00");

        if ($value === '' || (ord($value[0]) & 0x80)) {
            $value = "\x00".$value;
        }

        return "\x02".$this->asn1Length(strlen($value)).$value;
    }

    private function asn1ObjectIdentifier(string $value): string
    {
        return "\x06".$this->asn1Length(strlen($value)).$value;
    }

    private function asn1Null(): string
    {
        return "\x05\x00";
    }

    private function asn1BitString(string $value): string
    {
        return "\x03".$this->asn1Length(strlen($value)).$value;
    }
}
