<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class NotTrashed implements ValidationRule
{
    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(
        protected string $model,
        protected ?string $message = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $record = $this->model::withTrashed()->find($value);

        if ($record?->trashed()) {
            $fail($this->message ?? 'Este registro se encuentra en la papelera de reciclaje y no puede asignarse.');
        }
    }
}
