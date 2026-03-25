<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\Task;
use App\Observers\ContractObserver;
use App\Observers\TaskObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Model::shouldBeStrict();
        Model::preventLazyLoading(! app()->isLocal());
        Model::automaticallyEagerLoadRelationships();

        Task::observe(TaskObserver::class);
        Contract::observe(ContractObserver::class);
    }
}
