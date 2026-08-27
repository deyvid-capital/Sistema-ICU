<x-mail::message>
# Restablecer tu contraseña

Hola {{ $nombre }},

Recibimos una solicitud para cambiar la contraseña de tu cuenta en **Sistema ICU**. Si fuiste tú, sigue estos tres pasos. Si no fuiste tú, ignora este correo: nadie entra a tu cuenta solo con este mensaje.

## Tu código

Escríbelo **tal cual** (puedes omitir el guion). Caduca en **{{ $minutos }} minutos**, a las **{{ $expira }}**.

<x-mail::panel>
**{{ $codigo }}**
</x-mail::panel>

## Qué hacer ahora

1. Abre la pantalla de restablecimiento (botón de abajo).
2. Escribe el código.
3. Elige una contraseña nueva y confírmala.

<x-mail::button :url="$url">
Ingresar el código
</x-mail::button>

**Importante:** este código no es tu contraseña. No lo reenvíes ni lo compartas. Si no pediste el cambio, un administrador puede ayudarte desde el panel.

Gracias,<br>
Equipo de Sistema ICU
</x-mail::message>
