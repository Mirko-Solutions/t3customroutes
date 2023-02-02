<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 't3customroutes',
    'description' => 'TYPO3 extension used to register custom api endpoints, via YAML file configuration using Symfony Routing package',
    'category' => 'module',
    'version' => '0.0.2',
    'state' => 'stable',
    'uploadfolder' => 0,
    'author' => 'Mirko (developer: Mirko)',
    'author_email' => 'support@mirko.in.ua',
    'author_company' => 'Mirko',
    'constraints' => [
        'depends' => [
            'php' => '8.0.*-8.1.*',
            'typo3' => '11.0.0-12.9.99',
        ],
    ],
];
