<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Company\Models\Plan;

class PlanPolicy
{
    /**
     * Determine whether the user can view any plans.
     */
    public function viewAny(User $user): bool
    {
        // Tout utilisateur authentifié peut voir la liste des plans
        return true;
    }

    /**
     * Determine whether the user can view the plan.
     */
    public function view(User $user, Plan $plan): bool
    {
        // L'utilisateur peut voir les plans publics et actifs
        // Ou son propre plan même s'il n'est pas public
        return ($plan->is_public && $plan->is_active) || 
               ($user->company && $user->company->plan_id === $plan->id);
    }

    /**
     * Determine whether the user can create plans.
     */
    public function create(User $user): bool
    {
        // Seuls les super admin peuvent créer des plans
        return false;
    }

    /**
     * Determine whether the user can update the plan.
     */
    public function update(User $user, Plan $plan): bool
    {
        // Seuls les super admin peuvent modifier des plans
        return false;
    }

    /**
     * Determine whether the user can delete the plan.
     */
    public function delete(User $user, Plan $plan): bool
    {
        // Seuls les super admin peuvent supprimer des plans
        return false;
    }

    /**
     * Determine whether the user can restore the plan.
     */
    public function restore(User $user, Plan $plan): bool
    {
        // Seuls les super admin peuvent restaurer des plans
        return false;
    }

    /**
     * Determine whether the user can permanently delete the plan.
     */
    public function forceDelete(User $user, Plan $plan): bool
    {
        // Seuls les super admin peuvent supprimer définitivement des plans
        return false;
    }
}
