<?php

namespace BinSoul\Symfony\Bundle\Routing\Router;

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
    /**
     * @var RequestContext
     */
    private $context;
    /**
     * @var RouterInterface[][]
     */
    private $routers = [];

    /**
     * Constructs an instance of this class.
     */
    public function __construct(RequestContext $context = null)
    {
        $this->context = $context ?: new RequestContext();
    }

    /**
     * @param RouterInterface|RequestMatcherInterface|UrlGeneratorInterface $router
     * @param int                                                           $priority
     */
    public function addRouter($router, $priority = 0)
    {
        if (!$router instanceof RouterInterface && !($router instanceof RequestMatcherInterface && $router instanceof UrlGeneratorInterface)
        ) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid router.', get_class($router)));
        }

        if (!isset($this->routers[$priority])) {
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

    public function match($pathInfo): array
    {
        $request = $this->rebuildRequest($pathInfo);

        return $this->handleMatch($pathInfo, $request);
    }

    public function matchRequest(Request $request): array
    {
        return $this->handleMatch($request->getPathInfo(), $request);
    }

    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        foreach ($this->routers as $routers) {
            foreach ($routers as $router) {
                try {
                    $router->setContext($this->context);

                    return $router->generate($name, $parameters, $referenceType);
                } catch (RouteNotFoundException $e) {
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
                $router->setContext($this->getContext());

                $routeCollection->addCollection($router->getRouteCollection());
            }
        }

        return $routeCollection;
    }

    public function warmUp($cacheDir): void
    {
        foreach ($this->routers as $routers) {
            foreach ($routers as $router) {
                if ($router instanceof WarmableInterface) {
                    $router->warmUp($cacheDir);
                }
            }
        }
    }

    private function handleMatch(string $pathInfo, Request $request): array
    {
        $methodNotAllowed = null;

        foreach ($this->routers as $routers) {
            foreach ($routers as $router) {
                try {
                    $router->setContext($this->getContext());

                    if ($router instanceof RequestMatcherInterface) {
                        return $router->matchRequest($request);
                    }

                    return $router->match($pathInfo);
                } catch (RouteNotFoundException $e) {
                    // ignore
                } catch (MethodNotAllowedException $e) {
                    $methodNotAllowed = $e;
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
        if ($context->getBaseUrl()) {
            $uri = $context->getBaseUrl().$pathInfo;
            $server['SCRIPT_FILENAME'] = $context->getBaseUrl();
            $server['PHP_SELF'] = $context->getBaseUrl();
        }

        $host = $context->getHost() ?: 'localhost';
        if ($context->getScheme() === 'https' && $context->getHttpsPort() !== 443) {
            $host .= ':'.$context->getHttpsPort();
        }

        if ($context->getScheme() === 'http' && $context->getHttpPort() !== 80) {
            $host .= ':'.$context->getHttpPort();
        }

        $uri = $context->getScheme().'://'.$host.$uri.'?'.$context->getQueryString();

        return Request::create($uri, $context->getMethod(), $context->getParameters(), [], [], $server);
    }
}
