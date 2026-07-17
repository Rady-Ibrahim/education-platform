<?php

namespace App\Modules\Shared\Providers;

use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    abstract protected function moduleName(): string;

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [];
    }

    public function register(): void
    {
        foreach ($this->bindings() as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    public function boot(): void
    {
        $this->loadModuleRoutes();
        $this->loadModuleViews();
    }

    protected function loadModuleRoutes(): void
    {
        $web = $this->modulePath('Routes/web.php');
        $api = $this->modulePath('Routes/api.php');

        if (file_exists($web)) {
            $this->loadRoutesFrom($web);
        }

        if (file_exists($api)) {
            $this->loadRoutesFrom($api);
        }
    }

    protected function loadModuleViews(): void
    {
        $views = $this->modulePath('Resources/views');

        if (is_dir($views)) {
            $this->loadViewsFrom($views, $this->moduleName());
        }
    }

    protected function modulePath(string $path = ''): string
    {
        $base = app_path('Modules/'.$this->moduleName());

        return $path === '' ? $base : $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
