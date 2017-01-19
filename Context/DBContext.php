<?php

namespace Kaliber5\BehatBundle\Context;

use Fidry\AliceBundleExtension\Context\Doctrine\AliceORMContext;
use Kaliber5\BehatBundle\Doctrine\ConnectionFactory;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class DBContext
 *
 * This context sets a new DB Name on init for parallel processing
 *
 * @package Kaliber5\BehatBundle\Context
 */
class DBContext extends AliceORMContext
{

    private $dbname = '';

    /**
     * {@inheritdoc}
     */
    public function setKernel(KernelInterface $kernel)
    {
        /** @var ConnectionFactory $factory */
        $factory = $kernel->getContainer()->get('doctrine.dbal.connection_factory');
        if ($factory instanceof ConnectionFactory) {
            $this->setDatabaseName($factory->getDbName());
            $factory->setDbName($this->getDatabaseName());
        } else {
            throw new \Exception(get_class($factory));
        }

        return parent::setKernel($kernel);
    }

    /**
     * @return string
     */
    public function getDatabaseName()
    {
        if ($this->dbname === '') {
            $this->dbname = substr(md5(microtime()), 0, 40);
        }

        return $this->dbname;
    }

    /**
     * @param string $dbname
     */
    public function setDatabaseName($dbname)
    {
        $this->dbname = $dbname;
    }
}
