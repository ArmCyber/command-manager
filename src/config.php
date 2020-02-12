<?php
return [
    'groups' => [
        'full' => [
            'php artisan migrate',
            'self',
            'npm install',
            'npm run prod',
        ],
    ],

    'default_group' => 'self',

    'manager_class' => 'App\Console\CommandManager',
];
