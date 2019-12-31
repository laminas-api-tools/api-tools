<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools;

use Laminas\Loader\AutoloaderFactory;
use RuntimeException;

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

/**
 * Test bootstrap, for setting up autoloading
 *
 * @subpackage UnitTest
 */
class Bootstrap
{
    protected static $serviceManager;

    public static function init()
    {
        static::initAutoloader();
    }

    protected static function initAutoloader()
    {
        $vendorPath = static::findParentPath('vendor');

        if (is_readable($vendorPath . '/autoload.php')) {
            $loader = include $vendorPath . '/autoload.php';
            return;
        }

        $laminasPath = getenv('LAMINAS_PATH') ?: (defined('LAMINAS_PATH') ? LAMINAS_PATH : (is_dir($vendorPath . '/Laminas/library') ? $vendorPath . '/Laminas/library' : false));

        if (!$laminasPath) {
            throw new RuntimeException('Unable to load Laminas. Run `php composer.phar install` or define a LAMINAS_PATH environment variable.');
        }

        if (isset($loader)) {
            $loader->add('Laminas', $laminasPath . '/Laminas');
        } else {
            include $laminasPath . '/Laminas/Loader/AutoloaderFactory.php';
            AutoloaderFactory::factory(array(
                'Laminas\Loader\StandardAutoloader' => array(
                    'autoregister_laminas' => true,
                    'namespaces' => array(
                        'Laminas\ApiTools' => __DIR__ . '/../src/',
                        __NAMESPACE__ => __DIR__,
                        'Test' => __DIR__ . '/../vendor/Test/',
                    ),
                ),
            ));
        }
    }

    protected static function findParentPath($path)
    {
        $dir = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/' . $path)) {
            $dir = dirname($dir);
            if ($previousDir === $dir) return false;
            $previousDir = $dir;
        }
        return $dir . '/' . $path;
    }
}

Bootstrap::init();
