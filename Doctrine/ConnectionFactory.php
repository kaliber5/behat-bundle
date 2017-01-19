<?php

namespace Kaliber5\BehatBundle\Doctrine;

use \Doctrine\Bundle\DoctrineBundle\ConnectionFactory as BaseConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;

/**
 * Class ConnectionFactory
 *
 * This class expose Methods to change the configured DB Name
 *
 * @package Kaliber5\BehatBundle\Doctrine
 */
class ConnectionFactory extends BaseConnectionFactory
{
    /**
     * set the new db name static to stay alive between reboots
     *
     * @var string
     */
    private static $dbName = '';

    /**
     * {@inheritDoc}
     */
    public function createConnection(
        array $params,
        Configuration $config = null,
        EventManager $eventManager = null,
        array $mappingTypes = []
    ) {
        if (self::$dbName === '') {
            $this->createDbName();
        }

        $params['dbname'] = substr($params['dbname'], 0, strrpos($params['dbname'], '/') + 1).$this->getDbName();
        $params['path'] = $params['dbname'];

        return parent::createConnection(
            $params,
            $config,
            $eventManager,
            $mappingTypes
        );
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return self::$dbName;
    }

    /**
     * @param string $dbName
     */
    public function setDbName($dbName)
    {
        self::$dbName = $dbName;
    }

    /**
     * creates an custom db name
     */
    protected function createDbName()
    {
        self::$dbName = substr(md5(microtime()), 0, 40);
    }
}
