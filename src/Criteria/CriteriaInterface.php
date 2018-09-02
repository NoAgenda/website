<?php

namespace App\Criteria;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * Criteria modify a Doctrine query through the Specification pattern.
 */
interface CriteriaInterface
{
    public function match(QueryBuilder $builder, $alias);

    public function modify(Query $query);

    public function supports($entityClass): bool;
}
