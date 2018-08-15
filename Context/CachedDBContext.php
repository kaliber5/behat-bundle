<?php

namespace Kaliber5\BehatBundle\Context;

use Kaliber5\BehatBundle\ContextTraits\DBCacheTrait;

/**
 * Class CachedDBContext
 *
 * This uses cached databasefiles
 *
 * @package Kaliber5\BehatBundle\Context
 */
class CachedDBContext extends DBContext
{
    use DBCacheTrait;

    /**
     * @param $names
     *
     * @return mixed|void
     */
    public function loadFile($names)
    {
        if (self::$renameDb === false) {
            parent::loadFile($names);
            return;
        }
        if (!is_array($names)) {
            $names = [$names];
        }
        $key = implode('', $names);
        if ($this->getCachedDbState($key)) {
            return;
        }
        parent::loadFile($names);
        $this->cacheDbState($key);
    }

    /**
     * @return string
     */
    protected function getDbName()
    {
        return $this->getDatabaseName();
    }
}
