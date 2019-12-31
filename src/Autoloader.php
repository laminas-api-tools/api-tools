<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools;

use Laminas\Loader\StandardAutoloader;

/**
 * Extends StandardAutoloader to remove "_" as a directory separator
 */
class Autoloader extends StandardAutoloader
{
    /**
     * Transform the class name to a filename
     *
     * Unlike the StandardAutoloader, this class does not transform an "_"
     * character into a directory separator; in all other ways, however, it
     * acts the same.
     *
     * @param  string $class
     * @param  string $directory
     * @return string
     */
    protected function transformClassNameToFilename($class, $directory)
    {
        // $class may contain a namespace portion, in  which case we need
        // to preserve any underscores in that portion.
        $matches = [];
        preg_match('/(?P<namespace>.+\\\)?(?P<class>[^\\\]+$)/', $class, $matches);

        $class     = (isset($matches['class'])) ? $matches['class'] : '';
        $namespace = (isset($matches['namespace'])) ? $matches['namespace'] : '';

        return $directory
             . str_replace(self::NS_SEPARATOR, '/', $namespace)
             . $class
             . '.php';
    }
}
