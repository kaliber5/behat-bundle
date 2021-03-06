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
trait DBCacheTrait
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
        $source = $this->getDbPath().$this->getDbName();
        if (file_exists($source)) {
            $target = $this->getDbPath().$this->fixCacheKey($key);
            copy($source, $target);

            return true;
        }

        return false;
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
        $source = $this->getDbPath().$this->fixCacheKey($key);
        if (file_exists($source)) {
            $target = $this->getDbPath().$this->getDbName();
            copy($source, $target);
            $this->getContainer()->get('doctrine.orm.entity_manager')->clear();

            return true;
        }

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
        $url = $this->getContainer()->getParameter('env(DATABASE_URL)');
        $path = substr($url, strrpos($url, $this->getContainer()->getParameter('kernel.project_dir')));
        $path = substr($path, 0, strrpos($path, '/') + 1);

        return $path;
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
