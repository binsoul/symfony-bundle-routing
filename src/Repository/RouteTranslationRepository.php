<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Routing\Repository;

use BinSoul\Symfony\Bundle\Doctrine\Repository\AbstractRepository;
use BinSoul\Symfony\Bundle\I18n\Entity\LocaleEntity;
use BinSoul\Symfony\Bundle\Routing\Entity\RouteEntity;
use BinSoul\Symfony\Bundle\Routing\Entity\RouteTranslationEntity;
use Doctrine\Persistence\ManagerRegistry;

class RouteTranslationRepository extends AbstractRepository
{
    /**
     * Constructs an instance of this class.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(RouteTranslationEntity::class, $registry);
    }

    /**
     * @return RouteTranslationEntity[]
     */
    public function loadAll(): array
    {
        /** @var RouteTranslationEntity[] $result */
        $result = $this->getRepository()->findBy([]);

        return $result;
    }

    public function load(int $id): ?RouteTranslationEntity
    {
        /** @var RouteTranslationEntity|null $result */
        $result = $this->getRepository()->find($id);

        return $result;
    }

    /**
     * @return RouteTranslationEntity[]
     */
    public function findAllByRoute(RouteEntity $route): array
    {
        return $this->getRepository()->findBy(['route' => $route]);
    }

    /**
     * @return RouteTranslationEntity[]
     */
    public function findAllByLocale(LocaleEntity $locale): array
    {
        return $this->getRepository()->findBy(['locale' => $locale]);
    }

    public function findByRouteAndLocale(RouteEntity $route, LocaleEntity $locale): ?RouteTranslationEntity
    {
        /** @var RouteTranslationEntity|null $result */
        $result = $this->getRepository()->findOneBy(['route' => $route, 'locale' => $locale]);

        return $result;
    }

    public function save(RouteTranslationEntity $entity, bool $flush = true): void
    {
        $manager = $this->getManager();
        $manager->persist($entity);

        if ($flush) {
            $manager->flush($entity);
        }
    }
}
