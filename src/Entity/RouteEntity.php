<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Routing\Entity;

use BinSoul\Symfony\Bundle\Website\Entity\WebsiteEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a route.
 *
 * @ORM\Entity()
 * @ORM\Table(
 *     name="route",
 *     uniqueConstraints={
 *        @ORM\UniqueConstraint(columns={"website_id", "parent_id", "segment"}),
 *        @ORM\UniqueConstraint(columns={"website_id", "name"}),
 *     }
 * )
 */
class RouteEntity
{
    /**
     * @var int|null ID of the route
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var WebsiteEntity Website of the route
     * @ORM\ManyToOne(targetEntity="\BinSoul\Symfony\Bundle\Website\Entity\WebsiteEntity")
     * @ORM\JoinColumn(nullable=false)
     */
    private $website;

    /**
     * @var RouteEntity|null Parent of the route
     * @ORM\ManyToOne(targetEntity="\BinSoul\Symfony\Bundle\Routing\Entity\RouteEntity")
     * @ORM\JoinColumn(nullable=true)
     */
    private $parent;

    /**
     * @var string Name of the route
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string Path segment of the route
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $segment;

    /**
     * @var string|null Controller of the route
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $controller;

    /**
     * @var array|null
     * @ORM\Column(type="json", nullable=true, length=1024)
     */
    private $parameters;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     */
    private $isVisible = true;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     */
    private $isIndexable = true;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     */
    private $isFollowable = true;

    /**
     * @var RouteTranslationEntity[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="\BinSoul\Symfony\Bundle\Routing\Entity\RouteTranslationEntity", mappedBy="route")
     */
    private $translations;

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

    public function getParent(): ?RouteEntity
    {
        return $this->parent;
    }

    public function setParent(?RouteEntity $parent): void
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

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

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
     * @return RouteTranslationEntity[]|ArrayCollection
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }
}
