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

    private static $renameDb = false;

    /**
     * {@inheritDoc}
     */
    public function createConnection(
        array $params,
        Configuration $config = null,
        EventManager $eventManager = null,
        array $mappingTypes = []
    ) {
        if (self::$renameDb) {
            if (self::$dbName === '') {
                $this->createDbName();
            }
            if (isset($params['url'])) {
                $params['url'] = substr($params['url'], 0, strrpos($params['url'], '/') + 1) . $this->getDbName();
            } elseif (isset($params['dbname'])) {
                $params['dbname'] = substr($params['dbname'], 0, strrpos($params['dbname'], '/') + 1) . $this->getDbName();
                $params['path'] = $params['dbname'];
            } else {
                throw new \Exception('There was neither url nor dbname set');
            }
        } else {
            if (isset($params['url'])) {
                $name = substr($params['url'], strrpos($params['url'], '/'));
            } elseif (isset($params['dbname'])) {
                $name = substr($params['dbname'], strrpos($params['dbname'], '/'));
            } else {
                throw new \Exception('There was neither url nor dbname set');
            }
            $this->setDbName($name);
        }

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

    public static function setRenameDb(bool $rename)
    {
        if (self::$renameDb !== $rename) {
            self::$dbName = '';
            self::$renameDb = $rename;
        }
    }

    /**
     * creates an custom db name
     */
    protected function createDbName()
    {
        self::$dbName = substr(md5(microtime()), 0, 40);
    }
}
