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

    'manager_class' => 'App\Console\CommandManager',
];
