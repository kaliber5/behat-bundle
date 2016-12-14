<?php

namespace Kaliber5\BehatBundle\ContextTraits;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DBCacheTrait
 *
 * Saves the current db file in the cache folder
 *
 * Only use this with sqlite
 *
 * @package Kaliber5\BehatBundle\ContextTraits
 */
trait DBCacheStubTrait
{

    /**
     * Copy the db state into a file
     *
     * @param $key
     *
     * @return bool
     */
    protected function cacheDbState($key)
    {
        return true;
    }

    /**
     * load the db state from a file if exists
     *
     * @param $key
     *
     * @return bool true if a cache file exists, false otherwise
     */
    protected function getCachedDbState($key)
    {
        return false;
    }

    /**
     * Fix the key, so you can use the __METHOD__ var as a key
     *
     * @param $key
     *
     * @return mixed
     */
    protected function fixCacheKey($key)
    {
        if (strpos($key, '::') !== false) {
            return preg_replace('/.*::/', '', $key);
        } else {
            return $key;
        }
    }

    /**
     * returns the path to the db
     *
     * @return string
     */
    protected function getDbPath()
    {
        return $this->getContainer()->getParameter('kernel.cache_dir').DIRECTORY_SEPARATOR;
    }

    /**
     * this method have to return the file of the testdb
     *
     * @return string
     */
    abstract protected function getDbName();

    /**
     * this method have to return the service container
     *
     * @return ContainerInterface
     */
    abstract protected function getContainer();
}
