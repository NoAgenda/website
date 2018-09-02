<?php

namespace App\Criteria;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractCriteria implements CriteriaInterface
{
    public function match(QueryBuilder $builder, $alias)
    {
        return null;
    }

    public function modify(Query $query)
    {

    }

    public function supports($className): bool
    {
        return true;
    }

    protected function getFieldName($alias, $field)
    {
        return implode('.', [$alias, $field]);
    }
}
