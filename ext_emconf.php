<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 't3customroutes',
    'description' => 'TYPO3 extension used to register custom api endpoints',
    'category' => 'module',
    'version' => '0.0.1',
    'state' => 'stable',
    'uploadfolder' => 0,
    'author' => 'Mirko (developer: Mirko)',
    'author_email' => 'support@mirko.in.ua',
    'author_company' => 'Mirko',
    'constraints' => [
        'depends' => [
            'php' => '8.0.*-8.1.*',
            'typo3' => '11.0.0-11.5.99',
        ],
    ],
];