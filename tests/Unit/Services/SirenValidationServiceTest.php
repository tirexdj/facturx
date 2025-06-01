<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\SirenValidationService;
use App\Services\PPFApiService;
use Mockery;

class SirenValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private SirenValidationService $sirenService;
    private PPFApiService $ppfApiService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ppfApiService = Mockery::mock(PPFApiService::class);
        $this->sirenService = new SirenValidationService($this->ppfApiService);
    }

    /** @test */
    public function it_validates_correct_siren_format()
    {
        $this->assertTrue($this->sirenService->isValidSirenFormat('123456789'));
        $this->assertFalse($this->sirenService->isValidSirenFormat('12345678')); // Too short
        $this->assertFalse($this->sirenService->isValidSirenFormat('1234567890')); // Too long
        $this->assertFalse($this->sirenService->isValidSirenFormat('12345678a')); // Contains letter
        $this->assertFalse($this->sirenService->isValidSirenFormat('')); // Empty
    }

    /** @test */
    public function it_validates_correct_siret_format()
    {
        $this->assertTrue($this->sirenService->isValidSiretFormat('12345678901234'));
        $this->assertFalse($this->sirenService->isValidSiretFormat('1234567890123')); // Too short
        $this->assertFalse($this->sirenService->isValidSiretFormat('123456789012345')); // Too long
        $this->assertFalse($this->sirenService->isValidSiretFormat('1234567890123a')); // Contains letter
        $this->assertFalse($this->sirenService->isValidSiretFormat('')); // Empty
    }

    /** @test */
    public function it_validates_siren_checksum()
    {
        // Test avec des numéros SIREN valides (algorithme de Luhn)
        $this->assertTrue($this->sirenService->isValidSirenChecksum('732829320')); // La Poste
        $this->assertTrue($this->sirenService->isValidSirenChecksum('552120222')); // SNCF
        
        // Test avec des numéros SIREN invalides
        $this->assertFalse($this->sirenService->isValidSirenChecksum('123456789'));
        $this->assertFalse($this->sirenService->isValidSirenChecksum('000000000'));
    }

    /** @test */
    public function it_validates_siret_checksum()
    {
        // Test avec des numéros SIRET valides
        $this->assertTrue($this->sirenService->isValidSiretChecksum('73282932000074')); // La Poste siège
        $this->assertTrue($this->sirenService->isValidSiretChecksum('55212022200025')); // SNCF siège
        
        // Test avec des numéros SIRET invalides
        $this->assertFalse($this->sirenService->isValidSiretChecksum('12345678901234'));
        $this->assertFalse($this->sirenService->isValidSiretChecksum('00000000000000'));
    }

    /** @test */
    public function it_extracts_siren_from_siret()
    {
        $this->assertEquals('123456789', $this->sirenService->extractSirenFromSiret('12345678901234'));
        $this->assertEquals('732829320', $this->sirenService->extractSirenFromSiret('73282932000074'));
    }

    /** @test */
    public function it_validates_siren_siret_consistency()
    {
        // SIREN et SIRET cohérents
        $this->assertTrue($this->sirenService->areSirenSiretConsistent('123456789', '12345678901234'));
        $this->assertTrue($this->sirenService->areSirenSiretConsistent('732829320', '73282932000074'));
        
        // SIREN et SIRET incohérents
        $this->assertFalse($this->sirenService->areSirenSiretConsistent('123456789', '98765432101234'));
        $this->assertFalse($this->sirenService->areSirenSiretConsistent('732829320', '55212022200025'));
    }

    /** @test */
    public function it_validates_siren_with_ppf_api()
    {
        $siren = '732829320';
        
        $this->ppfApiService->shouldReceive('validateSiren')
            ->once()
            ->with($siren)
            ->andReturn([
                'valid' => true,
                'data' => [
                    'siren' => $siren,
                    'raisonSociale' => 'LA POSTE',
                    'typeEntite' => 'Publique',
                    'etatAdministratif' => 'A',
                ]
            ]);

        $result = $this->sirenService->validateSirenWithPPF($siren);

        $this->assertTrue($result['valid']);
        $this->assertEquals('LA POSTE', $result['data']['raisonSociale']);
    }

    /** @test */
    public function it_handles_ppf_api_validation_failure()
    {
        $siren = '123456789';
        
        $this->ppfApiService->shouldReceive('validateSiren')
            ->once()
            ->with($siren)
            ->andReturn([
                'valid' => false,
                'message' => 'SIREN non trouvé dans l\'annuaire'
            ]);

        $result = $this->sirenService->validateSirenWithPPF($siren);

        $this->assertFalse($result['valid']);
        $this->assertEquals('SIREN non trouvé dans l\'annuaire', $result['message']);
    }

    /** @test */
    public function it_validates_siret_with_ppf_api()
    {
        $siret = '73282932000074';
        
        $this->ppfApiService->shouldReceive('validateSiret')
            ->once()
            ->with($siret)
            ->andReturn([
                'valid' => true,
                'data' => [
                    'siret' => $siret,
                    'siren' => '732829320',
                    'denomination' => 'LA POSTE',
                    'typeEtablissement' => 'P',
                    'etatAdministratif' => 'A',
                ]
            ]);

        $result = $this->sirenService->validateSiretWithPPF($siret);

        $this->assertTrue($result['valid']);
        $this->assertEquals('LA POSTE', $result['data']['denomination']);
    }

    /** @test */
    public function it_validates_complete_siren_siret_data()
    {
        $siren = '732829320';
        $siret = '73282932000074';

        // Mock successful API responses
        $this->ppfApiService->shouldReceive('validateSiren')
            ->once()
            ->with($siren)
            ->andReturn([
                'valid' => true,
                'data' => [
                    'siren' => $siren,
                    'raisonSociale' => 'LA POSTE',
                    'typeEntite' => 'Publique',
                ]
            ]);

        $this->ppfApiService->shouldReceive('validateSiret')
            ->once()
            ->with($siret)
            ->andReturn([
                'valid' => true,
                'data' => [
                    'siret' => $siret,
                    'siren' => $siren,
                    'denomination' => 'LA POSTE',
                    'etatAdministratif' => 'A',
                ]
            ]);

        $result = $this->sirenService->validateSirenSiret($siren, $siret);

        $this->assertTrue($result['valid']);
        $this->assertEquals('LA POSTE', $result['data']['raisonSociale']);
        $this->assertEquals('LA POSTE', $result['data']['denomination']);
    }

    /** @test */
    public function it_fails_validation_for_inconsistent_siren_siret()
    {
        $siren = '732829320';
        $siret = '55212022200025'; // Different SIREN

        $result = $this->sirenService->validateSirenSiret($siren, $siret);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('SIREN et SIRET incohérents', $result['message']);
    }

    /** @test */
    public function it_fails_validation_for_invalid_siren_format()
    {
        $result = $this->sirenService->validateSirenSiret('12345', null);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Format SIREN invalide', $result['message']);
    }

    /** @test */
    public function it_fails_validation_for_invalid_siret_format()
    {
        $result = $this->sirenService->validateSirenSiret(null, '12345');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Format SIRET invalide', $result['message']);
    }

    /** @test */
    public function it_fails_validation_for_invalid_siren_checksum()
    {
        $result = $this->sirenService->validateSirenSiret('123456789', null);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Clé de contrôle SIREN invalide', $result['message']);
    }

    /** @test */
    public function it_fails_validation_for_invalid_siret_checksum()
    {
        $result = $this->sirenService->validateSirenSiret(null, '12345678901234');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Clé de contrôle SIRET invalide', $result['message']);
    }

    /** @test */
    public function it_validates_only_siren_when_siret_not_provided()
    {
        $siren = '732829320';

        $this->ppfApiService->shouldReceive('validateSiren')
            ->once()
            ->with($siren)
            ->andReturn([
                'valid' => true,
                'data' => [
                    'siren' => $siren,
                    'raisonSociale' => 'LA POSTE',
                    'typeEntite' => 'Publique',
                ]
            ]);

        $result = $this->sirenService->validateSirenSiret($siren, null);

        $this->assertTrue($result['valid']);
        $this->assertEquals('LA POSTE', $result['data']['raisonSociale']);
    }

    /** @test */
    public function it_validates_only_siret_when_siren_not_provided()
    {
        $siret = '73282932000074';

        $this->ppfApiService->shouldReceive('validateSiret')
            ->once()
            ->with($siret)
            ->andReturn([
                'valid' => true,
                'data' => [
                    'siret' => $siret,
                    'siren' => '732829320',
                    'denomination' => 'LA POSTE',
                ]
            ]);

        $result = $this->sirenService->validateSirenSiret(null, $siret);

        $this->assertTrue($result['valid']);
        $this->assertEquals('LA POSTE', $result['data']['denomination']);
    }

    /** @test */
    public function it_handles_ppf_api_errors_gracefully()
    {
        $siren = '732829320';

        $this->ppfApiService->shouldReceive('validateSiren')
            ->once()
            ->with($siren)
            ->andThrow(new \Exception('API temporairement indisponible'));

        $result = $this->sirenService->validateSirenSiret($siren, null);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Erreur lors de la validation', $result['message']);
    }

    /** @test */
    public function it_returns_appropriate_error_when_no_siren_or_siret_provided()
    {
        $result = $this->sirenService->validateSirenSiret(null, null);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('SIREN ou SIRET requis', $result['message']);
    }

    /** @test */
    public function it_cleans_siren_siret_numbers()
    {
        // Test avec des espaces et tirets
        $this->assertEquals('123456789', $this->sirenService->cleanSirenSiret('123 456 789'));
        $this->assertEquals('123456789', $this->sirenService->cleanSirenSiret('123-456-789'));
        $this->assertEquals('12345678901234', $this->sirenService->cleanSirenSiret('123 456 789 01234'));
        $this->assertEquals('12345678901234', $this->sirenService->cleanSirenSiret('123-456-789-01234'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
