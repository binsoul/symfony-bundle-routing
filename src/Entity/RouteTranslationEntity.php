<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Routing\Entity;

use BinSoul\Symfony\Bundle\I18n\Entity\LocaleEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a translation of a route.
 *
 * @ORM\Entity()
 * @ORM\Table(
 *     name="route_translation",
 *     uniqueConstraints={
 *        @ORM\UniqueConstraint(columns={"route_id", "locale_id"}),
 *     }
 * )
 */
class RouteTranslationEntity
{
    /**
     * @var int|null ID of the translation
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var RouteEntity Route of the translation
     * @ORM\ManyToOne(targetEntity="\BinSoul\Symfony\Bundle\Routing\Entity\RouteEntity", inversedBy="translations")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $route;

    /**
     * @var LocaleEntity Locale of the translation
     * @ORM\ManyToOne(targetEntity="\BinSoul\Symfony\Bundle\I18n\Entity\LocaleEntity")
     * @ORM\JoinColumn(nullable=false)
     */
    private $locale;

    /**
     * @var string Path segment of the route
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $segment;

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
