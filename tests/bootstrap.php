<?php

/**
 * PHPUnit bootstrap for site-tint plugin tests.
 *
 * Uses a host Craft project's vendor autoloader so that craft\base\Model
 * and all Craft/Yii2 classes are available without a full Craft bootstrap.
 * Boots a minimal Yii2 console application so validators can run.
 *
 * The plugin is developed as a standalone git repo and consumed by host
 * projects as a Composer path repository, typically symlinked in from
 * outside the host project's directory tree (see e.g. mwe's
 * plugins/craft-site-tint). Because of that symlink, PHP resolves __DIR__
 * to this file's real location, which shares no filesystem ancestry with
 * the host project — a fixed dirname(__DIR__, N) depth cannot reach it.
 * Autoloader discovery below therefore searches candidates instead of
 * assuming a directory depth, so tests work both when run from a host
 * project's root and when the plugin has its own `composer install`.
 */

$candidateAutoloaders = [
    getcwd() . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
];

$projectRootAutoload = null;
foreach ($candidateAutoloaders as $candidate) {
    if (file_exists($candidate)) {
        $projectRootAutoload = $candidate;
        break;
    }
}

if ($projectRootAutoload === null) {
    fwrite(STDERR, "Could not locate a Composer autoloader. Run phpunit from a host Craft project's root, or run `composer install` inside the plugin directory.\n");
    exit(1);
}

$projectRoot = dirname($projectRootAutoload, 2);

require $projectRootAutoload;

// Yii.php registers the Yii global class alias and sets up Yii::$container.
// It must be required explicitly — the Composer autoloader does not do this.
require $projectRoot . '/vendor/yiisoft/yii2/Yii.php';

// Craft.php defines the global Craft class (extends Yii) in the root namespace.
// PSR-4 autoloading won't find it since it has no namespace prefix.
require $projectRoot . '/vendor/craftcms/cms/src/Craft.php';

// Register plugin namespace so tests can autoload transom\craftsitetint\ classes
\Yii::$classMap['transom\\craftsitetint\\models\\Settings'] = dirname(__DIR__) . '/src/models/Settings.php';
\Yii::$classMap['transom\\craftsitetint\\SiteTint'] = dirname(__DIR__) . '/src/SiteTint.php';

// Register plugin PSR-4 namespace via a Composer ClassLoader instance
$loader = new \Composer\Autoload\ClassLoader();
$loader->addPsr4('transom\\craftsitetint\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('transom\\craftsitetint\\tests\\', __DIR__ . '/');
$loader->register(true);

// Boot a minimal Yii2 console application.
// This satisfies Yii::createObject() inside validators without needing
// a database connection or full Craft bootstrap.
new \yii\console\Application([
    'id' => 'site-tint-tests',
    'basePath' => dirname(__DIR__),
    'components' => [
        'i18n' => [
            'class' => \yii\i18n\I18N::class,
            'translations' => [
                '*' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'forceTranslation' => false,
                ],
            ],
        ],
    ],
]);
