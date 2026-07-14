<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailTestCommandTest extends TestCase
{
    public function test_mail_test_command_sends_raw_email_to_given_address(): void
    {
        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $body, callable $callback): bool {
                $message = new class
                {
                    public string $to = '';

                    public string $subject = '';

                    public function to(string $address): self
                    {
                        $this->to = $address;

                        return $this;
                    }

                    public function subject(string $subject): self
                    {
                        $this->subject = $subject;

                        return $this;
                    }
                };

                $callback($message);

                return str_contains($body, 'correo de prueba')
                    && $message->to === 'deyvid@capitalsafi.com'
                    && $message->subject === 'Prueba de correo - Sistema ICU';
            })
            ->andReturnNull();

        $this->artisan('mail:test', [
            'email' => 'deyvid@capitalsafi.com',
            '--subject' => 'Prueba de correo - Sistema ICU',
        ])
            ->expectsOutputToContain('Correo enviado correctamente a deyvid@capitalsafi.com.')
            ->assertSuccessful();
    }

    public function test_mail_test_command_reports_failure_when_transport_throws(): void
    {
        Mail::shouldReceive('raw')
            ->once()
            ->andThrow(new \RuntimeException('Authentication unsuccessful'));

        $this->artisan('mail:test', ['email' => 'deyvid@capitalsafi.com'])
            ->expectsOutputToContain('No se pudo enviar el correo.')
            ->expectsOutputToContain('SMTP AUTH')
            ->assertFailed();
    }
}
