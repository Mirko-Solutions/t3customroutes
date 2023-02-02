<?php

declare(strict_types=1);

namespace Mirko\T3customroutes\Dispatcher;

use Throwable;
use ReflectionException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Routing\Route;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Mirko\T3customroutes\Service\RouteService;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Mirko\T3customroutes\Configuration\Configuration;
use Mirko\T3customroutes\Processor\ProcessorInterface;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use Mirko\T3customroutes\Provider\RoutesLoader\RouteProvider;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class Bootstrap
{
    /**
     * @var ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * @var HttpFoundationFactory
     */
    protected HttpFoundationFactory $httpFoundationFactory;

    /**
     * Bootstrap constructor.
     */
    public function __construct()
    {
        $this->httpFoundationFactory = new HttpFoundationFactory();
        $this->response = new Response('php://temp', 200, ['Content-Type' => 'application/ld+json']);
    }

    /**
     * @param ServerRequestInterface $inputRequest
     *
     * @return Response
     * @throws Throwable
     */
    public function process(ServerRequestInterface $inputRequest): ResponseInterface
    {
        try {
            $request = $this->httpFoundationFactory->createRequest($inputRequest);
            $context = (new RequestContext())->fromRequest($request);
            $this->callProcessors($request, $this->response);
            if ($this->isMainEndpointResponseClassDefined() && $this->isContextMatchingMainEndpointRoute($context)) {
                $output = $this->processMainEndpoint();
            } else {
                $output = $this->processOperationByRequest($context, $request);
            }
        } catch (Throwable $throwable) {
            try {
                $message = SymfonyResponse::$statusTexts[SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR];

                if (Environment::getContext()->isDevelopment()) {
                    $message = $throwable->getFile() . ':' . $throwable->getLine() . ' ' . $throwable->getMessage();
                }

                $output = json_encode(
                    [
                        "status" => SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
                        "error" => $message,
                    ],
                    JSON_THROW_ON_ERROR
                );

                $this->response = $this->response->withStatus(
                    SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
                    SymfonyResponse::$statusTexts[SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR]
                );
            } catch (Throwable $throwableSerializationException) {
                throw $throwable;
            }
        }

        $this->response->getBody()->write($output);

        return $this->response;
    }

    /**
     * @return bool
     */
    protected function isMainEndpointResponseClassDefined(): bool
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3customroutes']['mainEndpointResponseClass']);
    }

    /**
     * @param RequestContext $context
     *
     * @return bool
     */
    protected function isContextMatchingMainEndpointRoute(RequestContext $context): bool
    {
        $routes = (new RouteCollection());
        $routes->add('main_endpoint', new Route(RouteService::getFullApiBasePath() . '/'));
        $routes->add('main_endpoint_bis', new Route(RouteService::getFullApiBasePath()));

        try {
            (new UrlMatcher($routes, $context))->match($context->getPathInfo());

            return true;
        } catch (ResourceNotFoundException $resourceNotFoundException) {
        }

        return false;
    }

    /**
     * @return string
     */
    protected function processMainEndpoint(): string
    {
        return GeneralUtility::callUserFunction(
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3customroutes']['mainEndpointResponseClass'],
            $_params,
            $this
        );
    }

    /**
     * @param $matchedRoute
     * @param Request $request
     * @return string
     * @throws ReflectionException|NoSuchCacheException
     */
    protected function processRegisteredEndpoint($matchedRoute, Request $request): string
    {
        $route = RouteProvider::getRouteByName($matchedRoute['_route']);
        $reflection = RouteService::getReflectionForRoute($route);
        $methodName = RouteService::getMethodName($route);
        $reflectionMethod = $reflection->getMethod($methodName);
        $params = [];

        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            $reflectionParameterName = $reflectionParameter->getName();
            if (array_key_exists($reflectionParameter->getName(), $matchedRoute)) {
                $value = $matchedRoute[$reflectionParameterName];
                $params[$reflectionParameter->getPosition()] = $value;
            }
        }

        $classObj = GeneralUtility::makeInstance(RouteService::getControllerName($route));

        $callable = [$classObj, $methodName];
        if (is_callable($callable)) {
            $response = call_user_func_array($callable, $params);

            return (string)$response;
        }

        throw new \InvalidArgumentException(
            'No method name \'' . $methodName . '\' in class ' . RouteService::getControllerName($route), 1294585865
        );
    }

    /**
     * @param RequestContext $requestContext
     * @param Request $request
     * @return string
     * @throws ReflectionException|NoSuchCacheException
     */
    public function processOperationByRequest(
        RequestContext $requestContext,
        Request $request,
    ): string {
        try {
            $matchedRoute = (new UrlMatcher(RouteProvider::getConfiguredRoutes(), $requestContext))
                ->matchRequest($request);
            return $this->processRegisteredEndpoint(
                $matchedRoute,
                $request
            );
        } catch (ResourceNotFoundException|MethodNotAllowedException $resourceNotFoundException) {
            // do not stop - continue to find correct route
        }

        throw new RouteNotFoundException("RouteNotFound");
    }

    /**
     * @param Request $request
     * @param ResponseInterface $response
     * @return void
     */
    protected function callProcessors(Request $request, ResponseInterface $response): void
    {
        array_filter(
            Configuration::getProcessors(),
            static function (string $processorClass) use ($request, &$response) {
                if (!is_subclass_of($processorClass, ProcessorInterface::class, true)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Process `%s` needs to be an instance of `%s`',
                            $processorClass,
                            ProcessorInterface::class
                        ),
                        1603705384
                    );
                }
                /** @var ProcessorInterface $processor */
                $processor = GeneralUtility::makeInstance($processorClass);
                $processor->process($request, $response);
            }
        );
    }
}