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

    public function match(CriteriaInterface $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        $query = $this->doMatch($criteria, $orderBy, $limit, $offset);

        return $query->getResult();
    }

    public function paginate(CriteriaInterface $criteria = null, int $page = 1, array $orderBy = null)
    {
        $query = $this->doMatch($criteria, $orderBy);

        return $this->createPaginator($query, $page);
    }

    protected function addDefaultOrderBy(array $orderBy = null)
    {
        if ($this->defaultOrderBy) {
            $orderBy = $orderBy === null ? $this->defaultOrderBy : array_merge($orderBy, $this->defaultOrderBy);
        }

        return $orderBy;
    }

    protected function createPaginator(Query $query, int $page = 1): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));

        $paginator->setMaxPerPage($this->itemsPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    protected function doMatch(CriteriaInterface $criteria = null, array $orderBy = null, $limit = null, $offset = null): Query
    {
        $builder = $this->createQueryBuilder('entity');

        if ($criteria) {
            if (!$criteria->supports($this->getEntityName())) {
                throw new \InvalidArgumentException(sprintf('Unsupported criteria specified for entity "%s".', $this->getEntityName()));
            }

            $expression = $criteria->match($builder, 'entity');
            $builder->where($expression);
        }

        $orderBy = $this->addDefaultOrderBy($orderBy);

        if ($orderBy !== null) {
            foreach ($orderBy as $fieldName => $direction) {
                $field = $this->getFieldName('entity', $fieldName);

                $builder->addOrderBy($field, $direction);
            }
        }

        if ($limit !== null) {
            $builder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $builder->setFirstResult($offset);
        }

        $query = $builder->getQuery();

        if ($criteria) {
            $criteria->modify($query);
        }

        return $query;
    }

    protected function getFieldName($alias, $field): string
    {
        return implode('.', [$alias, $field]);
    }
}
