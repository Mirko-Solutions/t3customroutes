<?php

declare(strict_types=1);

namespace Mirko\T3customroutes\Service;

use RuntimeException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use Mirko\T3customroutes\Routing\Enhancer\RoutesResourceEnhancer;

class SiteService
{
    /**
     * @return Site
     */
    public static function getCurrent(): Site
    {
        static $site;

        if ($site === null) {
            $site = self::getResolvedByTypo3() ??
                self::getFirstMatchingCurrentUrl() ??
                self::getFirstWithWildcardDomain();
        }

        if (!$site instanceof Site) {
            throw new RuntimeException('Could not determine current site', 1604259480589);
        }

        return $site;
    }

    /**
     * @return array
     */
    public static function getAll(): array
    {
        static $allSites;

        return $allSites ??
            $allSites = GeneralUtility::makeInstance(SiteFinder::class)
                ->getAllSites();
    }

    /**
     * @param Site $site
     * @return array|null
     */
    public static function getApiRouteEnhancer(Site $site): ?array
    {
        foreach ($site->getConfiguration()['routeEnhancers'] ?? [] as $routeEnhancer) {
            if ($routeEnhancer['type'] === RoutesResourceEnhancer::ENHANCER_NAME) {
                return $routeEnhancer;
            }
        }

        return null;
    }

    /**
     * @param string $identifier
     * @return Site
     * @throws SiteNotFoundException
     */
    public static function getByIdentifier(string $identifier): Site
    {
        return GeneralUtility::makeInstance(SiteFinder::class)
            ->getSiteByIdentifier($identifier);
    }

    /**
     * @return Site|null
     */
    protected static function getResolvedByTypo3(): ?Site
    {
        if (!class_exists(SiteMatcher::class)) {
            return null;
        }
        $routeResult = GeneralUtility::makeInstance(SiteMatcher::class)
            ->matchRequest(ServerRequestFactory::fromGlobals());

        return $routeResult instanceof SiteRouteResult ? $routeResult->getSite() : null;
    }

    /**
     * @return Site|null
     */
    protected static function getFirstMatchingCurrentUrl(): ?Site
    {
        foreach (self::getAll() as $site) {
            if (rtrim(trim((string)$site->getBase()), '/')
                === GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST')) {
                return $site;
            }
        }

        return null;
    }

    /**
     * @return Site|null
     */
    protected static function getFirstWithWildcardDomain(): ?Site
    {
        foreach (self::getAll() as $site) {
            if (trim((string)$site->getBase()) === '/') {
                return $site;
            }
        }

        return null;
    }
}
