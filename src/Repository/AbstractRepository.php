<?php

namespace App\Repository;

use App\Criteria\CriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

abstract class AbstractRepository extends ServiceEntityRepository
{
    protected $defaultOrderBy = null;
    protected $itemsPerPage = 50;

    public function findBy(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        $orderBy = array_merge($orderBy ?? [], $this->defaultOrderBy ?? []);

        return parent::findBy($criteria ?? [], $orderBy, $limit, $offset);
    }

    public function findOneBy(array $criteria = null, array $orderBy = null)
    {
        $orderBy = array_merge($orderBy ?? [], $this->defaultOrderBy ?? []);

        return parent::findOneBy($criteria ?? [], $orderBy);
    }

    protected function createPaginator(Query $query, int $page = 1): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));

        $paginator->setMaxPerPage($this->itemsPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }
}
