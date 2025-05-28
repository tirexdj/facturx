<?php

namespace App\Actions\Api\V1\Company;

use App\Domain\Company\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteCompanyAction
{
    public function execute(string $id): void
    {
        DB::transaction(function () use ($id) {
            $company = Company::with(['users', 'clients', 'quotes', 'invoices'])->findOrFail($id);

            // Vérifier qu'on peut supprimer l'entreprise
            if ($company->users()->count() > 0) {
                throw ValidationException::withMessages([
                    'company' => 'Cannot delete company with active users. Please remove all users first.'
                ]);
            }

            if ($company->clients()->count() > 0) {
                throw ValidationException::withMessages([
                    'company' => 'Cannot delete company with existing clients. Please remove all clients first.'
                ]);
            }

            if ($company->quotes()->count() > 0) {
                throw ValidationException::withMessages([
                    'company' => 'Cannot delete company with existing quotes. Please remove all quotes first.'
                ]);
            }

            if ($company->invoices()->count() > 0) {
                throw ValidationException::withMessages([
                    'company' => 'Cannot delete company with existing invoices. Please remove all invoices first.'
                ]);
            }

            // Supprimer les données liées
            $company->addresses()->delete();
            $company->phoneNumbers()->delete();
            $company->emails()->delete();

            // Supprimer la company (soft delete)
            $company->delete();
        });
    }
}
