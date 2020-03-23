<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Routing\Router;

use BinSoul\Symfony\Bundle\I18n\Entity\LocaleEntity;
use BinSoul\Symfony\Bundle\Routing\Entity\RouteEntity;
use BinSoul\Symfony\Bundle\Routing\Entity\RouteTranslationEntity;
use BinSoul\Symfony\Bundle\Routing\Repository\RouteRepository;
use BinSoul\Symfony\Bundle\Routing\Repository\RouteTranslationRepository;
use BinSoul\Symfony\Bundle\Website\Entity\DomainEntity;
use BinSoul\Symfony\Bundle\Website\Entity\WebsiteEntity;
use BinSoul\Symfony\Bundle\Website\Repository\DomainRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Uses the {@see RouteRepository} to generate and match paths.
 */
class DatabaseRouter implements RouterInterface, RequestMatcherInterface
{
    /**
     * @var RouteRepository
     */
    private $routeRepository;
    /**
     * @var RouteTranslationRepository
     */
    private $routeTranslationRepository;
    /**
     * @var DomainRepository
     */
    private $domainRepository;
    /**
     * @var RouteEntity[][]
     */
    private $routes = [];
    /**
     * @var RouteTranslationEntity[][][]
     */
    private $translations = [];
    /**
     * @var DomainEntity[]
     */
    private $domains = [];
    /**
     * @var RequestContext
     */
    private $context;

    /**
     * Constructs an instance of this class.
     */
    public function __construct(
        RouteRepository $routeRepository,
        RouteTranslationRepository $routeTranslationRepository,
        DomainRepository $domainRepository,
        RequestContext $context = null
    ) {
        $this->domainRepository = $domainRepository;
        $this->routeRepository = $routeRepository;
        $this->routeTranslationRepository = $routeTranslationRepository;
        $this->context = $context ?: new RequestContext();
    }

    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        /** @var DomainEntity|null $domain */
        $domain = $parameters['domain'] ?? $this->getContext()->getParameter('domain');
        if ($domain === null || !($domain instanceof DomainEntity)) {
            throw new RouteNotFoundException('Domain parameter not set.');
        }

        /** @var LocaleEntity|null $locale */
        $locale = $parameters['locale'] ?? $this->getContext()->getParameter('locale');
        if ($locale === null || !($locale instanceof LocaleEntity)) {
            throw new RouteNotFoundException('Locale parameter not set.');
        }

        $website = $domain->getWebsite();

        if ($website->getLocaleType() === WebsiteEntity::LOCALE_TYPE_SUBDOMAIN) {
            foreach ($this->loadDomains() as $domainEntity) {
                $domainWebsite = $domainEntity->getWebsite();
                $domainLocale = $domainEntity->getDefaultLocale();

                if (!$domainLocale) {
                    continue;
                }

                if ($domainWebsite->getId() === $website->getId() && $domainLocale->getId() === $locale->getId()) {
                    $domain = $domainEntity;

                    break;
                }
            }
        }

        $targetRoute = null;

        $routes = $this->loadRoutes($website);
        foreach ($routes as $route) {
            if ($route->getName() === $name) {
                $targetRoute = $route;

                break;
            }
        }

        if ($targetRoute === null) {
            throw new RouteNotFoundException(sprintf('No route found with name "%s".', $name));
        }

        $translations = $this->loadTranslations($website, $locale);
        $url = [$translations[$targetRoute->getId()]->getSegment()];

        while ($targetRoute->getParent() !== null) {
            $targetRoute = $targetRoute->getParent();

            $url[] = $translations[$targetRoute->getId()]->getSegment();
        }

        array_pop($url);
        $url = array_reverse($url);

        if ($website->getLocaleType() === WebsiteEntity::LOCALE_TYPE_PATH && $locale->getId() !== $website->getDefaultLocale()->getId()) {
            if (\count($url) === 0) {
                $url = [$locale->getCode(), ''];
            } elseif (\count($url) > 0 && $url[0] !== $locale->getCode()) {
                array_unshift($url, $locale->getCode());
            }
        }

        $path = implode('/', $url);

        $result = $domain->getUrl().'/'.ltrim($path, '/');
        $query = [];
        foreach ($parameters as $key => $value) {
            if (strpos($result, '{'.$key.'}') !== false) {
                if (\is_object($value) || \is_array($value)) {
                    continue;
                }

                $result = str_replace('{'.$key.'}', $value, $result);
            } else {
                $query[$key] = $value;
            }
        }

