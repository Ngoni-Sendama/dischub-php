<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita8a531549a052454e3b95d7f95e0f479
{
    public static $prefixLengthsPsr4 = array (
        'N' => 
        array (
            'Ngoni\\DischubPhp\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Ngoni\\DischubPhp\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita8a531549a052454e3b95d7f95e0f479::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita8a531549a052454e3b95d7f95e0f479::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita8a531549a052454e3b95d7f95e0f479::$classMap;

        }, null, ClassLoader::class);
    }
}
