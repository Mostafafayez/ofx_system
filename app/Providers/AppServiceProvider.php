<?php

namespace App\Providers;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;
use App\Services\ContractServices;
use App\Services\CollectionService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ContractServices::class, function ($app) {
            return new ContractServices();
        });

        $this->app->singleton(CollectionService::class, function ($app) {
            return new CollectionService();
        });
    }

    /**
     * Bootstrap any application services.
     */

public function boot(): void
{
    // Enforce morph map globally
    Relation::enforceMorphMap([
        'user' => 'App\Models\User',
        'contract' => 'App\Models\Contract',
        'team' => 'App\Models\Team',
        'task' => 'App\Models\Task',
        'note' => 'App\Models\Note',
        'service' => 'App\Models\Service',
        'offer' => 'App\Models\Offer',
        'follow_up' => 'App\Models\FollowUp',
        'lead' => 'App\Models\Lead',
        'contract_service' => 'App\Models\ContractService',
    ]);

    Route::aliasMiddleware('role', RoleMiddleware::class);

    // Define a Gate for sales access
    Gate::define('access-sales', function ($user) {
        return $user->hasRole('sales')||$user->hasRole('owner');
    });

    Gate::define('teams', function ($user) {
        return $user->hasRole('owner')&&  $user->hasPermission('team_view')&& $user->hasRole('teamleader');
    });


    Gate::define('owner', function ($user) {
        return $user->hasRole('owner');
    });


    Gate::define('department-control', function ($user) {
        return $user->hasRole('owner') && $user->hasPermission('manage_department');
    });

    Gate::define('services_control', function ($user) {

        return $user->hasRole('owner') || $user->can('manage_services');
    });


    Gate::define('team_control', function ($user) {

        return $user->hasRole('owner') || $user->can('manage_teams');
    });

}



}
