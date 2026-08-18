<?php

namespace App\Livewire\Profile;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo as BreezyPersonalInfo;

class PersonalInfo extends BreezyPersonalInfo
{
    protected string $view = 'livewire.profile.personal-info';

    /**
     * @var list<string>
     */
    public array $only = ['nombres', 'apellidos', 'telefono'];

    /**
     * Keep Livewire's component name. Breezy maps this to the class basename,
     * which collides with the vendor `personal_info` alias.
     */
    public function getName()
    {
        return $this->__name;
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill($this->user->only([
            'nombres',
            'apellidos',
            'carnet',
            'email',
            'telefono',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->getProfileFormComponents())
            ->columns(2)
            ->statePath('data');
    }

    /**
     * @return array<int, TextInput>
     */
    protected function getProfileFormSchema(): array
    {
        return $this->getProfileFormComponents();
    }

    /**
     * @return array<int, TextInput>
     */
    protected function getProfileFormComponents(): array
    {
        return [
            TextInput::make('nombres')
                ->label('Nombres')
                ->placeholder('Ej. Juan Carlos')
                ->required()
                ->maxLength(100)
                ->autocomplete('given-name'),
            TextInput::make('apellidos')
                ->label('Apellidos')
                ->placeholder('Ej. Pérez López')
                ->required()
                ->maxLength(100)
                ->autocomplete('family-name'),
            TextInput::make('carnet')
                ->label('Carnet')
                ->disabled()
                ->dehydrated(false)
                ->helperText('El carnet lo administra el área de sistemas. Si necesita corregirlo, solicite el cambio.'),
            TextInput::make('email')
                ->label('Correo electrónico')
                ->email()
                ->disabled()
                ->dehydrated(false)
                ->prefixIcon(Heroicon::OutlinedEnvelope)
                ->helperText('El correo no se puede modificar. Si necesita uno distinto, solicítelo al administrador.'),
            TextInput::make('telefono')
                ->label('Teléfono')
                ->placeholder('Ej. 7123-4567')
                ->tel()
                ->maxLength(30)
                ->unique(User::class, 'telefono', ignorable: $this->user)
                ->nullable()
                ->dehydrateStateUsing(fn (mixed $state): ?string => filled($state) ? $state : null)
                ->prefixIcon(Heroicon::OutlinedPhone)
                ->helperText('Use un número de contacto vigente para notificaciones internas.')
                ->columnSpanFull(),
        ];
    }

    public function submit(): void
    {
        $originalEmail = $this->user->email;

        $data = collect($this->form->getState())
            ->only(['nombres', 'apellidos', 'telefono'])
            ->all();

        unset($data['email'], $data['carnet']);

        $data['name'] = $this->fullName(
            $data['nombres'] ?? null,
            $data['apellidos'] ?? null,
        );

        $this->user->update($data);

        if ($this->user->email !== $originalEmail) {
            $this->user->forceFill(['email' => $originalEmail])->save();
        }

        $this->form->fill($this->user->only([
            'nombres',
            'apellidos',
            'carnet',
            'email',
            'telefono',
        ]));
        $this->sendNotification();
    }

    protected function fullName(?string $nombres, ?string $apellidos): string
    {
        return trim(implode(' ', array_filter(
            [$nombres, $apellidos],
            fn (?string $value): bool => filled($value),
        )));
    }
}
