<?php

namespace App\Console\Commands;

use App\Services\Configuracion\MailConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailTestCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mail:test
                            {email=deyvid@capitalsafi.com : Destinatario del correo de prueba}
                            {--subject=Prueba de correo - Sistema ICU : Asunto del mensaje}';

    /**
     * @var string
     */
    protected $description = 'Envía un correo de prueba para verificar la configuración SMTP';

    public function handle(): int
    {
        app(MailConfigService::class)->aplicar();

        /** @var string $email */
        $email = $this->argument('email');
        /** @var string $subject */
        $subject = $this->option('subject');

        $password = (string) config('mail.mailers.smtp.password');
        $hasSpecialChars = (bool) preg_match('/[^A-Za-z0-9]/', $password);
        $fuente = app(MailConfigService::class)->resolver()['activo'] ? 'base de datos' : '.env';

        $this->info('Configuración de correo actual:');
        $this->table(
            ['Clave', 'Valor'],
            [
                ['Fuente', $fuente],
                ['MAIL_MAILER', (string) config('mail.default')],
                ['MAIL_HOST', (string) config('mail.mailers.smtp.host')],
                ['MAIL_PORT', (string) config('mail.mailers.smtp.port')],
                ['MAIL_SCHEME', (string) (config('mail.mailers.smtp.scheme') ?? 'null')],
                ['MAIL_ENCRYPTION', (string) (config('mail.mailers.smtp.encryption') ?? 'null')],
                ['MAIL_REQUIRE_TLS', config('mail.mailers.smtp.require_tls') ? 'true' : 'false'],
                ['MAIL_USERNAME', (string) config('mail.mailers.smtp.username')],
                ['MAIL_PASSWORD length', (string) strlen($password)],
                ['MAIL_PASSWORD special chars', $hasSpecialChars ? 'yes' : 'no'],
                ['MAIL_PASSWORD ends with #', str_ends_with($password, '#') ? 'yes' : 'no'],
                ['MAIL_FROM_ADDRESS', (string) config('mail.from.address')],
                ['MAIL_FROM_NAME', (string) config('mail.from.name')],
                ['Destinatario', $email],
            ],
        );

        if ($password === '' || (! $hasSpecialChars && $this->envPasswordLooksUnquoted())) {
            $this->warn('Si la contraseña tiene # o caracteres especiales, en .env debe ir entre comillas:');
            $this->line('MAIL_PASSWORD="tuClaveCon#Simbolos"');
        }

        $sentAt = $this->now();
        $body = "Hola,\n\nEste es un correo de prueba del Sistema ICU.\n\nSi lo recibes, la configuración SMTP está funcionando correctamente.\n\nEnviado el: {$sentAt}";

        try {
            $this->comment('Enviando correo de prueba...');

            Mail::raw($body, function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });

            $this->newLine();
            $this->info("Correo enviado correctamente a {$email}.");
            $this->line('Revisa la bandeja de entrada y la carpeta de spam.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->newLine();
            $this->error('No se pudo enviar el correo.');
            $this->line($exception->getMessage());
            $this->newLine();
            $this->warn('Qué revisar en Office 365 / Microsoft 365:');
            $this->line('1. Usuario y contraseña en .env (MAIL_USERNAME / MAIL_PASSWORD entre comillas si hay #).');
            $this->line('2. Que SMTP AUTH esté habilitado para la cuenta ti@capitalsafi.com.');
            $this->line('3. Si el tenant exige MFA, usa una "contraseña de aplicación".');
            $this->line('4. MAIL_SCHEME=smtp + MAIL_ENCRYPTION=tls + puerto 587 (STARTTLS).');
            $this->line('5. Tras cambiar .env: vendor/bin/sail artisan config:clear');

            return self::FAILURE;
        }
    }

    private function envPasswordLooksUnquoted(): bool
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            return false;
        }

        $contents = file_get_contents($envPath);

        return is_string($contents) && preg_match('/^MAIL_PASSWORD=[^"\'].*#/m', $contents) === 1;
    }

    private function now(): string
    {
        return now()->timezone(config('app.display_timezone'))->format('d/m/Y H:i:s');
    }
}
