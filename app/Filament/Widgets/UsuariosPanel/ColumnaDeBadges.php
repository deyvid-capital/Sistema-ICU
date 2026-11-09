<?php

namespace App\Filament\Widgets\UsuariosPanel;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/**
 * Describe una columna de badges que lista las asignaciones activas
 * (`estado = true`) de una relación HasMany del usuario.
 *
 * Con la misma definición se arman la carga anticipada, la columna, su
 * búsqueda y la restricción para filtros, así que agregar un tipo nuevo de
 * asignación es una sola línea en la pestaña.
 *
 * `atributo` admite notación de punto: 'sistema.nombre_sistema' carga la
 * relación `sistema` de cada asignación y muestra su `nombre_sistema`.
 */
final readonly class ColumnaDeBadges
{
    /**
     * Badges visibles antes de "Mostrar más". Filament solo renderiza los
     * ocultos (para poder expandirlos) si la lista usa listWithLineBreaks().
     */
    public const int LIMITE_VISIBLE = 3;

    public function __construct(
        public string $nombre,
        public string $etiqueta,
        public string $relacion,
        public string $atributo,
        public string $color = 'primary',
        public bool $ocultaPorDefecto = false,
    ) {}

    public static function make(
        string $nombre,
        string $etiqueta,
        string $relacion,
        string $atributo,
        string $color = 'primary',
        bool $ocultaPorDefecto = false,
    ): self {
        return new self($nombre, $etiqueta, $relacion, $atributo, $color, $ocultaPorDefecto);
    }

    /**
     * Carga anticipada solo de las asignaciones activas (evita N+1).
     *
     * @return array<string, \Closure(Relation<Model, Model, mixed>): void>
     */
    public function carga(): array
    {
        return [
            $this->relacion => function (Relation $query): void {
                $query->where('estado', true);

                if ($this->relacionAnidada() !== null) {
                    $query->with($this->relacionAnidada());
                }
            },
        ];
    }

    public function columna(): TextColumn
    {
        return TextColumn::make($this->nombre)
            ->label($this->etiqueta)
            ->state(fn (User $record): array => $this->valores($record))
            ->badge()
            ->color($this->color)
            ->placeholder('—')
            ->listWithLineBreaks()
            ->limitList(self::LIMITE_VISIBLE)
            ->expandableLimitedList()
            ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                $this->relacion,
                fn (Builder $query): Builder => $this->coincide($query->where('estado', true), $search),
            ))
            ->toggleable(isToggledHiddenByDefault: $this->ocultaPorDefecto);
    }

    /**
     * Usuarios con al menos una asignación activa de este tipo.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function conAlguna(Builder $query, string $boolean = 'and'): Builder
    {
        return $query->has($this->relacion, '>=', 1, $boolean, fn (Builder $query): Builder => $query->where('estado', true));
    }

    /**
     * @return list<string>
     */
    private function valores(User $record): array
    {
        return $record->{$this->relacion}
            ->map(fn (Model $asignacion): mixed => data_get($asignacion, $this->atributo))
            ->filter(fn (mixed $valor): bool => filled($valor))
            ->map(fn (mixed $valor): string => (string) $valor)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    private function coincide(Builder $query, string $search): Builder
    {
        $columna = Str::afterLast($this->atributo, '.');
        $relacionAnidada = $this->relacionAnidada();

        if ($relacionAnidada === null) {
            return $query->whereLike($columna, "%{$search}%", caseSensitive: false);
        }

        return $query->whereHas(
            $relacionAnidada,
            fn (Builder $query): Builder => $query->whereLike($columna, "%{$search}%", caseSensitive: false),
        );
    }

    private function relacionAnidada(): ?string
    {
        return str_contains($this->atributo, '.') ? Str::beforeLast($this->atributo, '.') : null;
    }
}
