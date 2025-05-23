<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Produit\Produit;
use App\Domain\ValueObject\Identifier;

interface ProductRepository
{
    /**
     * Find a product by its identifier
     */
    public function findById(Identifier $id): ?Produit;
    
    /**
     * Find products by company ID
     * 
     * @return Produit[]
     */
    public function findByCompanyId(Identifier $companyId): array;
    
    /**
     * Find a product by reference
     */
    public function findByReference(string $reference): ?Produit;
    
    /**
     * Find products by category ID
     * 
     * @return Produit[]
     */
    public function findByCategoryId(Identifier $categoryId): array;
    
    /**
     * Find active products
     * 
     * @return Produit[]
     */
    public function findActive(Identifier $companyId): array;
    
    /**
     * Save a product
     */
    public function save(Produit $produit): void;
    
    /**
     * Delete a product
     */
    public function delete(Produit $produit): void;
}
