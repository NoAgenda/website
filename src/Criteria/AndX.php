<?php

namespace App\Criteria;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

class AndX extends AbstractCriteria
{
    /**
     * @var CriteriaInterface[]
     */
    private $children;

    public function __construct(...$children)
    {
        $this->children = $children;
    }

    public function match(QueryBuilder $builder, $alias)
    {
        return call_user_func_array(
            [$builder->expr(), 'andX'],
            array_map(
                function (CriteriaInterface $criteria) use ($builder, $alias) {
                    return $criteria->match($builder, $alias);
                },
                $this->children
            )
        );
    }

    public function modify(Query $query)
    {
        foreach ($this->children as $child) {
            $child->modify($query);
        }
    }
}
