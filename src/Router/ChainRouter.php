<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Routing\Router;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Combines several routers in a defined order.
 */
class ChainRouter implements RouterInterface, RequestMatcherInterface, WarmableInterface
{
    private RequestContext $context;

    /**
     * @var array<int, array<int, RouterInterface|RequestMatcherInterface|UrlGeneratorInterface>>
     */
    private array $routers = [];

    /**
     * Constructs an instance of this class.
     */
    public function __construct(?RequestContext $context = null)
    {
        $this->context = $context ?: new RequestContext();
    }

    public function addRouter(RouterInterface|RequestMatcherInterface|UrlGeneratorInterface $router, int $priority = 0): void
    {
        if (! $router instanceof RouterInterface && ! ($router instanceof RequestMatcherInterface && $router instanceof UrlGeneratorInterface)
        ) {
            throw new InvalidArgumentException(sprintf('%s is not a valid router.', $router::class));
        }

        if (! isset($this->routers[$priority])) {
            $this->routers[$priority] = [];
            krsort($this->routers);
        }

        $this->routers[$priority][] = $router;
    }

    public function getContext(): RequestContext
    {
        return $this->context;
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function match(string $pathinfo): array
    {
        $request = $this->rebuildRequest($pathinfo);

        return $this->handleMatch($pathinfo, $request);
    }

    /**
     * @return array<string, mixed>
     */
    public function matchRequest(Request $request): array
    {
        return $this->handleMatch($request->getPathInfo(), $request);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generate(string $name, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        foreach ($this->routers as $routers) {
            foreach ($routers as $router) {
                if (! ($router instanceof RouterInterface)) {
                    continue;
                }

                try {
                    $router->setContext($this->context);

                    return $router->generate($name, $parameters, $referenceType);
                } catch (RouteNotFoundException) {
                    // ignore
                }
            }
        }

        throw new RouteNotFoundException(sprintf('None of the routers in the chain generated route "%s".', $name));
    }

    public function getRouteCollection(): RouteCollection
    {
        $routeCollection = new RouteCollection();

        foreach ($this->routers as $routers) {
            foreach ($routers as $router) {
                if (! ($router instanceof RouterInterface)) {
                    continue;
                }

                $router->setContext($this->getContext());

                $routeCollection->addCollection($router->getRouteCollection());
            }
        }

        return $routeCollection;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        foreach ($this->routers as $routers) {
            foreach ($routers as $router) {
                if ($router instanceof WarmableInterface) {
                    $router->warmUp($cacheDir, $buildDir);
                }
            }
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleMatch(string $pathInfo, Request $request): array
    {
        $methodNotAllowed = null;

        foreach ($this->routers as $routers) {
            foreach ($routers as $router) {
                if (! ($router instanceof RouterInterface)) {
                    continue;
                }

                try {
                    $router->setContext($this->getContext());

                    if ($router instanceof RequestMatcherInterface) {
                        return $router->matchRequest($request);
                    }

                    return $router->match($pathInfo);
                } catch (RouteNotFoundException) {
                    // ignore
                } catch (MethodNotAllowedException $methodNotAllowedException) {
                    $methodNotAllowed = $methodNotAllowedException;
                }
            }
        }

        throw $methodNotAllowed ?: new RouteNotFoundException(sprintf('None of the routers in the chain matched "%s".', $pathInfo));
    }

    private function rebuildRequest(string $pathInfo): Request
    {
        $context = $this->getContext();

        $uri = $pathInfo;

        $server = [];

        if ($context->getBaseUrl() !== '') {
            $uri = $context->getBaseUrl() . $pathInfo;
            $server['SCRIPT_FILENAME'] = $context->getBaseUrl();
            $server['PHP_SELF'] = $context->getBaseUrl();
        }

        $host = $context->getHost() ?: 'localhost';

        if ($context->getScheme() === 'https' && $context->getHttpsPort() !== 443) {
            $host .= ':' . $context->getHttpsPort();
        }

        if ($context->getScheme() === 'http' && $context->getHttpPort() !== 80) {
            $host .= ':' . $context->getHttpPort();
        }

        $uri = $context->getScheme() . '://' . $host . $uri . '?' . $context->getQueryString();

        return Request::create($uri, $context->getMethod(), $context->getParameters(), [], [], $server);
    }
}
