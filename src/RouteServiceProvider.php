<?php

namespace Phuongtt\Api\System;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function boot()
    {
        $this->loadConfig();

        parent::boot();
    }

    /**
     * Register
     */
    public function register()
    {
        $this->registerAssets();
    }

    /**
     * Register assets
     */
    private function registerAssets()
    {
        $this->publishes([
            __DIR__ . '/config/optimus.components.php' => config_path('optimus.components.php'),
        ]);
    }

    /**
     * Load configuration
     */
    private function loadConfig()
    {
        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app['config'];

        if ($config->get('optimus.components') === null) {
            $config->set('optimus.components', require __DIR__ . '/config/optimus.components.php');
        }
    }

    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function map(Router $router)
    {
        $config = $this->app['config']['optimus.components'];

        $middleware = $config['protection_middleware'];
        $basicMiddleware = $config['protection_basic_middleware'];
        $integrateMiddleware = @$config['protection_integrate_middleware'];

        $highLevelParts = array_map(function ($namespace) {
            return glob(sprintf('%s%s*', $namespace, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
        }, $config['namespaces']);

        foreach ($highLevelParts as $part => $partComponents) {
            foreach ($partComponents as $componentRoot) {
                $component = substr($componentRoot, strrpos($componentRoot, DIRECTORY_SEPARATOR) + 1);

                $namespace = sprintf(
                    '%s\\%s\\Controllers',
                    $part,
                    $component
                );

                $fileNames = [
                    'routes' => 2,
                    'routes_protected' => 2,
                    'routes_public' => 0,
                    'routes_basic' => 1,
                    'routes_integrate' => 3,
                ];

                foreach ($fileNames as $fileName => $protected) {
                    $path = sprintf('%s/%s.php', $componentRoot, $fileName);

                    if (!file_exists($path)) {
                        continue;
                    }

                    if($protected === 2){
                        $mdware = $middleware;
                    }elseif($protected === 1){
                        $mdware = $basicMiddleware;
                    }elseif($protected === 3){
                        $mdware = $integrateMiddleware;
                    }else{
                        $mdware = [];
                    }

                    $router->group([
                        'middleware' => $mdware,
                        'namespace'  => $namespace,
                        'prefix'     => $config['prefix'],
                    ], function ($router) use ($path) {
                        require $path;
                    });
                }
            }
        }
    }
}
