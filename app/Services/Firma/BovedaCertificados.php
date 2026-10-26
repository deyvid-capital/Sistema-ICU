<?php

namespace App\Services\Firma;

use App\Models\User;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use SensitiveParameter;

/**
 * Custodia de los .p12: se guardan cifrados (AES-256-GCM) con FIRMA_ENCRYPTION_KEY
 * en el disco privado y solo se descifran en memoria.
 *
 * La clave es distinta de APP_KEY para que rotar APP_KEY no inutilice las firmas.
 */
class BovedaCertificados
{
    private const DISCO = 'local';

    private const DIRECTORIO = 'firma-digital/certificados';

    public function guardar(User $titular, #[SensitiveParameter] string $contenidoP12): string
    {
        $ruta = self::DIRECTORIO.'/'.$titular->getKey().'/'.Str::uuid().'.p12.enc';

        Storage::disk(self::DISCO)->put($ruta, $this->cifrar($contenidoP12));

        return $ruta;
    }

    public function leer(string $ruta): string
    {
        $contenido = Storage::disk(self::DISCO)->get($ruta);

        if ($contenido === null) {
            throw new RuntimeException("No se encontró el certificado cifrado [{$ruta}].");
        }

        return $this->descifrar($contenido);
    }

    public function eliminar(?string $ruta): void
    {
        if (filled($ruta)) {
            Storage::disk(self::DISCO)->delete($ruta);
        }
    }

    public function cifrar(#[SensitiveParameter] string $valor): string
    {
        return $this->encrypter()->encryptString($valor);
    }

    public function descifrar(string $valor): string
    {
        return $this->encrypter()->decryptString($valor);
    }

    private function encrypter(): Encrypter
    {
        $clave = (string) config('firma.encryption_key');

        if ($clave === '') {
            throw new RuntimeException('Falta FIRMA_ENCRYPTION_KEY en el .env (ver Docs/firma-digital/03-seguridad.md).');
        }

        if (Str::startsWith($clave, 'base64:')) {
            $clave = base64_decode(Str::after($clave, 'base64:'), true) ?: '';
        }

        return new Encrypter($clave, 'aes-256-gcm');
    }
}
