<?php

namespace Kaliber5\BehatBundle\Context;

use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Fidry\AliceDataFixtures\Bridge\Doctrine\Persister\ObjectManagerPersister;
use Hautelook\AliceBundle\FixtureLocatorInterface;
use Kaliber5\BehatBundle\Doctrine\ConnectionFactory;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class DBContext
 *
 * This context sets a new DB Name on init for parallel processing
 *
 * @package Kaliber5\BehatBundle\Context
 */
class DBContext
{
    private $dbname = '';

    /**
     * @var SchemaTool
     */
    private $schemaTool;

    /**
     * @var ClassMetadata[]
     */
    private $classes;

    /**
     * @var bool
     */
    protected static $renameDb = false;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ConnectionFactory $factory
     */
    public function __construct(EntityManagerInterface $entityManager, ConnectionFactory $factory)
    {
        if (self::$renameDb) {
            $this->setDatabaseName($factory->getDbName());
            $factory->setDbName($this->getDatabaseName());
        }

        $this->schemaTool = new SchemaTool($entityManager);
        $this->classes = $entityManager->getMetadataFactory()->getAllMetadata();
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

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        $this->schemaTool->createSchema($this->classes);
    }

    /**
     * {@inheritdoc}
     */
    public function dropSchema()
    {
        $this->schemaTool->dropSchema($this->classes);
    }

    /**
     * {@inheritdoc}
     */
    public function emptyDatabase()
    {
        umask(0000);
        $this->dropSchema();
        $this->createSchema();
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function loadFile($names)
    {
        $this->emptyDatabase();
        if (!is_array($names)) {
            $names = [$names];
        }

        $this->loadFixtureFiles($names);
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function appendFile($names)
    {
        if (!is_array($names)) {
            $names = [$names];
        }

        $this->loadFixtureFiles($names);
    }

    /**
     * @BeforeFeature ~@javascript
     *
     * @param BeforeFeatureScope $scope
     */
    public static function setupRenameDb(BeforeFeatureScope $scope)
    {
        self::$renameDb = true;
        ConnectionFactory::setRenameDb(true);
    }

    /**
     * @BeforeFeature @javascript
     *
     * @param BeforeFeatureScope $scope
     */
    public static function setupNotRenameDb(BeforeFeatureScope $scope)
    {
        self::$renameDb = false;
        ConnectionFactory::setRenameDb(false);
    }

    /**
     * @param array $names
     */
    private function loadFixtureFiles(array $names)
    {
        /** @var FixtureLocatorInterface $locator */
        $locator = $this->getContainer()->get('hautelook_alice.locator');
        $fixtures = $locator->locateFiles([], 'test');

        $files = array_filter($fixtures, function (string $filename) use ($names) {
            foreach ($names as $name) {
                if (strpos($filename, '/' . $name . '.yaml') !== false) {
                    return true;
                }
            }

            return false;
        });
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $persister = new ObjectManagerPersister($em);

        /** @var FixtureLocatorInterface $locator */
        $loader = $this->getContainer()->get('hautelook_alice.data_fixtures.append_loader');

        $loader = $loader->withPersister($persister);

        $loader->load($files, $this->getContainer()->getParameterBag()->all());

        $this->getContainer()->get('doctrine.orm.entity_manager')->clear();
    }
}
