<?php

namespace Eccube\Tests\ServiceProvider;

use Pimple\Container;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Eccube\Tests\Fixture\Generator as FixtureGenerator;

/**
 * FixtureServiceProvider
 *
 * @author Kentaro Ohkouchi
 */
class FixtureServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['eccube.fixture.generator'] = function ($app) {
            return new FixtureGenerator($app);
        };
    }

    public function boot(Application $app)
    {
        // quiet
    }
}