<?php

namespace App\Actions\Api\V1\Customer;

use App\Domain\Customer\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateClientAction
{
    /**
     * Execute the action to create a new client.
     */
    public function execute(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            // Extract related data
            $addresses = $data['addresses'] ?? [];
            $phoneNumbers = $data['phone_numbers'] ?? [];
            $emails = $data['emails'] ?? [];
            $contacts = $data['contacts'] ?? [];
            
            // Remove related data from main data
            unset($data['addresses'], $data['phone_numbers'], $data['emails'], $data['contacts']);
            
            // Create the client
            $client = Client::create($data);
            
            // Create addresses
            if (!empty($addresses)) {
                $this->createAddresses($client, $addresses);
            }
            
            // Create phone numbers
            if (!empty($phoneNumbers)) {
                $this->createPhoneNumbers($client, $phoneNumbers);
            }
            
            // Create emails
            if (!empty($emails)) {
                $this->createEmails($client, $emails);
            }
            
            // Create contacts
            if (!empty($contacts)) {
                $this->createContacts($client, $contacts);
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
     * Create addresses for the client.
     */
    private function createAddresses(Client $client, array $addresses): void
    {
        foreach ($addresses as $addressData) {
            $client->addresses()->create($addressData);
        }
    }

    /**
     * Create phone numbers for the client.
     */
    private function createPhoneNumbers(Client $client, array $phoneNumbers): void
    {
        foreach ($phoneNumbers as $phoneData) {
            $client->phoneNumbers()->create($phoneData);
        }
    }

    /**
     * Create emails for the client.
     */
    private function createEmails(Client $client, array $emails): void
    {
        foreach ($emails as $emailData) {
            $client->emails()->create($emailData);
        }
    }

    /**
     * Create contacts for the client.
     */
    private function createContacts(Client $client, array $contacts): void
    {
        foreach ($contacts as $contactData) {
            $client->contacts()->create($contactData);
        }
    }
}
