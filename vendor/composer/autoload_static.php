<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit2e0b78fcb5907fc34959989b7a762ad7
{
    public static $prefixLengthsPsr4 = array (
        'm' => 
        array (
            'mytharcher\\sdk\\alipay\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'mytharcher\\sdk\\alipay\\' => 
        array (
            0 => __DIR__ . '/..' . '/mytharcher/alipay-php-sdk',
        ),
    );

    public static $prefixesPsr0 = array (
        'D' => 
        array (
            'Detection' => 
            array (
                0 => __DIR__ . '/..' . '/mobiledetect/mobiledetectlib/namespaced',
            ),
        ),
    );

    public static $classMap = array (
        'Mobile_Detect' => __DIR__ . '/..' . '/mobiledetect/mobiledetectlib/Mobile_Detect.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit2e0b78fcb5907fc34959989b7a762ad7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit2e0b78fcb5907fc34959989b7a762ad7::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit2e0b78fcb5907fc34959989b7a762ad7::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit2e0b78fcb5907fc34959989b7a762ad7::$classMap;

        }, null, ClassLoader::class);
    }
}
