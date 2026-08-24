<x-filament::section
    :aside="true"
    heading="Autenticación de dos factores"
    description="Código de la app en cada inicio de sesión."
>
    @if($this->showRequiresTwoFactorAlert())
        <div style="{{ \Illuminate\Support\Arr::toCssStyles([\Filament\Support\get_color_css_variables('danger',shades: [50, 100, 400, 500, 800])]) }}" class="mb-2 p-4 rounded bg-custom-50 dark:bg-custom-400/10 ring-1 ring-custom-100 dark:ring-custom-500/70">
            <div class="flex">
                <div class="flex-shrink-0">
                    @svg('heroicon-s-shield-exclamation', 'w-5 h-5 text-custom-400')
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-custom-800 dark:text-white">
                        {{ __('filament-breezy::default.profile.2fa.must_enable') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @unless ($user->hasEnabledTwoFactor())
        <h3 class="flex items-center gap-2 text-lg font-medium">
            @svg('heroicon-o-exclamation-circle', 'w-6')
            No activada
        </h3>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Use una app autenticadora (Microsoft Authenticator u otra).
        </p>

        <div class="flex justify-between mt-3">
            {{ $this->enableAction }}
        </div>
    @else
        @if ($user->hasConfirmedTwoFactor())
            <h3 class="flex items-center gap-2 text-lg font-medium">
                @svg('heroicon-o-shield-check', 'w-6')
                Activa
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Si pierde el teléfono, un administrador puede restablecerla.
            </p>
            <div class="flex justify-between mt-3">
                {{ $this->disableAction }}
            </div>
        @else
            <h3 class="flex items-center gap-2 text-lg font-medium">
                @svg('heroicon-o-question-mark-circle', 'w-6')
                Confirmar configuración
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Escanee el QR o copie la clave, luego confirme con el código de 6 dígitos.
            </p>
            <div class="flex mt-3 space-x-4">
                <div>
                    {!! $this->getTwoFactorQrCode() !!}
                    <p class="pt-2 text-sm">
                        {{ __('filament-breezy::default.profile.2fa.setup_key') }}
                        {{ $this->two_factor_secret }}
                    </p>
                </div>
            </div>

            <div class="flex justify-between mt-3">
                {{ $this->confirmAction }}
                {{ $this->disableAction }}
            </div>
        @endif
    @endunless

    <x-filament-actions::modals />
</x-filament::section>
