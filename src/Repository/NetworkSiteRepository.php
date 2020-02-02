<?php

namespace App\Repository;

use App\Entity\NetworkSite;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NetworkSite|null find($id, $lockMode = null, $lockVersion = null)
 * @method NetworkSite|null findOneBy(array $criteria, array $orderBy = null)
 * @method NetworkSite[]    findAll()
 * @method NetworkSite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NetworkSiteRepository extends AbstractRepository
{
    protected $defaultOrderBy = [
        'priority' => 'asc',
        'name' => 'asc',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetworkSite::class);
    }

    public function getHomepageSites()
    {
        $results = $this->findBy(null, null, 12);

        return $results;
    }
}
