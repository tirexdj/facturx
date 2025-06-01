<?php

namespace App\Actions\Api\V1\Customer;

use App\Domain\Customer\Models\Client;
use Illuminate\Support\Facades\DB;
use Exception;

class DeleteClientAction
{
    /**
     * Execute the action to delete a client.
     */
    public function execute(Client $client): bool
    {
        return DB::transaction(function () use ($client) {
            // Check if client has associated invoices or quotes
            if ($client->invoices()->exists() || $client->quotes()->exists()) {
                throw new Exception(
                    'Impossible de supprimer ce client car il a des factures ou devis associés. Utilisez l\'archivage à la place.',
                    422
                );
            }
            
            // Soft delete related data
            $client->addresses()->delete();
            $client->phoneNumbers()->delete();
            $client->emails()->delete();
            $client->contacts()->delete();
            
            // Soft delete the client
            return $client->delete();
        });
    }

    /**
     * Force delete a client and all related data.
     */
    public function forceDelete(Client $client): bool
    {
        return DB::transaction(function () use ($client) {
            // Force delete related data
            $client->addresses()->forceDelete();
            $client->phoneNumbers()->forceDelete();
            $client->emails()->forceDelete();
            $client->contacts()->forceDelete();
            
            // Force delete the client
            return $client->forceDelete();
        });
    }

    /**
     * Restore a soft-deleted client.
     */
    public function restore(Client $client): bool
    {
        return DB::transaction(function () use ($client) {
            // Restore the client
            $client->restore();
            
            // Restore related data
            $client->addresses()->withTrashed()->restore();
            $client->phoneNumbers()->withTrashed()->restore();
            $client->emails()->withTrashed()->restore();
            $client->contacts()->withTrashed()->restore();
            
            return true;
        });
    }
}
