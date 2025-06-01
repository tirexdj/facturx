<?php

namespace App\Actions\Api\V1\Customer;

use App\Domain\Customer\Models\Client;
use App\Domain\Customer\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class ImportClientsAction
{
    /**
     * Execute the action to import clients from a file.
     */
    public function execute(UploadedFile $file, array $mapping, array $options): array
    {
        $data = $this->parseFile($file, $options['skip_first_row'] ?? true);
        
        if (empty($data)) {
            throw new Exception('Le fichier ne contient aucune donnée valide.');
        }

        $results = [
            'total' => count($data),
            'imported' => 0,
            'updated' => 0,
            'errors' => [],
            'duplicates' => []
        ];

        return DB::transaction(function () use ($data, $mapping, $options, &$results) {
            foreach ($data as $index => $row) {
                try {
                    $clientData = $this->mapRowToClientData($row, $mapping, $options);
                    
                    if (empty($clientData['name'])) {
                        $results['errors'][] = [
                            'row' => $index + 1,
                            'error' => 'Le nom du client est obligatoire'
                        ];
                        continue;
                    }

                    $existingClient = $this->findExistingClient($clientData, $options['company_id']);
                    
                    if ($existingClient && !$options['update_existing']) {
                        $results['duplicates'][] = [
                            'row' => $index + 1,
                            'client' => $clientData['name'],
                            'reason' => 'Client existant (SIREN/SIRET/Email)'
                        ];
                        continue;
                    }

                    if ($existingClient && $options['update_existing']) {
                        $this->updateExistingClient($existingClient, $clientData);
                        $results['updated']++;
                    } else {
                        $this->createNewClient($clientData);
                        $results['imported']++;
                    }

                } catch (Exception $e) {
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return $results;
        });
    }

    /**
     * Parse the uploaded file and extract data.
     */
    private function parseFile(UploadedFile $file, bool $skipFirstRow = true): array
    {
        $extension = $file->getClientOriginalExtension();
        $data = [];

        try {
            if (in_array($extension, ['csv', 'txt'])) {
                $data = $this->parseCsvFile($file, $skipFirstRow);
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                $data = $this->parseExcelFile($file, $skipFirstRow);
            }
        } catch (Exception $e) {
            throw new Exception('Erreur lors de la lecture du fichier: ' . $e->getMessage());
        }

        return $data;
    }

    /**
     * Parse CSV file.
     */
    private function parseCsvFile(UploadedFile $file, bool $skipFirstRow): array
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        
        if ($handle === false) {
            throw new Exception('Impossible de lire le fichier CSV');
        }

        $rowIndex = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($skipFirstRow && $rowIndex === 0) {
                $rowIndex++;
                continue;
            }
            
            $data[] = $row;
            $rowIndex++;
        }

        fclose($handle);
        return $data;
    }

    /**
     * Parse Excel file.
     */
    private function parseExcelFile(UploadedFile $file, bool $skipFirstRow): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $data = [];

        $startRow = $skipFirstRow ? 2 : 1;
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cellValue = $worksheet->getCell($col . $row)->getValue();
                $rowData[] = $cellValue;
            }
            
            // Skip empty rows
            if (array_filter($rowData)) {
                $data[] = $rowData;
            }
        }

        return $data;
    }

    /**
     * Map a row of data to client data structure.
     */
    private function mapRowToClientData(array $row, array $mapping, array $options): array
    {
        $clientData = [
            'company_id' => $options['company_id'],
            'client_type' => $options['default_client_type'],
            'currency_code' => $options['default_currency_code'],
            'language_code' => $options['default_language_code'],
            'category_id' => $options['category_id'] ?? null,
        ];

        // Map basic client fields
        foreach ($mapping as $field => $columnIndex) {
            if ($columnIndex !== null && isset($row[$columnIndex]) && !empty($row[$columnIndex])) {
                $value = trim($row[$columnIndex]);
                
                switch ($field) {
                    case 'client_type':
                        if (in_array($value, ['company', 'individual'])) {
                            $clientData['client_type'] = $value;
                        }
                        break;
                        
                    case 'siren':
                        $clientData['siren'] = preg_replace('/[^0-9]/', '', $value);
                        break;
                        
                    case 'siret':
                        $clientData['siret'] = preg_replace('/[^0-9]/', '', $value);
                        break;
                        
                    case 'credit_limit':
                        $clientData['credit_limit'] = is_numeric($value) ? (float) $value : null;
                        break;
                        
                    case 'tags':
                        $clientData['tags'] = array_map('trim', explode(',', $value));
                        break;
                        
                    default:
                        if (Str::startsWith($field, 'address_') || 
                            Str::startsWith($field, 'phone_') || 
                            Str::startsWith($field, 'contact_') ||
                            $field === 'email') {
                            // Handle related data separately
                            continue 2;
                        }
                        $clientData[$field] = $value;
                        break;
                }
            }
        }

        // Handle address data
        $addressData = $this->extractAddressData($row, $mapping);
        if (!empty($addressData)) {
            $clientData['addresses'] = [$addressData];
        }

        // Handle phone data
        $phoneData = $this->extractPhoneData($row, $mapping);
        if (!empty($phoneData)) {
            $clientData['phone_numbers'] = [$phoneData];
        }

        // Handle email data
        $emailData = $this->extractEmailData($row, $mapping);
        if (!empty($emailData)) {
            $clientData['emails'] = [$emailData];
        }

        // Handle contact data
        $contactData = $this->extractContactData($row, $mapping);
        if (!empty($contactData)) {
            $clientData['contacts'] = [$contactData];
        }

        return $clientData;
    }

    /**
     * Extract address data from row.
     */
    private function extractAddressData(array $row, array $mapping): array
    {
        $addressData = [];
        $addressFields = [
            'address_line_1' => 'line_1',
            'address_line_2' => 'line_2', 
            'address_line_3' => 'line_3',
            'address_postal_code' => 'postal_code',
            'address_city' => 'city',
            'address_state_province' => 'state_province',
            'address_country_code' => 'country_code'
        ];

        foreach ($addressFields as $mapKey => $addressKey) {
            if (isset($mapping[$mapKey]) && isset($row[$mapping[$mapKey]])) {
                $value = trim($row[$mapping[$mapKey]]);
                if (!empty($value)) {
                    $addressData[$addressKey] = $value;
                }
            }
        }

        if (!empty($addressData)) {
            $addressData['is_default'] = true;
            $addressData['is_billing'] = true;
            $addressData['is_shipping'] = true;
            $addressData['country_code'] = $addressData['country_code'] ?? 'FR';
        }

        return $addressData;
    }

    /**
     * Extract phone data from row.
     */
    private function extractPhoneData(array $row, array $mapping): array
    {
        $phoneData = [];
        
        if (isset($mapping['phone_number']) && isset($row[$mapping['phone_number']])) {
            $phone = trim($row[$mapping['phone_number']]);
            if (!empty($phone)) {
                $phoneData['number'] = $phone;
                $phoneData['country_code'] = '+33';
                $phoneData['is_default'] = true;
                
                if (isset($mapping['phone_country_code']) && isset($row[$mapping['phone_country_code']])) {
                    $countryCode = trim($row[$mapping['phone_country_code']]);
                    if (!empty($countryCode)) {
                        $phoneData['country_code'] = $countryCode;
                    }
                }
            }
        }

        return $phoneData;
    }

    /**
     * Extract email data from row.
     */
    private function extractEmailData(array $row, array $mapping): array
    {
        $emailData = [];
        
        if (isset($mapping['email']) && isset($row[$mapping['email']])) {
            $email = trim($row[$mapping['email']]);
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailData['email'] = $email;
                $emailData['is_default'] = true;
            }
        }

        return $emailData;
    }

    /**
     * Extract contact data from row.
     */
    private function extractContactData(array $row, array $mapping): array
    {
        $contactData = [];
        $contactFields = [
            'contact_first_name' => 'first_name',
            'contact_last_name' => 'last_name',
            'contact_job_title' => 'job_title',
            'contact_department' => 'department'
        ];

        foreach ($contactFields as $mapKey => $contactKey) {
            if (isset($mapping[$mapKey]) && isset($row[$mapping[$mapKey]])) {
                $value = trim($row[$mapping[$mapKey]]);
                if (!empty($value)) {
                    $contactData[$contactKey] = $value;
                }
            }
        }

        if (!empty($contactData) && !empty($contactData['first_name'])) {
            $contactData['is_primary'] = true;
        }

        return $contactData;
    }

    /**
     * Find existing client based on unique identifiers.
     */
    private function findExistingClient(array $clientData, string $companyId): ?Client
    {
        $query = Client::where('company_id', $companyId);

        // Check by SIREN
        if (!empty($clientData['siren'])) {
            $client = $query->where('siren', $clientData['siren'])->first();
            if ($client) return $client;
        }

        // Check by SIRET
        if (!empty($clientData['siret'])) {
            $client = $query->where('siret', $clientData['siret'])->first();
            if ($client) return $client;
        }

        // Check by VAT number
        if (!empty($clientData['vat_number'])) {
            $client = $query->where('vat_number', $clientData['vat_number'])->first();
            if ($client) return $client;
        }

        // Check by email if provided
        if (!empty($clientData['emails'])) {
            $email = $clientData['emails'][0]['email'];
            $client = $query->whereHas('emails', function ($q) use ($email) {
                $q->where('email', $email);
            })->first();
            if ($client) return $client;
        }

        return null;
    }

    /**
     * Create a new client.
     */
    private function createNewClient(array $clientData): Client
    {
        $createAction = new CreateClientAction();
        return $createAction->execute($clientData);
    }

    /**
     * Update an existing client.
     */
    private function updateExistingClient(Client $client, array $clientData): Client
    {
        $updateAction = new UpdateClientAction();
        return $updateAction->execute($client, $clientData);
    }
}
