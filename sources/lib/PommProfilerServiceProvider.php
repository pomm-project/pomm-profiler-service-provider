<?php
/*
 * This file is part of Pomm's Silex™ ProfilerServiceProvider package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\Silex\ProfilerServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

use Silex\Application;
use Silex\Api\BootableProviderInterface;
use Silex\Api\ControllerProviderInterface;

use PommProject\SymfonyBridge\DatabaseDataCollector;
use PommProject\SymfonyBridge\Configurator\DatabaseCollectorConfigurator;
use PommProject\SymfonyBridge\Controller\PommProfilerController;

use Symfony\Bridge\Twig\Extension\YamlExtension;

/**
 * PommProfilerServiceProvider
 *
 * Silex ServiceProvider for Pomm profiler.
 *
 * @package PommProfilerServiceProvider
 * @copyright 2014 Grégoire HUBERT
 * @author Jérôme MACIAS
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see ServiceProviderInterface
 * @see ControllerProviderInterface
 */
class PommProfilerServiceProvider implements ServiceProviderInterface, ControllerProviderInterface, BootableProviderInterface
{
    /**
     * register
     *
     * @see ServiceProviderInterface
     */
    public function register(Container $app)
    {
        $app['pomm.data_collector'] = function () use ($app) {
            return new DatabaseDataCollector(null, $app['stopwatch']);
        };

        $app['pomm.data_collector.configurator'] = function () use ($app) {
            return new DatabaseCollectorConfigurator($app['pomm.data_collector']);
        };

        $app->extend('data_collectors', function ($collectors, $app) {
            $collectors['pomm'] = function () use ($app) {
                return $app['pomm.data_collector'];
            };

            return $collectors;
        });

        $app->extend('data_collector.templates', function ($templates) {
            $templates[] = ['pomm', '@Pomm/Profiler/db.html.twig'];

            return $templates;
        });

        $app->extend('twig', function ($twig, $app) {
            if (!$twig->hasExtension('yaml')) {
                $twig->addExtension(new YamlExtension());
            }

            $twig->addFilter(new \Twig_SimpleFilter('sql_format', function($sql) { return \SqlFormatter::format($sql); }));

            return $twig;
        });

        $app->extend('twig.loader.filesystem', function ($loader, $app) {
            $loader->addPath($app['pomm.templates_path'], 'Pomm');

            return $loader;
        });

        $app['pomm.templates_path'] = function () {
            $r = new \ReflectionClass('PommProject\SymfonyBridge\DatabaseDataCollector');

            return dirname(dirname(dirname($r->getFileName()))).'/views';
        };

        $app['pomm_profiler.controller'] = function ($app) {
            return new PommProfilerController($app['url_generator'], $app['profiler'], $app['twig'], $app['pomm']);
        };

        $app['pomm.mount_prefix'] = '_pomm';
    }

    /**
     * boot
     *
     * @see ServiceProviderInterface
     */
    public function boot(Application $app)
    {
        $app->mount($app['pomm.mount_prefix'], $this->connect($app));
    }

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->get('/explain/{token}/{index_query}', 'pomm_profiler.controller:explainAction')
            ->bind('_pomm_profiler_explain');

        return $controllers;
    }
}
