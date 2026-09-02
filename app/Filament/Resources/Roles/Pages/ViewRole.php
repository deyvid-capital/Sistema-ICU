<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ViewRole as BaseViewRole;

class ViewRole extends BaseViewRole
{
    protected static string $resource = RoleResource::class;
}
