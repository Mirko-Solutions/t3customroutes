<?php

defined('TYPO3') || die('Access denied.');

call_user_func(
    static function () {
        if (TYPO3_MODE === 'BE') {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'T3customroutes',
                'tools',
                'm1',
                '',
                [
                    \Mirko\T3customroutes\Controller\OpenApiController::class => 'display, spec',
                ],
                [
                    'access' => 'user,group',
                    'icon' => 'EXT:t3customroutes/Resources/Public/Icons/Extension.svg',
                    'labels' => 'LLL:EXT:t3customroutes/Resources/Private/Language/locallang_mod.xlf',
                    'inheritNavigationComponentFromMainModule' => false,
                ]
            );
        }
    }
);
