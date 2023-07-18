<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Routing\Entity;

use BinSoul\Symfony\Bundle\I18n\Entity\LocaleEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a translation of a route.
 */
#[ORM\Entity]
#[ORM\Table(name: 'route_translation')]
#[ORM\UniqueConstraint(columns: ['route_id', 'locale_id'])]
class RouteTranslationEntity
{
    /**
     * @var int|null ID of the translation
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    /**
     * @var RouteEntity Route of the translation
     */
    #[ORM\ManyToOne(targetEntity: RouteEntity::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private RouteEntity $route;

    /**
     * @var LocaleEntity Locale of the translation
     */
    #[ORM\ManyToOne(targetEntity: LocaleEntity::class)]
    #[ORM\JoinColumn(nullable: false)]
    private LocaleEntity $locale;

    /**
     * @var string Path segment of the route
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $segment;

    /**
     * Constructs an instance of this class.
     */
    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoute(): RouteEntity
    {
        return $this->route;
    }

    public function setRoute(RouteEntity $route): void
    {
        $this->route = $route;
    }

    public function getLocale(): LocaleEntity
    {
        return $this->locale;
    }

    public function setLocale(LocaleEntity $locale): void
    {
        $this->locale = $locale;
    }

    public function getSegment(): string
    {
        return $this->segment;
    }

    public function setSegment(string $segment): void
    {
        $this->segment = trim($segment, '/');
    }
}
