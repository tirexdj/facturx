<?php

namespace App\Actions\Api\V1\Customer;

use App\Domain\Customer\Models\Client;
use Illuminate\Support\Facades\DB;

class UpdateClientAction
{
    /**
     * Execute the action to update a client.
     */
    public function execute(Client $client, array $data): Client
    {
        return DB::transaction(function () use ($client, $data) {
            // Extract related data
            $addresses = $data['addresses'] ?? null;
            $phoneNumbers = $data['phone_numbers'] ?? null;
            $emails = $data['emails'] ?? null;
            $contacts = $data['contacts'] ?? null;
            
            // Remove related data from main data
            unset($data['addresses'], $data['phone_numbers'], $data['emails'], $data['contacts']);
            
            // Update the client
            $client->update($data);
            
            // Update addresses if provided
            if ($addresses !== null) {
                $this->updateAddresses($client, $addresses);
            }
            
            // Update phone numbers if provided
            if ($phoneNumbers !== null) {
                $this->updatePhoneNumbers($client, $phoneNumbers);
            }
            
            // Update emails if provided
            if ($emails !== null) {
                $this->updateEmails($client, $emails);
            }
            
            // Update contacts if provided
            if ($contacts !== null) {
                $this->updateContacts($client, $contacts);
            }
            
            return $client->load([
                'category',
                'paymentTerms',
                'addresses',
                'phoneNumbers',
                'emails',
                'contacts'
            ]);
        });
    }

    /**
     * Update addresses for the client.
     */
    private function updateAddresses(Client $client, array $addresses): void
    {
        $existingIds = [];
        
        foreach ($addresses as $addressData) {
            if (isset($addressData['id']) && $addressData['id']) {
                // Update existing address
                $address = $client->addresses()->find($addressData['id']);
                if ($address) {
                    $address->update($addressData);
                    $existingIds[] = $address->id;
                }
            } else {
                // Create new address
                $address = $client->addresses()->create($addressData);
                $existingIds[] = $address->id;
            }
        }
        
        // Delete addresses not in the update
        $client->addresses()->whereNotIn('id', $existingIds)->delete();
    }

    /**
     * Update phone numbers for the client.
     */
    private function updatePhoneNumbers(Client $client, array $phoneNumbers): void
    {
        $existingIds = [];
        
        foreach ($phoneNumbers as $phoneData) {
            if (isset($phoneData['id']) && $phoneData['id']) {
                // Update existing phone number
                $phone = $client->phoneNumbers()->find($phoneData['id']);
                if ($phone) {
                    $phone->update($phoneData);
                    $existingIds[] = $phone->id;
                }
            } else {
                // Create new phone number
                $phone = $client->phoneNumbers()->create($phoneData);
                $existingIds[] = $phone->id;
            }
        }
        
        // Delete phone numbers not in the update
        $client->phoneNumbers()->whereNotIn('id', $existingIds)->delete();
    }

    /**
     * Update emails for the client.
     */
    private function updateEmails(Client $client, array $emails): void
    {
        $existingIds = [];
        
        foreach ($emails as $emailData) {
            if (isset($emailData['id']) && $emailData['id']) {
                // Update existing email
                $email = $client->emails()->find($emailData['id']);
                if ($email) {
                    $email->update($emailData);
                    $existingIds[] = $email->id;
                }
            } else {
                // Create new email
                $email = $client->emails()->create($emailData);
                $existingIds[] = $email->id;
            }
        }
        
        // Delete emails not in the update
        $client->emails()->whereNotIn('id', $existingIds)->delete();
    }

    /**
     * Update contacts for the client.
     */
    private function updateContacts(Client $client, array $contacts): void
    {
        $existingIds = [];
        
        foreach ($contacts as $contactData) {
            if (isset($contactData['id']) && $contactData['id']) {
                // Update existing contact
                $contact = $client->contacts()->find($contactData['id']);
                if ($contact) {
                    $contact->update($contactData);
                    $existingIds[] = $contact->id;
                }
            } else {
                // Create new contact
                $contact = $client->contacts()->create($contactData);
                $existingIds[] = $contact->id;
            }
        }
        
        // Delete contacts not in the update
        $client->contacts()->whereNotIn('id', $existingIds)->delete();
    }
}
