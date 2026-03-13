<?php

namespace Modules\Frontend\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Finder\Finder;

class FrontendServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $moduleName = 'Frontend';

    /**
     * @var string
     */
    protected $moduleNameLower = 'frontend';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(base_path('Modules/Frontend/database/migrations'));
        $this->loadHelpers();

        // adding global middleware
        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');

        // register commands
        $this->registerCommands('\Modules\Frontend\Console\Commands');
    }
    
    /**
     * Load helper files
     *
     * @return void
     */
    protected function loadHelpers()
    {
        $helpers = [
            'frontend_helper.php',
        ];
        
        foreach ($helpers as $helper) {
            $file = __DIR__ . '/../Helpers/' . $helper;
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            base_path('Modules/Frontend/Config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            base_path('Modules/Frontend/Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);

        $sourcePath = base_path('Modules/Frontend/Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'frontend');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (Config::get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }

    /**
     * Register commands.
     *
     * @param  string  $namespace
     */
    protected function registerCommands($namespace = '')
    {
        $finder = new Finder(); // from Symfony\Component\Finder;
        $finder->files()->name('*.php')->in(__DIR__.'/../Console');

        $classes = [];
        foreach ($finder as $file) {
            $class = $namespace.'\\'.$file->getBasename('.php');
            array_push($classes, $class);
        }

        $this->commands($classes);
    }
}
