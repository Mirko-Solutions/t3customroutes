<?php

declare(strict_types=1);

namespace Mirko\T3customroutes\Service;

use Symfony\Component\Routing\Route;
use GoldSpecDigital\ObjectOrientedOAS\OpenApi;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Tag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Server;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Components;
use GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException as OasInvalidArgumentException;

/**
 * Class OpenApiBuilder
 */
class OpenApiBuilder
{
    /**
     * @var Components
     */
    protected static Components $components;

    /**
     * @var Route[]
     */
    protected static array $apiResources = [];

    /**
     * @param Route[] $apiResources
     *
     * @return OpenApi
     * @throws OasInvalidArgumentException
     */
    public static function build(array $apiResources): OpenApi
    {
        self::$apiResources = $apiResources;
        self::$components = Components::create();

        return OpenApi::create()
            ->openapi(OpenApi::OPENAPI_3_0_2)
            ->servers(...self::getServers())
            ->tags(...self::getTags($apiResources))
            ->paths(...self::getPaths($apiResources))
            ->components(self::$components);
    }

    /**
     * @return array
     */
    protected static function getServers(): array
    {
        return [
            Server::create()
                ->url(RouteService::getSiteBasePath()),
        ];
    }

    /**
     * @param Route[] $apiResources
     *
     * @return array
     */
    protected static function getTags(array $apiResources): array
    {
        return array_map('self::getTag', $apiResources);
    }

    /**
     * @param Route $apiResource
     *
     * @return Tag
     */
    protected static function getTag(Route $apiResource): Tag
    {
        return Tag::create()
            ->name($apiResource->getPath())
            ->description(sprintf('Operations about %s', $apiResource->getPath()));
    }

    /**
     * @param Route[] $apiResources
     *
     * @return PathItem[]
     * @throws OasInvalidArgumentException
     */
    protected static function getPaths(array $apiResources): array
    {
        $paths = [];

        foreach ($apiResources as $apiResource) {
            if (!isset($paths[$apiResource->getPath()])) {
                $paths[$apiResource->getPath()] = [
                    'path' => PathItem::create()->route($apiResource->getPath()),
                    'operations' => [],
                ];
            }

            $paths[$apiResource->getPath()]['operations'][] = self::getOperation($apiResource);
        }

        return array_values(
            array_map(
                static function (array $pathElement) {
                    /** @var PathItem $pathItem */
                    $pathItem = $pathElement['path'];

                    return $pathItem->operations(...$pathElement['operations']);
                },
                $paths
            )
        );
    }

    /**
     * @param Route $route
     *
     * @return Operation
     * @throws OasInvalidArgumentException
     */
    protected static function getOperation(Route $route): Operation
    {
        $summary = 'Retrieves the resource.';
        $method = empty($route->getMethods()) ? "GET" : strtoupper($route->getMethods()[0]);

        return Operation::create()
            ->tags(self::getTag($route))
            ->action(constant(Operation::class . '::ACTION_' . $method))
            ->summary($summary)
            ->parameters(...self::getOperationParameters($route));
    }

    /**
     * @param Route $route
     * @return array
     */
    protected static function getOperationParameters(Route $route): array
    {
        $params = [];
        foreach ($route->getRequirements() as $requirementName => $requirement) {
            $param = new Parameter();
            $param = $param->name($requirementName);
            $param = $param->in('path');
            if ($route->getDefault($requirementName)) {
                $param = $param->required(false);
            }
            $params[] = $param;
        }

        return $params;
    }
}
