<?php

declare(strict_types=1);

namespace Mirko\T3customroutes\Processor;

use RuntimeException;
use TYPO3\CMS\Core\Context\Context;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;

class LanguageProcessor implements ProcessorInterface
{
    /**
     * @param Request $request
     * @param ResponseInterface $response
     * @return void
     */
    public function process(Request $request, ResponseInterface $response): void
    {
        if (!$GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
            throw new RuntimeException(
                sprintf('`TYPO3_REQUEST` is not an instance of `%s`', ServerRequestInterface::class),
                1580483236906
            );
        }

        $languageHeader = $GLOBALS['TYPO3_REQUEST']->getHeader(
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3customroutes']['languageHeader']
        );
        $languageUid = (int)(!empty($languageHeader) ? array_shift($languageHeader) : 0);
        $language = $request->get('site') ? $request->get('site')->getLanguageById($languageUid) : null;
        if ($language) {
            $container = GeneralUtility::getContainer();
            $container->get(Context::class)
                ->setAspect('language', LanguageAspectFactory::createFromSiteLanguage($language));
            $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('language', $language);
        }
    }
}
