<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Client\Client;
use App\Domain\ValueObject\Identifier;

interface ClientRepository
{
    /**
     * Find a client by its identifier
     */
    public function findById(Identifier $id): ?Client;
    
    /**
     * Find clients by company ID
     * 
     * @return Client[]
     */
    public function findByCompanyId(Identifier $companyId): array;
    
    /**
     * Find a client by SIREN
     */
    public function findBySiren(string $siren): ?Client;
    
    /**
     * Find a client by SIRET
     */
    public function findBySiret(string $siret): ?Client;
    
    /**
     * Save a client
     */
    public function save(Client $client): void;
    
    /**
     * Delete a client
     */
    public function delete(Client $client): void;
}
