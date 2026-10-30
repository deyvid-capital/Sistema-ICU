<?php

namespace App\Exceptions;

use Exception;

/**
 * Error de negocio del módulo de firma digital. El mensaje se muestra tal cual
 * al usuario, así que nunca incluye contraseñas ni contenido de archivos.
 */
class FirmaDigitalException extends Exception
{
    public static function archivoInvalido(): self
    {
        return new self('El archivo no es un certificado .p12 válido.');
    }

    public static function contrasenaP12Incorrecta(): self
    {
        return new self('La contraseña del archivo .p12 no es correcta.');
    }

    public static function escribioContrasenaDelSistema(): self
    {
        return new self('Escribiste tu contraseña del sistema ICU. Aquí va la contraseña de tu archivo .p12 (la de Digicert/Viafirma).');
    }

    public static function contrasenaSistemaIncorrecta(): self
    {
        return new self('La contraseña del sistema no es correcta.');
    }

    public static function clavePrivadaNoCorresponde(): self
    {
        return new self('La clave privada del .p12 no corresponde a su certificado. Solicita un archivo nuevo a Digicert.');
    }

    public static function certificadoVencido(?string $vigenteHasta = null): self
    {
        return new self($vigenteHasta === null
            ? 'El certificado no está vigente.'
            : "El certificado no está vigente (válido hasta el {$vigenteHasta}).");
    }

    public static function sinUsoDeFirma(): self
    {
        return new self('El certificado no está habilitado para firmar documentos.');
    }

    public static function usuarioSinCarnet(): self
    {
        return new self('El usuario no tiene carnet registrado en el sistema. Un administrador debe completarlo antes de activar la firma.');
    }

    public static function documentoNoCoincide(string $documentoCertificado, string $carnet): self
    {
        return new self("El certificado pertenece al documento {$documentoCertificado}, pero el carnet del usuario es {$carnet}.");
    }

    public static function certificadoDuplicado(string $usuario): self
    {
        return new self("Este certificado ya está asignado a {$usuario}.");
    }

    public static function noActivable(): self
    {
        return new self('Esta firma no está pendiente de activación.');
    }

    public static function noPerteneceAlUsuario(): self
    {
        return new self('Esta firma digital pertenece a otro usuario.');
    }

    public static function envolturaInvalida(): self
    {
        return new self('No se pudo abrir tu firma con la contraseña del sistema. Reactívala desde Mi firma digital.');
    }

    public static function sinDocumentos(): self
    {
        return new self('Elige al menos un documento del sistema o sube un PDF.');
    }

    public static function demasiadosDocumentos(int $maximo): self
    {
        return new self("Un envío puede tener como máximo {$maximo} documentos.");
    }

    public static function documentoNoFirmable(string $nombre, string $estado): self
    {
        return new self("«{$nombre}» no se puede enviar a firmar: está {$estado}. Si hay que corregirlo, regénéralo primero.");
    }

    public static function documentoNoDisponible(string $nombre): self
    {
        return new self("El archivo de \"{$nombre}\" ya no está disponible en el sistema. Genéralo de nuevo.");
    }

    public static function pdfCifrado(string $nombre): self
    {
        return new self("\"{$nombre}\" está protegido con contraseña y no se puede firmar.");
    }

    public static function pdfInvalido(string $nombre): self
    {
        return new self("\"{$nombre}\" no es un PDF válido o está dañado.");
    }

    public static function sinFirmantes(): self
    {
        return new self('Elige al menos un firmante.');
    }

    public static function firmanteRepetido(): self
    {
        return new self('Un firmante aparece más de una vez.');
    }

    public static function firmanteSinFirmaActiva(string $nombres): self
    {
        return new self("No tienen una firma digital activa y vigente: {$nombres}.");
    }

    public static function solicitudNoEsBorrador(): self
    {
        return new self('Este envío ya fue enviado: no se pueden cambiar sus documentos ni sus firmas.');
    }

    public static function posicionInvalida(): self
    {
        return new self('Las posiciones de firma no son válidas. Recarga la página e inténtalo de nuevo.');
    }

    public static function posicionFueraDePagina(string $firmante, string $documento): self
    {
        return new self("El recuadro de {$firmante} en \"{$documento}\" sale de la página.");
    }

    public static function posicionesSuperpuestas(string $documento, int $pagina): self
    {
        return new self("Hay recuadros de firma superpuestos en \"{$documento}\" (página {$pagina}).");
    }

    /**
     * @param  list<string>  $faltantes
     */
    public static function posicionesIncompletas(array $faltantes): self
    {
        return new self('Falta ubicar la firma de: '.implode('; ', $faltantes).'.');
    }

    public static function contrasenaAlFirmarIncorrecta(int $intentosRestantes): self
    {
        return new self($intentosRestantes > 0
            ? "La contraseña del sistema no es correcta. Te quedan {$intentosRestantes} intentos."
            : 'La contraseña del sistema no es correcta. Tu firma quedó bloqueada temporalmente.');
    }

    public static function firmaBloqueada(string $hasta): self
    {
        return new self("Tu firma está bloqueada por intentos fallidos hasta las {$hasta}.");
    }

    public static function sinFirmaActiva(): self
    {
        return new self('No tienes una firma digital activa. Revisa Mi firma digital.');
    }

    public static function dosPasosNoConfigurado(): self
    {
        return new self('Para firmar se exige verificación en dos pasos. Actívala en Mi perfil.');
    }

    public static function codigoDosPasosIncorrecto(): self
    {
        return new self('El código de verificación en dos pasos no es correcto.');
    }

    public static function noEsTuFirma(): self
    {
        return new self('Este recuadro de firma no te corresponde.');
    }

    public static function firmaNoPendiente(): self
    {
        return new self('Este documento ya no está pendiente de tu firma.');
    }

    public static function noEsTuTurno(string $esperando): self
    {
        return new self("Este envío exige orden: falta la firma de {$esperando}.");
    }

    public static function documentoEnUso(string $nombre): self
    {
        return new self("Otra persona está firmando \"{$nombre}\" en este momento. Inténtalo en unos segundos.");
    }

    public static function documentoAlterado(string $nombre): self
    {
        return new self("El archivo de \"{$nombre}\" no coincide con su última versión registrada. Se avisó a los administradores.");
    }

    public static function firmaNoVerificada(string $nombre): self
    {
        return new self("La firma de \"{$nombre}\" no pasó la verificación y no se guardó.");
    }

    public static function solicitudNoEnCurso(): self
    {
        return new self('Este envío ya no está en curso.');
    }

    public static function firmanteYaFirmo(string $nombre): self
    {
        return new self("{$nombre} ya firmó al menos un documento y no se puede reemplazar.");
    }

    public static function firmanteYaIncluido(string $nombre): self
    {
        return new self("{$nombre} ya es firmante de este envío.");
    }

    public static function firmadorNoDisponible(): self
    {
        return new self('El servicio de firma no está disponible. Intenta nuevamente en unos minutos.');
    }
}
