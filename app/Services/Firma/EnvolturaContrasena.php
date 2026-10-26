<?php

namespace App\Services\Firma;

use App\Exceptions\FirmaDigitalException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use JsonException;
use SensitiveParameter;

/**
 * "Contraseña única": la contraseña del .p12 se guarda cifrada con una llave que
 * solo se obtiene de la contraseña del sistema del titular (Argon2id). Así el
 * titular firma con su contraseña de login y el servidor no puede firmar por él.
 *
 * El resultado se vuelve a cifrar con FIRMA_ENCRYPTION_KEY: sin esa clave, una
 * copia de la base de datos no sirve ni para intentar adivinar la contraseña.
 */
class EnvolturaContrasena
{
    private const VERSION = 1;

    public function __construct(private BovedaCertificados $boveda) {}

    public function envolver(
        #[SensitiveParameter] string $contrasenaP12,
        #[SensitiveParameter] string $contrasenaSistema,
    ): string {
        $sal = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $operaciones = (int) config('firma.kdf.operaciones');
        $memoria = (int) config('firma.kdf.memoria');

        $llave = $this->derivar($contrasenaSistema, $sal, $operaciones, $memoria);
        $datos = (new Encrypter($llave, 'aes-256-gcm'))->encryptString($contrasenaP12);
        sodium_memzero($llave);

        return $this->boveda->cifrar(json_encode([
            'v' => self::VERSION,
            'ops' => $operaciones,
            'mem' => $memoria,
            'sal' => base64_encode($sal),
            'datos' => $datos,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @throws FirmaDigitalException si la contraseña del sistema no abre la envoltura.
     */
    public function desenvolver(string $envuelta, #[SensitiveParameter] string $contrasenaSistema): string
    {
        try {
            $sobre = json_decode($this->boveda->descifrar($envuelta), true, flags: JSON_THROW_ON_ERROR);
            $llave = $this->derivar($contrasenaSistema, base64_decode($sobre['sal']), (int) $sobre['ops'], (int) $sobre['mem']);
            $contrasenaP12 = (new Encrypter($llave, 'aes-256-gcm'))->decryptString($sobre['datos']);
            sodium_memzero($llave);

            return $contrasenaP12;
        } catch (DecryptException|JsonException) {
            throw FirmaDigitalException::envolturaInvalida();
        }
    }

    /**
     * Marca del hash de contraseña con el que se envolvió. Si el hash del usuario
     * cambia sin pasar por Mi perfil, la marca deja de coincidir y la firma se suspende.
     */
    public function huellaContrasenaUsuario(string $hashContrasena): string
    {
        return hash('sha256', $hashContrasena);
    }

    private function derivar(#[SensitiveParameter] string $contrasena, string $sal, int $operaciones, int $memoria): string
    {
        return sodium_crypto_pwhash(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            $contrasena,
            $sal,
            $operaciones,
            $memoria,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13,
        );
    }
}
