<?php

namespace Tests\Unit\Lang;

use App\Models\AppearanceSetting;
use App\Models\MailSetting;
use App\Models\MediaSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Tests\TestCase;

class TraduccionesActividadTest extends TestCase
{
    /**
     * @return list<class-string<Model>>
     */
    private function modelosDeConfiguracion(): array
    {
        return [
            AppearanceSetting::class,
            MediaSetting::class,
            MailSetting::class,
        ];
    }

    public function test_hay_traduccion_para_cada_campo_auditado_de_configuracion(): void
    {
        $sinTraducir = [];

        foreach ($this->modelosDeConfiguracion() as $modelo) {
            foreach ((new $modelo)->getFillable() as $campo) {
                if (! trans()->has('activity.keys.'.$campo)) {
                    $sinTraducir[] = class_basename($modelo).'.'.$campo;
                }
            }
        }

        $this->assertSame([], $sinTraducir, 'Estos campos aparecerán sin traducir en el diff del registro de actividad.');
    }

    public function test_hay_traduccion_para_cada_subject_de_configuracion(): void
    {
        foreach ($this->modelosDeConfiguracion() as $modelo) {
            $clave = Str::snake(class_basename($modelo), ' ');

            $this->assertTrue(
                trans()->has('activity.subjects.'.$clave),
                "Falta activity.subjects.{$clave}.",
            );
        }
    }
}
