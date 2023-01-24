<?php

return [
    'frontend' => [
        'mirko/customroutes/process-api-request' => [
            'target' => \Mirko\T3customroutes\Middleware\CustomRoutesRequestResolver::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
            'before' => [
                'typo3/cms-frontend/shortcut-and-mountpoint-redirect',
            ],
        ],
    ],
];
