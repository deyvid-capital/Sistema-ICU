<x-filament-panels::page.simple>
    <div class="icu-2fa-setup">
        <div class="icu-2fa-setup__card">
            <div class="icu-2fa-setup__brand">
                <img
                    src="{{ asset('images/Capital-Safi-SA-logo-.png') }}"
                    alt="Capital Safi"
                    class="icu-2fa-setup__capital-logo"
                />
            </div>

            <div class="icu-2fa-setup__intro">
                <h1 class="icu-2fa-setup__title">Protege tu cuenta</h1>
                <p class="icu-2fa-setup__lead">
                    Por política de seguridad debes activar la verificación en dos pasos
                    antes de continuar en el sistema.
                </p>
            </div>

            @if ($step === 'install')
                <section class="icu-2fa-setup__step" aria-labelledby="paso-1">
                    <div class="icu-2fa-setup__step-badge">Paso 1</div>
                    <h2 id="paso-1" class="icu-2fa-setup__step-title">Instala Microsoft Authenticator</h2>
                    <p class="icu-2fa-setup__step-text">
                        Descarga la aplicación en tu teléfono. Es gratuita y te permitirá
                        generar códigos de un solo uso para iniciar sesión.
                    </p>

                    <div class="icu-2fa-setup__auth-brand">
                        <div class="icu-2fa-setup__auth-card">
                            <img
                                src="{{ asset('images/Microsoft-Authenticator-logo.jpg') }}"
                                alt="Microsoft Authenticator"
                                class="icu-2fa-setup__ms-logo"
                            />
                        </div>
                    </div>

                    @php($stores = $this->getStoreUrls())
                    <div class="icu-2fa-setup__stores">
                        <a
                            href="{{ $stores['app_store'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="icu-2fa-setup__store-link"
                        >
                            <img
                                src="{{ asset('images/logo-app-store.png') }}"
                                alt="Descargar en App Store"
                                class="icu-2fa-setup__store-badge icu-2fa-setup__store-badge--app-store"
                            />
                        </a>
                        <a
                            href="{{ $stores['play_store'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="icu-2fa-setup__store-link"
                        >
                            <img
                                src="{{ asset('images/logo-play-store.png') }}"
                                alt="Disponible en Google Play"
                                class="icu-2fa-setup__store-badge icu-2fa-setup__store-badge--play-store"
                            />
                        </a>
                    </div>

                    <div class="icu-2fa-setup__actions">
                        <x-filament::button wire:click="empezar" color="primary" size="lg" class="w-full sm:w-auto">
                            Ya la instalé — continuar
                        </x-filament::button>
                    </div>
                </section>
            @elseif ($step === 'setup')
                <section class="icu-2fa-setup__step" aria-labelledby="paso-2">
                    <div class="icu-2fa-setup__step-badge">Paso 2</div>
                    <h2 id="paso-2" class="icu-2fa-setup__step-title">Escanea el código QR</h2>
                    <p class="icu-2fa-setup__step-text">
                        Abre Microsoft Authenticator, agrega una cuenta y escanea este código.
                        Si no puedes escanear, introduce la clave manualmente.
                    </p>

                    <div class="icu-2fa-setup__qr-row">
                        <div class="icu-2fa-setup__qr">
                            {!! $this->getTwoFactorQrCode() !!}
                        </div>
                        <div class="icu-2fa-setup__secret">
                            <p class="icu-2fa-setup__secret-label">Clave de configuración</p>
                            <code class="icu-2fa-setup__secret-value">{{ $this->two_factor_secret }}</code>
                        </div>
                    </div>

                    <div class="icu-2fa-setup__step-badge icu-2fa-setup__step-badge--next">Paso 3</div>
                    <h2 class="icu-2fa-setup__step-title">Introduce el código de 6 dígitos</h2>
                    <p class="icu-2fa-setup__step-text">
                        La app muestra un código que cambia cada pocos segundos. Escríbelo para confirmar.
                    </p>

                    <form wire:submit="confirmar" class="icu-2fa-setup__form space-y-4">
                        {{ $this->form }}

                        <div class="icu-2fa-setup__actions">
                            <x-filament::button type="submit" color="primary" size="lg" class="w-full sm:w-auto">
                                Verificar y activar
                            </x-filament::button>
                        </div>
                    </form>
                </section>
            @else
                <section class="icu-2fa-setup__step" aria-labelledby="paso-4">
                    <div class="icu-2fa-setup__step-badge">Paso 4</div>
                    <h2 id="paso-4" class="icu-2fa-setup__step-title">Guarda tus códigos de recuperación</h2>
                    <p class="icu-2fa-setup__step-text">
                        Si pierdes el acceso a la app, estos códigos te permitirán entrar.
                        Guárdalos en un lugar seguro; solo se muestran ahora.
                    </p>

                    <ul class="icu-2fa-setup__recovery-list">
                        @foreach ($this->recoveryCodes as $code)
                            <li><code>{{ $code }}</code></li>
                        @endforeach
                    </ul>

                    <div class="icu-2fa-setup__actions">
                        <x-filament::button wire:click="continuarAlPanel" color="primary" size="lg" class="w-full sm:w-auto">
                            Ir al sistema
                        </x-filament::button>
                    </div>
                </section>
            @endif

            <div class="icu-2fa-setup__footer">
                <form method="post" action="{{ filament()->getLogoutUrl() }}">
                    @csrf
                    <button type="submit" class="icu-2fa-setup__logout">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page.simple>
