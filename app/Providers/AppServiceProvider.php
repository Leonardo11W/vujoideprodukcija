<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local')) {
            // Only register Telescope if it's available
            if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
                $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
                $this->app->register(TelescopeServiceProvider::class);
            }
            
            // Only register IDE Helper if it's available
            if (class_exists(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class)) {
                $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
            }
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        Paginator::useBootstrapFive();

        try {
            Carbon::setLocale($this->app['config']->get('app.locale', 'hr'));
        } catch (\Throwable $e) {
        } 

        Blade::directive('hasPermission', function ($permissions) {
            return "<?php 
                if(Auth::check()) {
                    \$user = Auth::user();
                    \$permission = {$permissions};
                    \$hasPermission = \$user->can(\$permission);
                    
                    // Log permission check for debugging
                    \Log::info('hasPermission directive check', [
                        'user_id' => \$user->id,
                        'user_email' => \$user->email,
                        'permission' => \$permission,
                        'has_permission' => \$hasPermission,
                        'user_roles' => \$user->roles->pluck('name')->toArray(),
                        'all_permissions' => \$user->getAllPermissions()->pluck('name')->toArray(),
                        'hasPermissionTo_direct' => \$user->hasPermissionTo(\$permission)
                    ]);
                } else {
                    \$hasPermission = false;
                }
                if(\$hasPermission):
            ?>";
        });

        Blade::directive('endhasPermission', function () {
            return '<?php endif; ?>';
        });

        $this->app->singleton('translation.loader', function ($app) {
            return new CustomTranslationLoader($app['files'], $app['path.lang']);
        });

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            $locale = $app['config']['app.locale'];

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });

    }
}
