<?php

namespace Tests\Feature\Filament\Fixtures;

use App\Filament\Forms\Components\CrudSelect;
use App\Models\CategoriaSoftware;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

/**
 * Formulario mínimo para probar CrudSelect fuera de un recurso.
 */
class CrudSelectFormFixture extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static bool $permitirEliminar = true;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $formularioCategoria = [
            TextInput::make('nombre')->required()->maxLength(100),
            TextInput::make('codigo')->required()->maxLength(50),
        ];

        return $schema
            ->statePath('data')
            ->components([
                CrudSelect::make('categoria')
                    ->label('Categoría')
                    ->optionsFromModel(CategoriaSoftware::class, 'nombre')
                    ->optionIcon(Heroicon::OutlinedTag)
                    ->createOptionForm($formularioCategoria)
                    ->editOptionForm($formularioCategoria)
                    ->canDeleteOption(fn (): bool => static::$permitirEliminar),
                CrudSelect::make('categorias')
                    ->label('Categorías')
                    ->multiple()
                    ->optionsFromModel(CategoriaSoftware::class, 'nombre')
                    ->createOptionForm($formularioCategoria)
                    ->editOptionForm($formularioCategoria),
                CrudSelect::make('prioridad')
                    ->options(['baja' => 'Baja', 'alta' => 'Alta'])
                    ->required(),
            ]);
    }

    public function save(): void
    {
        $this->form->getState();
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                {{ $this->form }}
                <x-filament-actions::modals />
            </div>
        BLADE;
    }
}
