<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit96f62d9bfae69c3bde600d809cced7bb
{
    public static $files = array (
        '5e5da40970ff0ff2b1981ca48446c94b' => __DIR__ . '/../..' . '/src/Support/helpers.php',
    );

    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'App\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInit96f62d9bfae69c3bde600d809cced7bb::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit96f62d9bfae69c3bde600d809cced7bb::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit96f62d9bfae69c3bde600d809cced7bb::$classMap;

        }, null, ClassLoader::class);
    }
}
