<?php

defined('TYPO3') || die('Access denied.');

call_user_func(
    static function () {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3customroutes']['basePath'] = 'api';
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3customroutes']['languageHeader'] = 'X-Locale';
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3customroutes']['mainEndpointResponseClass'] = \Mirko\T3customroutes\Controller\ApiController::class . '->main';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3customroutes']['processors'] = [
            \Mirko\T3customroutes\Processor\LanguageProcessor::class => 200,
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['enhancers'][\Mirko\T3customroutes\Routing\Enhancer\RoutesResourceEnhancer::ENHANCER_NAME] = \Mirko\T3customroutes\Routing\Enhancer\RoutesResourceEnhancer::class;
    }
);
