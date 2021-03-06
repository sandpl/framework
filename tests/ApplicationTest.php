<?php
declare(strict_types=1);
/**
 * This file is part of Spark Framework.
 *
 * @link     https://github.com/spark-php/framework
 * @document https://github.com/spark-php/framework
 * @contact  itwujunze@gmail.com
 * @license  https://github.com/spark-php/framework
 */

namespace Spark\Framework\Tests;

use Spark\Framework\Application;
use Spark\Framework\Common\Environment;
use Spark\Framework\Exceptions\DispatcherException;
use Spark\Framework\Helper\DotArray;
use Spark\Framework\Interfaces\ApplicationInterface;
use Spark\Framework\Interfaces\Di\ContainerInterface;
use Spark\Framework\Interfaces\Router\RouterInterface;
use Spark\Framework\Router\Route;
use Spark\Framework\Tests\fixtrues\AnotherTestController;
use Spark\Framework\Tests\fixtrues\TestController;

class ApplicationTest extends TestCase
{
    public function initializeProvider()
    {
        return [
            [
                function (ContainerInterface $container) {
                    $container->enableAutowiredForNamespace('Spark\\Framework\\Tests\\fixtrues');
                },
                function (RouterInterface $router) {
                    $router->addRoute(
                        (new Route)
                            ->put('/{a}[/{b}[/{c}]]')
                            ->setTarget(TestController::class)
                    );
                }
            ]
        ];
    }

    public function initializeExceptionProvider()
    {
        return [
          [
            function (ContainerInterface $container) {
                $container->enableAutowiredForNamespace('Spark\\Framework\\Tests\\fixtrues');
            },
            function (RouterInterface $router) {
                $router->addRoute(
                  (new Route)
                    ->put('/{a}[/{b}[/{c}]]')
                    ->setTarget([TestController::class, 'index'])
                );
            }
          ]
        ];
    }

    public function AnotherInitializeProvider()
    {
        return [
          [
            function (ContainerInterface $container) {
                $container->enableAutowiredForNamespace('Spark\\Framework\\Tests\\fixtrues');
            },
            function (RouterInterface $router) {
                $router->addRoute(
                  (new Route)
                    ->put('/{a}[/{b}[/{c}]]')
                    ->setTarget([AnotherTestController::class, 'hello'])
                );
            }
          ]
        ];
    }

    /**
     * @dataProvider initializeProvider
     * @param callable $containerInit
     * @param callable $routerInit
     * @throws \ReflectionException
     * @throws \Spark\Framework\Exceptions\ContainerException
     * @throws \Spark\Framework\exceptions\RouterException
     */
    public function testApplication(callable $containerInit, callable $routerInit)
    {
        $application = new Application($containerInit);
        $this->assertInstanceOf(Application::class, $application);
        $this->expectOutputString('["Hello Panda"]');
        /** @var Environment $environment */
        $environment = $application->getContainer()->getByAlias('environment');
        $environment->replace([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/foo/bar/test',
            'QUERY_STRING' => 'abc=123&foo=bar',
            'SERVER_NAME' => 'example.com',
            'CONTENT_TYPE' => 'application/json;charset=utf8',
            'CONTENT_LENGTH' => 15
        ]);
        $application->loadRouterConfig($routerInit);
        $application->run();
    }

    /**
     * @dataProvider AnotherInitializeProvider
     * @param callable $containerInit
     * @param callable $routerInit
     * @throws \ReflectionException
     * @throws \Spark\Framework\Exceptions\ContainerException
     * @throws \Spark\Framework\exceptions\RouterException
     */
    public function testAnotherApplication(callable $containerInit, callable $routerInit)
    {
        $application = new Application($containerInit);
        $this->assertInstanceOf(Application::class, $application);
        $this->expectOutputString('["Hello Spark"]');
        /** @var Environment $environment */
        $environment = $application->getContainer()->getByAlias('environment');
        $environment->replace([
          'REQUEST_METHOD' => 'PUT',
          'REQUEST_URI' => '/foo/bar/test',
          'QUERY_STRING' => 'abc=123&foo=bar',
          'SERVER_NAME' => 'example.com',
          'CONTENT_TYPE' => 'application/json;charset=utf8',
          'CONTENT_LENGTH' => 15
        ]);
        $application->loadRouterConfig($routerInit);
        $application->run();
    }

    /**
     *
     * @dataProvider initializeExceptionProvider
     * @param callable $containerInit
     * @param callable $routerInit
     * @throws \ReflectionException
     * @throws \Spark\Framework\Exceptions\ContainerException
     */
    public function testAnotherApplicationException(callable $containerInit, callable $routerInit)
    {
        $application = new Application($containerInit);
        $this->assertInstanceOf(Application::class, $application);
        $this->expectException(DispatcherException::class);
        /** @var Environment $environment */
        $environment = $application->getContainer()->getByAlias('environment');
        $environment->replace([
          'REQUEST_METHOD' => 'PUT',
          'REQUEST_URI' => '/foo/bar/test',
          'QUERY_STRING' => 'abc=123&foo=bar',
          'SERVER_NAME' => 'example.com',
          'CONTENT_TYPE' => 'application/json;charset=utf8',
          'CONTENT_LENGTH' => 15
        ]);
        $application->loadRouterConfig($routerInit);
        $application->run();
    }

    public function initializeConsoleAppProvider()
    {
        return [
            [
                function (ContainerInterface $container) {
                    $container->enableAutowiredForNamespace('Spark\\Framework\\Tests\\fixtrues');
                }
            ]
        ];
    }

    /**
     * @dataProvider initializeConsoleAppProvider
     *
     * @param callable $containerInit
     * @throws \ReflectionException
     * @throws \Spark\Framework\Exceptions\ContainerException
     *
     */
    public function testConsoleApplication(callable $containerInit)
    {
        $application = new Application($containerInit);
        $this->assertInstanceOf(ApplicationInterface::class, $application);
        /** @var \Symfony\Component\Console\Application $console */
        $console = $application->get(\Symfony\Component\Console\Application::class);

        $this->assertInstanceOf(\Symfony\Component\Console\Application::class, $console);
    }

    /**
     * @dataProvider initializeConsoleAppProvider
     *
     * @param callable $containerInit
     * @throws \ReflectionException
     * @throws \Spark\Framework\Exceptions\ContainerException
     */
    public function testLoadConfig(callable $containerInit)
    {
        $application = new Application($containerInit);
        $this->assertInstanceOf(ApplicationInterface::class, $application);

        $application->loadConfig(__DIR__.'/fixtrues/config');

        $settings = $application->getSettings();

        $this->assertInstanceOf(DotArray::class, $settings);

        $this->assertEquals($settings['app.foo.bar'], 'sparkPHP');
    }
}
