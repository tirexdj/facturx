<?php

namespace App\Actions\Api\V1\Company;

use App\Domain\Company\Models\Company;

class GetCompanyAction
{
    public function execute(string $id): Company
    {
        return Company::with([
            'plan',
            'addresses',
            'phoneNumbers',
            'emails',
            'users' => function ($query) {
                $query->select(['id', 'name', 'email', 'company_id', 'created_at']);
            }
        ])->findOrFail($id);
    }
}
