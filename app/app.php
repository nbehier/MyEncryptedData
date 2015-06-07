<?php

// This is the default config. See `deploy_config/README.md' for more info.
$config = array(
    'debug' => true,
    'timer.start' => $startTime,
    'monolog.name' => 'silex-bootstrap',
    'monolog.level' => \Monolog\Logger::DEBUG,
    'monolog.logfile' => __DIR__ . '/log/app.log',
    'twig.path' => __DIR__ . '/../src/App/views',
    'twig.options' => array(
        'cache' => __DIR__ . '/cache/twig',
    )
);

// Apply custom config if available
if (file_exists(__DIR__ . '/config.php')) {
    include __DIR__ . '/config.php';
}

// Initialize Application
$app = new App\Silex\Application($config);
$app->register(new Silex\Provider\TranslationServiceProvider());
$app['translator'] = $app->share($app->extend('translator', function ($translator, $app) {
    $translator->addLoader('yaml', new Symfony\Component\Translation\Loader\YamlFileLoader());
    $translator->addResource('yaml', __DIR__.'/resources/locales/fr.yml', 'fr');
    return $translator;
}));

/*
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => __DIR__.'/cache/profiler',
    'profiler.mount_prefix' => '/_profiler', // this is the default
));
*/
/**
 * Register controllers as services
 * @link http://silex.sensiolabs.org/doc/providers/service_controller.html
 **/
$app['app.default_controller'] = $app->share(
    function () use ($app) {
        return new \App\Controller\DefaultController($app['twig'], $app['logger']);
    }
);

// Map routes to controllers
include __DIR__ . '/routing.php';

return $app;
