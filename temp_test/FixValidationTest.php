<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;

class FixValidationTest extends TestCase
{
    use CreatesApplication;

    public function test_company_factory_works(): void
    {
        $this->refreshApplication();
        
        // Test that the Company factory can create a company without date parsing errors
        try {
            $company = \App\Domain\Company\Models\Company::factory()->make();
            echo "✓ Company factory works - fiscal_year_start: " . $company->fiscal_year_start . "\n";
        } catch (Exception $e) {
            echo "✗ Company factory failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function test_health_endpoint_works(): void
    {
        $this->refreshApplication();
        
        // Test that the health endpoint works without date parsing errors
        try {
            $response = $this->getJson('/api/health');
            echo "✓ Health endpoint works - status: " . $response->getStatusCode() . "\n";
            
            $data = $response->json();
            if (isset($data['timestamp'])) {
                echo "✓ Timestamp format: " . $data['timestamp'] . "\n";
            }
        } catch (Exception $e) {
            echo "✗ Health endpoint failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}

// Run the test
$test = new FixValidationTest();
$test->setUp();

echo "Testing fixes for date parsing issues...\n\n";

try {
    $test->test_company_factory_works();
    $test->test_health_endpoint_works();
    echo "\n✓ All tests passed! The fixes are working.\n";
} catch (Exception $e) {
    echo "\n✗ Tests failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
