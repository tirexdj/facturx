<?php

namespace App\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Company\Models\Company;

class CompanyPolicy
{
    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny(User $user): bool
    {
        // Seuls les super admin peuvent voir toutes les companies
        // Pour l'instant, on retourne false car on n'a pas de système de rôles
        return false;
    }

    /**
     * Determine whether the user can view the company.
     */
    public function view(User $user, Company $company): bool
    {
        // L'utilisateur peut voir sa propre company
        return $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can create companies.
     */
    public function create(User $user): bool
    {
        // Seuls les super admin peuvent créer des companies
        // Pour l'instant, on retourne false car on n'a pas de système de rôles
        return false;
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user, Company $company): bool
    {
        // L'utilisateur peut modifier sa propre company
        return $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user, Company $company): bool
    {
        // Seuls les super admin peuvent supprimer des companies
        // Et on ne peut pas supprimer sa propre company
        return false;
    }

    /**
     * Determine whether the user can restore the company.
     */
    public function restore(User $user, Company $company): bool
    {
        // Seuls les super admin peuvent restaurer des companies
        return false;
    }

    /**
     * Determine whether the user can permanently delete the company.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        // Seuls les super admin peuvent supprimer définitivement des companies
        return false;
    }

    /**
     * Determine whether the user can view own company.
     */
    public function viewOwn(User $user): bool
    {
        // Tout utilisateur authentifié peut voir sa propre company
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can update own company.
     */
    public function updateOwn(User $user): bool
    {
        // Tout utilisateur authentifié peut modifier sa propre company
        return $user->company_id !== null;
    }
}
