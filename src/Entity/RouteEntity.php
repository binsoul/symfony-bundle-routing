<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Routing\Entity;

use BinSoul\Symfony\Bundle\Website\Entity\WebsiteEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a route.
 */
#[ORM\Entity]
#[ORM\Table(name: 'route')]
#[ORM\UniqueConstraint(columns: ['website_id', 'parent_id', 'segment'])]
#[ORM\UniqueConstraint(columns: ['website_id', 'name'])]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class RouteEntity
{
    /**
     * @var int|null ID of the route
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    /**
     * @var WebsiteEntity Website of the route
     */
    #[ORM\ManyToOne(targetEntity: WebsiteEntity::class)]
    #[ORM\JoinColumn(nullable: false)]
    private WebsiteEntity $website;

    /**
     * @var RouteEntity|null Parent of the route
     */
    #[ORM\ManyToOne(targetEntity: RouteEntity::class)]
    #[ORM\JoinColumn]
    private ?self $parent = null;

    /**
     * @var string Name of the route
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    /**
     * @var string Path segment of the route
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $segment;

    /**
     * @var string|null Controller of the route
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $controller = null;

    /**
     * @var array|null
     */
    #[ORM\Column(type: Types::JSON, length: 1024, nullable: true)]
    private ?array $parameters = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isVisible = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isIndexable = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isFollowable = true;

    /**
     * @var RouteTranslationEntity[]|Collection<int, RouteTranslationEntity>
     */
    #[ORM\OneToMany(mappedBy: 'route', targetEntity: RouteTranslationEntity::class)]
    private Collection $translations;

    /**
     * Constructs an instance of this class.
     */
    public function __construct(?int $id = null)
    {
        $this->id = $id;
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWebsite(): WebsiteEntity
    {
        return $this->website;
    }

    public function setWebsite(WebsiteEntity $website): void
    {
        $this->website = $website;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSegment(): string
    {
        return $this->segment;
    }

    public function setSegment(string $segment): void
    {
        $this->segment = trim($segment, '/');
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function setController(?string $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * @return array|null
     */
    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    /**
     * @param array|null $parameters
     */
    public function setParameters(?array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): void
    {
        $this->isVisible = $isVisible;
    }

    public function isIndexable(): bool
    {
        return $this->isIndexable;
    }

    public function setIsIndexable(bool $isIndexable): void
    {
        $this->isIndexable = $isIndexable;
    }

    public function isFollowable(): bool
    {
        return $this->isFollowable;
    }

    public function setIsFollowable(bool $isFollowable): void
    {
        $this->isFollowable = $isFollowable;
    }

    /**
     * @return RouteTranslationEntity[]|Collection<int, RouteTranslationEntity>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }
}
