<?php

namespace Tests\Unit\Services\Customer;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Customer\ClientAddressService;
use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use App\Domain\Customer\Models\ClientAddress;

class ClientAddressServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClientAddressService $addressService;
    private Company $company;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addressService = new ClientAddressService();
        $this->company = Company::factory()->create();
        $this->client = Client::factory()->for($this->company)->create();
    }

    /** @test */
    public function it_creates_client_address_successfully()
    {
        $addressData = [
            'type' => 'billing',
            'line1' => '123 Rue de la Paix',
            'line2' => 'Apt 42',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'FR',
            'is_default' => true,
        ];

        $address = $this->addressService->createAddress($this->client, $addressData);

        $this->assertInstanceOf(ClientAddress::class, $address);
        $this->assertEquals('billing', $address->type);
        $this->assertEquals('123 Rue de la Paix', $address->line1);
        $this->assertEquals('Apt 42', $address->line2);
        $this->assertEquals('Paris', $address->city);
        $this->assertEquals('75001', $address->postal_code);
        $this->assertEquals('FR', $address->country);
        $this->assertTrue($address->is_default);
        $this->assertEquals($this->client->id, $address->client_id);
    }

    /** @test */
    public function it_sets_first_address_as_default()
    {
        $addressData = [
            'type' => 'billing',
            'line1' => '123 Rue Test',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'FR',
            // is_default not specified
        ];

        $address = $this->addressService->createAddress($this->client, $addressData);

        $this->assertTrue($address->is_default);
    }

    /** @test */
    public function it_ensures_only_one_default_per_type()
    {
        // Create first billing address
        $firstAddress = $this->addressService->createAddress($this->client, [
            'type' => 'billing',
            'line1' => 'First Address',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'FR',
            'is_default' => true,
        ]);

        // Create second billing address as default
        $secondAddress = $this->addressService->createAddress($this->client, [
            'type' => 'billing',
            'line1' => 'Second Address',
            'city' => 'Lyon',
            'postal_code' => '69000',
            'country' => 'FR',
            'is_default' => true,
        ]);

        // Refresh first address from database
        $firstAddress->refresh();

        $this->assertFalse($firstAddress->is_default);
        $this->assertTrue($secondAddress->is_default);
    }

    /** @test */
    public function it_allows_multiple_default_addresses_of_different_types()
    {
        $billingAddress = $this->addressService->createAddress($this->client, [
            'type' => 'billing',
            'line1' => 'Billing Address',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'FR',
            'is_default' => true,
        ]);

        $shippingAddress = $this->addressService->createAddress($this->client, [
            'type' => 'shipping',
            'line1' => 'Shipping Address',
            'city' => 'Marseille',
            'postal_code' => '13000',
            'country' => 'FR',
            'is_default' => true,
        ]);

        $this->assertTrue($billingAddress->is_default);
        $this->assertTrue($shippingAddress->is_default);
    }

    /** @test */
    public function it_updates_address_successfully()
    {
        $address = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'line1' => 'Old Address',
                'city' => 'Old City',
            ]);

        $updateData = [
            'line1' => 'New Address',
            'line2' => 'New Line 2',
            'city' => 'New City',
            'postal_code' => '12345',
        ];

        $updatedAddress = $this->addressService->updateAddress($address, $updateData);

        $this->assertEquals('New Address', $updatedAddress->line1);
        $this->assertEquals('New Line 2', $updatedAddress->line2);
        $this->assertEquals('New City', $updatedAddress->city);
        $this->assertEquals('12345', $updatedAddress->postal_code);
        
        // Type should remain unchanged
        $this->assertEquals('billing', $updatedAddress->type);
    }

    /** @test */
    public function it_handles_default_change_on_update()
    {
        $defaultAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'is_default' => true,
            ]);

        $otherAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'is_default' => false,
            ]);

        // Make the other address default
        $this->addressService->updateAddress($otherAddress, ['is_default' => true]);

        $defaultAddress->refresh();
        $otherAddress->refresh();

        $this->assertFalse($defaultAddress->is_default);
        $this->assertTrue($otherAddress->is_default);
    }

    /** @test */
    public function it_sets_address_as_default()
    {
        $defaultAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'is_default' => true,
            ]);

        $otherAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'is_default' => false,
            ]);

        $this->addressService->setAsDefault($otherAddress);

        $defaultAddress->refresh();
        $otherAddress->refresh();

        $this->assertFalse($defaultAddress->is_default);
        $this->assertTrue($otherAddress->is_default);
    }

    /** @test */
    public function it_deletes_address_successfully()
    {
        $keepAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'is_default' => true,
            ]);

        $deleteAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'shipping',
                'is_default' => false,
            ]);

        $result = $this->addressService->deleteAddress($deleteAddress);

        $this->assertTrue($result);
        $this->assertSoftDeleted('client_addresses', [
            'id' => $deleteAddress->id,
        ]);

        // Keep address should remain
        $this->assertDatabaseHas('client_addresses', [
            'id' => $keepAddress->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_prevents_deletion_of_last_default_address()
    {
        $onlyAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'is_default' => true,
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Impossible de supprimer la seule adresse par défaut');

        $this->addressService->deleteAddress($onlyAddress);
    }

    /** @test */
    public function it_allows_deletion_of_default_address_when_others_exist()
    {
        $defaultAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'is_default' => true,
            ]);

        $otherAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'is_default' => false,
            ]);

        $result = $this->addressService->deleteAddress($defaultAddress);

        $this->assertTrue($result);
        $this->assertSoftDeleted('client_addresses', [
            'id' => $defaultAddress->id,
        ]);

        // Other address should become default
        $otherAddress->refresh();
        $this->assertTrue($otherAddress->is_default);
    }

    /** @test */
    public function it_gets_default_address_by_type()
    {
        $billingAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'is_default' => true,
            ]);

        $shippingAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'shipping',
                'is_default' => true,
            ]);

        ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'is_default' => false,
            ]);

        $defaultBilling = $this->addressService->getDefaultAddress($this->client, 'billing');
        $defaultShipping = $this->addressService->getDefaultAddress($this->client, 'shipping');

        $this->assertEquals($billingAddress->id, $defaultBilling->id);
        $this->assertEquals($shippingAddress->id, $defaultShipping->id);
    }

    /** @test */
    public function it_returns_null_when_no_default_address_exists()
    {
        $defaultAddress = $this->addressService->getDefaultAddress($this->client, 'billing');

        $this->assertNull($defaultAddress);
    }

    /** @test */
    public function it_gets_addresses_by_type()
    {
        ClientAddress::factory()
            ->for($this->client)
            ->count(2)
            ->create(['type' => 'billing']);

        ClientAddress::factory()
            ->for($this->client)
            ->count(1)
            ->create(['type' => 'shipping']);

        $billingAddresses = $this->addressService->getAddressesByType($this->client, 'billing');
        $shippingAddresses = $this->addressService->getAddressesByType($this->client, 'shipping');

        $this->assertCount(2, $billingAddresses);
        $this->assertCount(1, $shippingAddresses);

        foreach ($billingAddresses as $address) {
            $this->assertEquals('billing', $address->type);
        }

        foreach ($shippingAddresses as $address) {
            $this->assertEquals('shipping', $address->type);
        }
    }

    /** @test */
    public function it_validates_address_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type d\'adresse invalide');

        $this->addressService->createAddress($this->client, [
            'type' => 'invalid_type',
            'line1' => '123 Test Street',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'FR',
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Champs requis manquants');

        $this->addressService->createAddress($this->client, [
            'type' => 'billing',
            // Missing required fields
        ]);
    }

    /** @test */
    public function it_validates_postal_code_format()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Format de code postal invalide');

        $this->addressService->createAddress($this->client, [
            'type' => 'billing',
            'line1' => '123 Test Street',
            'city' => 'Test City',
            'postal_code' => 'invalid',
            'country' => 'FR',
        ]);
    }

    /** @test */
    public function it_normalizes_address_data()
    {
        $addressData = [
            'type' => 'billing',
            'line1' => '  123 rue de la paix  ',
            'city' => '  PARIS  ',
            'postal_code' => ' 75001 ',
            'country' => 'fr',
        ];

        $address = $this->addressService->createAddress($this->client, $addressData);

        $this->assertEquals('123 rue de la paix', $address->line1);
        $this->assertEquals('PARIS', $address->city);
        $this->assertEquals('75001', $address->postal_code);
        $this->assertEquals('FR', $address->country);
    }

    /** @test */
    public function it_duplicates_address()
    {
        $originalAddress = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'line1' => 'Original Address',
                'city' => 'Original City',
                'is_default' => true,
            ]);

        $duplicatedAddress = $this->addressService->duplicateAddress($originalAddress, 'shipping');

        $this->assertNotEquals($originalAddress->id, $duplicatedAddress->id);
        $this->assertEquals('shipping', $duplicatedAddress->type);
        $this->assertEquals('Original Address', $duplicatedAddress->line1);
        $this->assertEquals('Original City', $duplicatedAddress->city);
        $this->assertEquals($this->client->id, $duplicatedAddress->client_id);
        
        // Original remains default billing, duplicate becomes default shipping
        $this->assertTrue($originalAddress->is_default);
        $this->assertTrue($duplicatedAddress->is_default);
    }

    /** @test */
    public function it_formats_address_for_display()
    {
        $address = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'line1' => '123 Rue de la Paix',
                'line2' => 'Apt 42',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'FR',
            ]);

        $formatted = $this->addressService->formatForDisplay($address);

        $expected = "123 Rue de la Paix\nApt 42\n75001 Paris\nFrance";
        $this->assertEquals($expected, $formatted);
    }

    /** @test */
    public function it_formats_address_for_display_without_line2()
    {
        $address = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'line1' => '123 Rue de la Paix',
                'line2' => null,
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'FR',
            ]);

        $formatted = $this->addressService->formatForDisplay($address);

        $expected = "123 Rue de la Paix\n75001 Paris\nFrance";
        $this->assertEquals($expected, $formatted);
    }

    /** @test */
    public function it_validates_country_code()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Code pays invalide');

        $this->addressService->createAddress($this->client, [
            'type' => 'billing',
            'line1' => '123 Test Street',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'INVALID',
        ]);
    }

    /** @test */
    public function it_bulk_creates_addresses()
    {
        $addressesData = [
            [
                'type' => 'billing',
                'line1' => 'Billing Address',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'FR',
                'is_default' => true,
            ],
            [
                'type' => 'shipping',
                'line1' => 'Shipping Address',
                'city' => 'Lyon',
                'postal_code' => '69000',
                'country' => 'FR',
                'is_default' => true,
            ],
        ];

        $addresses = $this->addressService->createMultiple($this->client, $addressesData);

        $this->assertCount(2, $addresses);
        $this->assertEquals('billing', $addresses[0]->type);
        $this->assertEquals('shipping', $addresses[1]->type);
        $this->assertTrue($addresses[0]->is_default);
        $this->assertTrue($addresses[1]->is_default);
    }
}
