<?php

declare(strict_types=1);

namespace Mirko\T3customroutes\Provider\RoutesLoader;

use Symfony\Component\Routing\Route;
use Symfony\Component\Config\FileLocator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Mirko\T3customroutes\Service\RouteService;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

final class RouteProvider
{
    /**
     * @return mixed|RouteCollection
     * @throws NoSuchCacheException
     */
    public static function getConfiguredRoutes()
    {
        $routeCollection = new RouteCollection();
        $cache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
        $coreCache = $cache->getCache('core');
        $cacheIdentifier = GeneralUtility::makeInstance(PackageDependentCacheIdentifier::class)->withPrefix(
            't3customroutes'
        )->toString();

        if ($coreCache->has($cacheIdentifier)) {
            return unserialize($coreCache->require($cacheIdentifier));
        }

        foreach (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray() as $extKey) {
            $extRoutesPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
                    $extKey
                ) . 'Configuration/';
            $fileLocator = new FileLocator([$extRoutesPath]);
            $loader = new YamlFileLoader($fileLocator);
            try {
                $routeCollection->addCollection($loader->load('routes.yaml'));
            } catch (FileLocatorFileNotFoundException $exception) {
            }
        }
        $routeCollection = self::processFoundedRoutes($routeCollection);

        $coreCache->set($cacheIdentifier, 'return \'' . serialize($routeCollection) . '\';');

        return $routeCollection;
    }

    /**
     * @param string $name
     * @return Route|null
     * @throws NoSuchCacheException
     */
    public static function getRouteByName(string $name)
    {
        return self::getConfiguredRoutes()->get($name);
    }

    /**
     * @param RouteCollection $routeCollection
     * @return RouteCollection
     */
    private static function processFoundedRoutes(RouteCollection $routeCollection): RouteCollection
    {
        foreach ($routeCollection->all() as $name => $route) {
            $path = str_starts_with($route->getPath(), '/') ? RouteService::getFullApiBasePath() . $route->getPath(
                ) : RouteService::getFullApiBasePath() . '/' . $route->getPath();
            $route->setPath($path);
            $defaults = $route->getDefaults();
            if (!$defaults['_controller']) {
                continue;
            }

            [$controller, $method] = explode('::', $defaults['_controller']);
            if (!class_exists($controller)) {
                $routeCollection->remove($name);
                continue;
            }

            $reflection = new \ReflectionClass($controller);
            if (!$reflection->hasMethod($method)) {
                $routeCollection->remove($name);
                continue;
            }

            $route->setDefault('_controller', $reflection->getName() . "->{$method}");
        }

        return $routeCollection;
    }
}