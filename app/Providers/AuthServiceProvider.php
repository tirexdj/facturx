<?php

namespace App\Providers;

use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\Service;
use App\Domain\Product\Models\Category;
use App\Policies\CompanyPolicy;
use App\Policies\PlanPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ServicePolicy;
use App\Policies\CategoryPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Company::class => CompanyPolicy::class,
        Plan::class => PlanPolicy::class,
        Product::class => ProductPolicy::class,
        Service::class => ServicePolicy::class,
        Category::class => CategoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
