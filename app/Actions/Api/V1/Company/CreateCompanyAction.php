<?php

namespace App\Actions\Api\V1\Company;

use App\Domain\Company\Models\Company;
use App\Domain\Shared\Models\Address;
use App\Domain\Shared\Models\Email;
use App\Domain\Shared\Models\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCompanyAction
{
    public function execute(array $data): Company
    {
        return DB::transaction(function () use ($data) {
            // Vérifier l'unicité du SIREN
            if (isset($data['siren']) && Company::where('siren', $data['siren'])->exists()) {
                throw ValidationException::withMessages([
                    'siren' => 'A company with this SIREN already exists.'
                ]);
            }

            // Vérifier l'unicité du SIRET
            if (isset($data['siret']) && Company::where('siret', $data['siret'])->exists()) {
                throw ValidationException::withMessages([
                    'siret' => 'A company with this SIRET already exists.'
                ]);
            }

            // Créer la company
            $company = Company::create([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'] ?? $data['name'],
                'trading_name' => $data['trading_name'] ?? null,
                'siren' => $data['siren'] ?? null,
                'siret' => $data['siret'] ?? null,
                'vat_number' => $data['vat_number'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'legal_form' => $data['legal_form'] ?? null,
                'website' => $data['website'] ?? null,
                'plan_id' => $data['plan_id'],
                'pdp_id' => $data['pdp_id'] ?? null,
                'vat_regime' => $data['vat_regime'] ?? 'normal',
                'fiscal_year_start' => $data['fiscal_year_start'] ?? now()->startOfYear(),
                'currency_code' => $data['currency_code'] ?? 'EUR',
                'language_code' => $data['language_code'] ?? 'fr',
                'is_active' => $data['is_active'] ?? true,
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Ajouter l'adresse principale si fournie
            if (isset($data['address'])) {
                $this->createAddress($company, $data['address']);
            }

            // Ajouter l'email principal si fourni
            if (isset($data['email'])) {
                $this->createEmail($company, $data['email']);
            }

            // Ajouter le téléphone principal si fourni
            if (isset($data['phone'])) {
                $this->createPhoneNumber($company, $data['phone']);
            }

            return $company;
        });
    }

    private function createAddress(Company $company, array $addressData): void
    {
        $company->addresses()->create([
            'type' => $addressData['type'] ?? 'main',
            'line_1' => $addressData['line_1'],
            'line_2' => $addressData['line_2'] ?? null,
            'city' => $addressData['city'],
            'postal_code' => $addressData['postal_code'],
            'state' => $addressData['state'] ?? null,
            'country_code' => $addressData['country_code'] ?? 'FR',
            'is_primary' => $addressData['is_primary'] ?? true,
        ]);

    }

    private function createEmail(Company $company, array $emailData): void
    {
        $company->emails()->create([
            'email' => $emailData['email'],
            'type' => $emailData['type'] ?? 'main',
            'is_primary' => $emailData['is_primary'] ?? true,
        ]);
    }

    private function createPhoneNumber(Company $company, array $phoneData): void
    {
        $company->phoneNumbers()->create([
            'number' => $phoneData['number'],
            'type' => $phoneData['type'] ?? 'main',
            'country_code' => $phoneData['country_code'] ?? 'FR',
            'is_primary' => $phoneData['is_primary'] ?? true,
        ]);
    }
}
