<?php
namespace Concrete5GraphqlWebsocket;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Config\Repository\Liaison;

class PackageHelpers
{
    protected static $fileConfig;
    protected static $pkgHandle;

    public static function getFileConfig($app)
    {
        if (!self::$fileConfig) {
            self::$fileConfig = new Liaison($app->make('config'), self::getPackageHandle());
        }

        return self::$fileConfig;
    }

    public static function setPackageHandle($pkgHandle) {
        self::$pkgHandle = $pkgHandle;
    }

    public static function getPackageHandle()
    {
        return isset(self::$pkgHandle) ? self::$pkgHandle : '';
    }
}