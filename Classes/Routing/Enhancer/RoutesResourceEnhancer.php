<?php

declare(strict_types=1);

namespace Mirko\T3customroutes\Routing\Enhancer;

use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Core\Routing\RouteCollection;
use Mirko\T3customroutes\Service\RouteService;
use TYPO3\CMS\Core\Routing\Enhancer\AbstractEnhancer;
use TYPO3\CMS\Core\Routing\Enhancer\RoutingEnhancerInterface;

/**
 * routeEnhancers:
 *   CustomRoutes:
 *     type: RoutesResourceEnhancer
 */
class RoutesResourceEnhancer extends AbstractEnhancer implements RoutingEnhancerInterface
{
    public const ENHANCER_NAME = 'RoutesResourceEnhancer';

    /**
     * @var array
     */
    protected array $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function enhanceForMatching(RouteCollection $collection): void
    {
        /** @var Route $variant */
        $variant = clone $collection->get('default');
        $variant->setPath($this->getBasePath() . '/{CustomRoutesResource?}');
        $variant->setRequirement('CustomRoutesResource', '.*');

        $collection->add('enhancer_' . $this->getBasePath() . spl_object_hash($variant), $variant);
    }

    /**
     * {@inheritdoc}
     *
     */
    public function enhanceForGeneration(RouteCollection $collection, array $parameters): void
    {
    }

    /**
     * @return string
     */
    protected function getBasePath(): string
    {
        static $basePath;

        return $basePath ?? $basePath = RouteService::getApiBasePath();
    }
}
