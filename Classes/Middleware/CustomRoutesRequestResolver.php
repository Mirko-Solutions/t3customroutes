<?php

declare(strict_types=1);

namespace Mirko\T3customroutes\Middleware;

use Throwable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Mirko\T3customroutes\Dispatcher\Bootstrap;

class CustomRoutesRequestResolver implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (is_array($request->getQueryParams()) && array_key_exists(
                'CustomRoutesResource',
                $request->getQueryParams()
            )) {
            return GeneralUtility::makeInstance(ObjectManager::class)
                ->get(Bootstrap::class)
                ->process($this->cleanupRequest($request));
        }
        return $handler->handle($request);
    }

    /**
     * Removes `t3customRoutesResource` query parameter as it may break further functionality.
     * This parameter is needed only to reach a handler - further processing should not rely on it.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    private function cleanupRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $cleanedQueryParams = $request->getQueryParams();
        unset($cleanedQueryParams['CustomRoutesResource']);

        return $request->withQueryParams($cleanedQueryParams);
    }
}
