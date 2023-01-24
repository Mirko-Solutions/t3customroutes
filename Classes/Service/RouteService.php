<?php

declare(strict_types=1);

namespace Mirko\T3customroutes\Service;

use RuntimeException;
use Symfony\Component\Routing\Route;
use TYPO3\CMS\Core\SingletonInterface;
use Mirko\T3customroutes\Routing\Enhancer\RoutesResourceEnhancer;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class RouteService implements SingletonInterface
{
    /**
     * @return string
     */
    public static function getApiBasePath(): string
    {
        return trim(
            self::getApiRouteEnhancer(
            )['basePath'] ?? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3customroutes']['basePath'],
            '/'
        );
    }

    /**
     * Returns base path including language prefix
     * @return string
     */
    public static function getFullApiBasePath(): string
    {
        return trim(self::getDefaultLanguageBasePath() . self::getApiBasePath(), '/');
    }

    /**
     * @return string
     */
    public static function getFullApiBaseUrl(): string
    {
        return rtrim((string)SiteService::getCurrent()->getBase(), '/')
            . '/' . ltrim(self::getFullApiBasePath(), '/');
    }

    public static function getSiteBasePath(): string
    {
        return rtrim((string)SiteService::getCurrent()->getBase(), '/');
    }

    /**
     * @return array
     */
    protected static function getApiRouteEnhancer(): array
    {
        static $apiRouteEnhancer;

        if (!empty($apiRouteEnhancer)) {
            return $apiRouteEnhancer;
        }

        $routeEnhancer = SiteService::getApiRouteEnhancer(SiteService::getCurrent());

        if ($routeEnhancer !== null) {
            return $routeEnhancer;
        }

        throw new RuntimeException(
            sprintf(
                'Route enhancer `%s` is not defined. You need to add it to your site configuration first. See example configuration in PHP doc of %s.',
                RoutesResourceEnhancer::ENHANCER_NAME,
                RoutesResourceEnhancer::class
            ),
            1565853631761
        );
    }

    /**
     * @return string
     */
    protected static function getDefaultLanguageBasePath(): string
    {
        return SiteService::getCurrent()->getDefaultLanguage()->getBase()->getPath();
    }

    /**
     * @param Route $route
     * @return \ReflectionClass
     * @throws ResourceNotFoundException
     */
    public static function getReflectionForRoute(Route $route): \ReflectionClass
    {
        $controller = self::getControllerName($route);

        if (!class_exists($controller)) {
            throw new ResourceNotFoundException($controller);
        }

        return new \ReflectionClass($controller);
    }

    /**
     * @param Route $route
     * @return string[]
     */
    public static function getRouteClassAndMethodName(Route $route)
    {
        $defaults = $route->getDefaults();

        if (!$defaults['_controller']) {
            throw new ResourceNotFoundException();
        }

        return explode('->', $defaults['_controller']);
    }

    /**
     * @param Route $route
     * @return string
     */
    public static function getMethodName(Route $route)
    {
        return static::getRouteClassAndMethodName($route)[1];
    }

    /**
     * @param Route $route
     * @return string
     */
    public static function getControllerName(Route $route)
    {
        return static::getRouteClassAndMethodName($route)[0];
    }
}