        return $result.(\count($query) ? '?'.http_build_query($query) : '');
    }

    public function matchRequest(Request $request)
    {
        /** @var DomainEntity|null $domain */
        $domain = $request->attributes->get('domain') ?? $this->getContext()->getParameter('domain');
        if ($domain === null || !($domain instanceof DomainEntity)) {
            throw new RouteNotFoundException('Domain parameter not set.');
        }

        /** @var LocaleEntity|null $locale */
        $locale = $request->attributes->get('locale') ?? $this->getContext()->getParameter('locale') ?? $domain->getWebsite()->getDefaultLocale();
        if ($locale === null || !($locale instanceof LocaleEntity)) {
            throw new RouteNotFoundException('Locale parameter not set.');
        }

        $path = $request->getPathInfo();
        $path = preg_replace('/\/+/', '/', $path);
        $path = trim((string) $path, '/');

        $website = $domain->getWebsite();

        $domainPath = parse_url($domain->getUrl(), PHP_URL_PATH) ?? '';
        $domainPath = trim($domainPath, '/');
        if (stripos($path, $domainPath) === 0) {
            $path = substr($path, strlen($domainPath));
            $path = preg_replace('/\/+/', '/', $path);
            $path = trim((string) $path, '/');
        }

        $parts = explode('/', $path);
        if ($website->getLocaleType() === WebsiteEntity::LOCALE_TYPE_PATH && $parts[0] === $locale->getCode()) {
            array_shift($parts);
        }

        if (!isset($parts[0]) || $parts[0] !== '') {
            array_unshift($parts, '');
        }

        $translations = $this->loadTranslations($website, $locale);

        $parentID = null;
        /** @var RouteTranslationEntity|null $route */
        $route = null;
        /** @var RouteTranslationEntity|null $wildCardRoute */
        $wildCardRoute = null;
        $wildCardParameters = [];

        while (\count($parts) > 0) {
            $isMatch = false;
            $wildCardRoute = null;
            foreach ($translations as $translation) {
                /** @var RouteTranslationEntity $translation */
                $parent = $translation->getRoute()->getParent();
                if ($parent === null) {
                    $targetID = null;
                } else {
                    $targetID = $parent->getId();
                }

                if ($targetID !== $parentID) {
                    continue;
                }

                $url = rtrim($translation->getSegment(), '/');
                if ($url !== '' && strpos($url, '{') === 0) {
                    $wildCardRoute = $translation;
                } elseif ($url === $parts[0]) {
                    $route = $translation;
                    $parentID = $translation->getRoute()->getId();
                    array_shift($parts);
                    $isMatch = true;

                    break;
                }
            }

            if ($isMatch) {
                continue;
            }

            if ($wildCardRoute) {
                $route = $wildCardRoute;
                $parentID = $wildCardRoute->getRoute()->getId();
                $wildCardParameters[trim($wildCardRoute->getSegment(), '/{}')] = $parts[0];
                array_shift($parts);
            } else {
                $route = null;

                break;
            }
        }

        if ($route !== null && $route->getRoute()->getController() !== '') {
            return array_merge(
                [
                    '_controller' => $route->getRoute()->getController(),
                    '_route' => $route->getRoute()->getName(),
                    'website' => $website,
                    'domain' => $domain,
                    'locale' => $locale,
                    'route' => $route->getRoute(),
                    'unmatchedPath' => $parts,
                ],
                $wildCardParameters
            );
        }

        throw new RouteNotFoundException(sprintf('No route found with path "%s".', $request->getPathInfo()));
    }

    public function match($pathInfo)
    {
        return $this->matchRequest($this->rebuildRequest($pathInfo));
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    public function getContext(): RequestContext
    {
        return $this->context;
    }

    public function getRouteCollection(): RouteCollection
    {
        return new RouteCollection();
    }

    /**
     * @return RouteEntity[]
     */
    private function loadRoutes(WebsiteEntity $website): array
    {
        if (!isset($this->routes[$website->getId()])) {
            $this->routes[$website->getId()] = [];

            $routes = $this->routeRepository->findAllByWebsite($website);
            foreach ($routes as $route) {
                $this->routes[$website->getId()][$route->getId()] = $route;
            }
        }

        return $this->routes[$website->getId()];
    }

    /**
     * @return RouteTranslationEntity[]
     */
    private function loadTranslations(WebsiteEntity $website, LocaleEntity $locale): array
    {
        if (!isset($this->translations[$website->getId()])) {
            $this->translations[$website->getId()] = [];
        }

        if (!isset($this->translations[$website->getId()][$locale->getId()])) {
            $this->translations[$website->getId()][$locale->getId()] = [];

            $routes = $this->loadRoutes($website);
            $translations = $this->routeTranslationRepository->findAllByLocale($locale);
            $translationsByRoute = [];
            foreach ($translations as $translation) {
                if (isset($routes[$translation->getRoute()->getId()])) {
                    $translationsByRoute[$translation->getRoute()->getId()] = $translation;
                }
            }

            foreach ($routes as $route) {
                if (!isset($translationsByRoute[$route->getId()])) {
                    $entity = new RouteTranslationEntity();
                    $entity->setLocale($locale);
                    $entity->setRoute($route);
                    $entity->setSegment($route->getSegment());
                    $translationsByRoute[$route->getId()] = $entity;
                }
            }

            $this->translations[$website->getId()][$locale->getId()] = $translationsByRoute;
        }

        return $this->translations[$website->getId()][$locale->getId()];
    }

    /**
     * @return DomainEntity[]
     */
    private function loadDomains(): array
    {
        if (\count($this->domains) === 0) {
            $this->domains = $this->domainRepository->loadAll();
        }

        return $this->domains;
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
