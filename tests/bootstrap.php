<?php

/**
 * PHPUnit bootstrap for site-tint plugin tests.
 *
 * Uses the project-root vendor autoloader so that craft\base\Model
 * and all Craft/Yii2 classes are available without a full Craft bootstrap.
 * Boots a minimal Yii2 console application so validators can run.
 */

// Project root is three levels up: tests/ -> plugin root -> plugins/ -> project root
$projectRoot = dirname(__DIR__, 3);
$projectRootAutoload = $projectRoot . '/vendor/autoload.php';

if (file_exists($projectRootAutoload)) {
    require $projectRootAutoload;
} else {
    require dirname(__DIR__) . '/vendor/autoload.php';
}

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
