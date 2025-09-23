<?php

return [
    'amazon-ses' => [
        'configuration-set' => null,
    ],

    'resources' => [
        'MailLogResource' => \Bauerdot\FilamentMailLog\Resources\MailLogResource::class,
        'MailSettingResource' => \Bauerdot\FilamentMailLog\Resources\MailSettingResource::class,
    ],

    'navigation' => [
        'maillog' => [
            'register' => true,
            'sort' => 1,
            'icon' => 'heroicon-o-rectangle-stack',
        ],
        'settings' => [
            'register' => true,
            'icon' => 'heroicon-o-cog',
            'sort' => 2,
        ],
    ],

    'sort' => [
        'column' => 'created_at',
        'direction' => 'desc',
    ],


];
