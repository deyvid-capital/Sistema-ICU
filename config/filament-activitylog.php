<?php

use App\Filament\Resources\Activitylogs\ActivitylogResource;

return [
    'resources' => [
        'label' => 'Registro de actividad',
        'plural_label' => 'Registros de actividad',
        'hide_restore_action' => false,
        'restore_action_label' => 'Restaurar',
        'hide_resource_action' => false,
        'hide_restore_model_action' => false,
        'resource_action_label' => 'Ver',
        'navigation_item' => false,
        'navigation_group' => null,
        'navigation_icon' => 'heroicon-o-clipboard-document-list',
        'navigation_sort' => null,
        'default_sort_column' => 'id',
        'default_sort_direction' => 'desc',
        'navigation_count_badge' => false,
        'resource' => ActivitylogResource::class,
    ],
    'date_format' => 'd/m/Y',
    'datetime_format' => 'd/m/Y H:i:s',
];
