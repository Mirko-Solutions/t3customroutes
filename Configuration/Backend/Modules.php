<?php

return [
    't3customroutes_tools' => [
        'parent' => 'tools',
        'position' => ['bottom'],
        'access' => 'user,group',
        'workspaces' => 'live',
        'path' => '/module/web/t3customroutes_tools',
        'icon' => 'EXT:t3customroutes/Resources/Public/Icons/Extension.svg',
        'labels' => 'LLL:EXT:t3customroutes/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 't3customroutes',
        'navigationComponent' => '',
        'inheritNavigationComponentFromMainModule' => false,
        'controllerActions' => [
            \Mirko\T3customroutes\Controller\OpenApiController::class => ['display', 'spec'],
        ],
    ],
];