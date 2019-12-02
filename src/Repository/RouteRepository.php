<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Routing\Repository;

use BinSoul\Symfony\Bundle\Doctrine\Repository\AbstractRepository;
use BinSoul\Symfony\Bundle\Routing\Entity\RouteEntity;
use BinSoul\Symfony\Bundle\Website\Entity\WebsiteEntity;
use Doctrine\Common\Persistence\ManagerRegistry;

class RouteRepository extends AbstractRepository
{
    /**
     * Constructs an instance of this class.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(RouteEntity::class, $registry);
    }

    /**
     * @return RouteEntity[]
     */
    public function loadAll(): array
    {
        /** @var RouteEntity[] $result */
        $result = $this->getRepository()->findBy([]);

        return $result;
    }

    public function load(int $id): ?RouteEntity
    {
        /** @var RouteEntity|null $result */
        $result = $this->getRepository()->find($id);

        return $result;
    }

    /**
     * @return RouteEntity[]
     */
    public function findAllByWebsite(WebsiteEntity $website): array
    {
        return $this->getRepository()->findBy(['website' => $website]);
    }

    /**
     * @return RouteEntity[]
     */
    public function findAllByParent(RouteEntity $route): array
    {
        return $this->getRepository()->findBy(['parent' => $route]);
    }

    public function findByWebsiteAndName(WebsiteEntity $website, string $name): ?RouteEntity
    {
        /** @var RouteEntity|null $result */
        $result = $this->getRepository()->findOneBy(['website' => $website, 'name' => $name]);

        return $result;
    }

    public function save(RouteEntity $entity, bool $flush = true): void
    {
        $manager = $this->getManager();
        $manager->persist($entity);

        if ($flush) {
            $manager->flush($entity);
        }
    }
}
