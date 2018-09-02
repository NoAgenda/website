<?php

namespace App\Criteria;

use App\Entity\Episode;
use Doctrine\ORM\QueryBuilder;

class SpecialEpisodes extends AbstractCriteria
{
    public function match(QueryBuilder $builder, $alias)
    {
        return $builder->expr()->eq($this->getFieldName($alias, 'special'), true);
    }

    public function supports($className): bool
    {
        return $className === Episode::class;
    }
}
