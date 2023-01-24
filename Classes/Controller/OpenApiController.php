<?php

declare(strict_types=1);

namespace Mirko\T3customroutes\Controller;

use ReflectionException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Mirko\T3customroutes\Service\OpenApiBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Mirko\T3customroutes\Provider\RoutesLoader\RouteProvider;
use GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException as OasInvalidArgumentException;

class OpenApiController extends ActionController
{
    /**
     * @throws SiteNotFoundException
     */
    public function displayAction(): void
    {
        $pageId = $this->request->getAttribute('site')->getRootPageId();
        $siteFinder = new SiteFinder();
        if ($pageId) {
            $siteIdentifier = $siteFinder->getSiteByPageId($pageId);
        } else {
            $allSites = $siteFinder->getAllSites();
            $siteIdentifier = reset($allSites);
        }
        $this->view->assign(
            'specUrl',
            $this->uriBuilder->reset()->uriFor(
                'spec',
                ['siteIdentifier' => $siteIdentifier->getIdentifier()]
            )
        );
        $this->view->assign(
            'site',
            GeneralUtility::makeInstance(SiteFinder::class)
                ->getSiteByIdentifier($siteIdentifier->getIdentifier())
        );
    }

    /**
     * @param string $siteIdentifier
     * @return string
     * @throws OasInvalidArgumentException
     * @throws ReflectionException
     * @throws SiteNotFoundException
     */
    public function specAction(string $siteIdentifier): string
    {
        $originalRequest = $GLOBALS['TYPO3_REQUEST'];
        $site = GeneralUtility::makeInstance(SiteFinder::class)
            ->getSiteByIdentifier($siteIdentifier);
        $imitateSiteRequest = $originalRequest->withAttribute('site', $site);
        $GLOBALS['TYPO3_REQUEST'] = $imitateSiteRequest;
        $output = OpenApiBuilder::build(RouteProvider::getConfiguredRoutes()->all())
            ->toJson();
        $GLOBALS['TYPO3_REQUEST'] = $originalRequest;

        return $output;
    }
}
