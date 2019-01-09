<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 19.05.2018
 * Time: 21:31
 */

namespace AppBundle\Doctrine;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator, PDO;

class ColumnHydrator extends AbstractHydrator
{
    protected function hydrateAllData()
    {
        return $this->_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}