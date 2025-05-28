<?php

namespace App\Actions\Api\V1\Company;

use App\Domain\Company\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCompanyAction
{
    public function execute(string $id, array $data): Company
    {
        return DB::transaction(function () use ($id, $data) {
            $company = Company::findOrFail($id);

            // Vérifier l'unicité du SIREN si modifié
            if (isset($data['siren']) && $data['siren'] !== $company->siren) {
                if (Company::where('siren', $data['siren'])->where('id', '!=', $id)->exists()) {
                    throw ValidationException::withMessages([
                        'siren' => 'A company with this SIREN already exists.'
                    ]);
                }
            }

            // Vérifier l'unicité du SIRET si modifié
            if (isset($data['siret']) && $data['siret'] !== $company->siret) {
                if (Company::where('siret', $data['siret'])->where('id', '!=', $id)->exists()) {
                    throw ValidationException::withMessages([
                        'siret' => 'A company with this SIRET already exists.'
                    ]);
                }
            }

            // Mettre à jour les données de base
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'legal_name' => $data['legal_name'] ?? null,
                'trading_name' => $data['trading_name'] ?? null,
                'siren' => $data['siren'] ?? null,
                'siret' => $data['siret'] ?? null,
                'vat_number' => $data['vat_number'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'legal_form' => $data['legal_form'] ?? null,
                'website' => $data['website'] ?? null,
                'plan_id' => $data['plan_id'] ?? null,
                'pdp_id' => $data['pdp_id'] ?? null,
                'vat_regime' => $data['vat_regime'] ?? null,
                'fiscal_year_start' => $data['fiscal_year_start'] ?? null,
                'currency_code' => $data['currency_code'] ?? null,
                'language_code' => $data['language_code'] ?? null,
                'is_active' => $data['is_active'] ?? null,
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'updated_by' => auth()->id(),
            ], function ($value) {
                return $value !== null;
            });

            $company->update($updateData);

            // Mettre à jour l'adresse principale si fournie
            if (isset($data['address'])) {
                $this->updateAddress($company, $data['address']);
            }

            // Mettre à jour l'email principal si fourni
            if (isset($data['email'])) {
                $this->updateEmail($company, $data['email']);
            }

            // Mettre à jour le téléphone principal si fourni
            if (isset($data['phone'])) {
                $this->updatePhoneNumber($company, $data['phone']);
            }

            return $company->fresh();
        });
    }

    private function updateAddress(Company $company, array $addressData): void
    {
        $primaryAddress = $company->addresses()->where('is_primary', true)->first();

        if ($primaryAddress) {
            $primaryAddress->update([
                'type' => $addressData['type'] ?? $primaryAddress->type,
                'line_1' => $addressData['line_1'] ?? $primaryAddress->line_1,
                'line_2' => $addressData['line_2'] ?? $primaryAddress->line_2,
                'city' => $addressData['city'] ?? $primaryAddress->city,
                'postal_code' => $addressData['postal_code'] ?? $primaryAddress->postal_code,
                'state' => $addressData['state'] ?? $primaryAddress->state,
                'country_code' => $addressData['country_code'] ?? $primaryAddress->country_code,
            ]);
        } else {
            $company->addresses()->create([
                'type' => $addressData['type'] ?? 'main',
                'line_1' => $addressData['line_1'],
                'line_2' => $addressData['line_2'] ?? null,
                'city' => $addressData['city'],
                'postal_code' => $addressData['postal_code'],
                'state' => $addressData['state'] ?? null,
                'country_code' => $addressData['country_code'] ?? 'FR',
                'is_primary' => true,
            ]);
        }
    }

    private function updateEmail(Company $company, array $emailData): void
    {
        $primaryEmail = $company->emails()->where('is_primary', true)->first();

        if ($primaryEmail) {
            $primaryEmail->update([
                'email' => $emailData['email'] ?? $primaryEmail->email,
                'type' => $emailData['type'] ?? $primaryEmail->type,
            ]);
        } else {
            $company->emails()->create([
                'email' => $emailData['email'],
                'type' => $emailData['type'] ?? 'main',
                'is_primary' => true,
            ]);
        }
    }

    private function updatePhoneNumber(Company $company, array $phoneData): void
    {
        $primaryPhone = $company->phoneNumbers()->where('is_primary', true)->first();

        if ($primaryPhone) {
            $primaryPhone->update([
                'number' => $phoneData['number'] ?? $primaryPhone->number,
                'type' => $phoneData['type'] ?? $primaryPhone->type,
                'country_code' => $phoneData['country_code'] ?? $primaryPhone->country_code,
            ]);
        } else {
            $company->phoneNumbers()->create([
                'number' => $phoneData['number'],
                'type' => $phoneData['type'] ?? 'main',
                'country_code' => $phoneData['country_code'] ?? 'FR',
                'is_primary' => true,
            ]);
        }
    }
}
