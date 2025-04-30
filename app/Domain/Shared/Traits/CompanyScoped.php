<?php

namespace App\Domain\Shared\Traits;

trait CompanyScoped
{
    public function scopeForCompany($query, $companyId = null)
    {
        $companyId = $companyId ?? auth()->user()->company_id;
        return $query->where('company_id', $companyId);
    }
    
    // Pour utiliser dans les actions ou repositories
    public static function bootCompanyScoped()
    {
        static::addGlobalScope('company', function ($query) {
            if (auth()->check()) {
                return $query->where('company_id', auth()->user()->company_id);
            }
        });
    }
}
