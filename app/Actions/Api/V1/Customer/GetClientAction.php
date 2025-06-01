<?php

namespace App\Actions\Api\V1\Customer;

use App\Domain\Customer\Models\Client;

class GetClientAction
{
    /**
     * Execute the action to get a specific client.
     */
    public function execute(string $clientId, array $with = []): ?Client
    {
        $defaultWith = [
            'category',
            'paymentTerms',
            'addresses',
            'phoneNumbers',
            'emails',
            'contacts'
        ];

        $relations = array_merge($defaultWith, $with);

        return Client::with($relations)->find($clientId);
    }

    /**
     * Get a client by SIREN.
     */
    public function getBySiren(string $siren, string $companyId): ?Client
    {
        return Client::where('siren', $siren)
            ->where('company_id', $companyId)
            ->with([
                'category',
                'paymentTerms',
                'addresses',
                'phoneNumbers',
                'emails',
                'contacts'
            ])
            ->first();
    }

    /**
     * Get a client by SIRET.
     */
    public function getBySiret(string $siret, string $companyId): ?Client
    {
        return Client::where('siret', $siret)
            ->where('company_id', $companyId)
            ->with([
                'category',
                'paymentTerms',
                'addresses',
                'phoneNumbers',
                'emails',
                'contacts'
            ])
            ->first();
    }

    /**
     * Get a client by VAT number.
     */
    public function getByVatNumber(string $vatNumber, string $companyId): ?Client
    {
        return Client::where('vat_number', $vatNumber)
            ->where('company_id', $companyId)
            ->with([
                'category',
                'paymentTerms',
                'addresses',
                'phoneNumbers',
                'emails',
                'contacts'
            ])
            ->first();
    }
}
