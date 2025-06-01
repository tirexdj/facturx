<?php

/**
 * Script to check and fix test configuration issues
 * Run this script to identify and fix common test problems
 */

require 'vendor/autoload.php';

class TestConfigurationChecker
{
    private array $issues = [];
    private array $fixedFiles = [];

    public function checkTestConfiguration(): void
    {
        echo "🔍 Checking test configuration...\n\n";

        $this->checkTestFiles();
        $this->checkForDuplicateRefreshDatabase();
        $this->checkForIncompatibleTraits();
        
        $this->reportIssues();
        $this->suggestFixes();
    }

    private function checkTestFiles(): void
    {
        $testFiles = $this->findTestFiles();
        
        foreach ($testFiles as $file) {
            $this->analyzeTestFile($file);
        }
    }

    private function findTestFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('tests/'),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }

    private function analyzeTestFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $relativePath = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $filePath);

        // Check for duplicate RefreshDatabase
        if (preg_match('/use RefreshDatabase/', $content) && 
            preg_match('/extends BaseApiTest/', $content)) {
            $this->issues[] = [
                'type' => 'duplicate_refresh_database',
                'file' => $relativePath,
                'message' => 'Using RefreshDatabase trait while extending BaseApiTest (which already uses it)'
            ];
        }

        // Check for multiple conflicting traits
        $traitCount = preg_match_all('/use\s+.*RefreshDatabase|use\s+.*ApiTestTrait/', $content);
        if ($traitCount > 1) {
            $this->issues[] = [
                'type' => 'multiple_database_traits',
                'file' => $relativePath,
                'message' => 'Multiple database-related traits detected'
            ];
        }

        // Check for direct Sanctum usage instead of helper methods
        if (preg_match('/Sanctum::actingAs/', $content) && 
            preg_match('/extends BaseApiTest/', $content)) {
            $this->issues[] = [
                'type' => 'direct_sanctum_usage',
                'file' => $relativePath,
                'message' => 'Using Sanctum::actingAs directly instead of $this->actingAsUser() helper'
            ];
        }

        // Check for direct JSON calls instead of API helpers
        if (preg_match('/\$this->postJson|->getJson|->putJson/', $content) && 
            preg_match('/extends BaseApiTest/', $content)) {
            $this->issues[] = [
                'type' => 'direct_json_calls',
                'file' => $relativePath,
                'message' => 'Using direct JSON calls instead of API helper methods (apiPost, apiGet, etc.)'
            ];
        }
    }

    private function checkForDuplicateRefreshDatabase(): void
    {
        echo "📋 Checking for duplicate RefreshDatabase usage...\n";
    }

    private function checkForIncompatibleTraits(): void
    {
        echo "🔧 Checking for incompatible trait combinations...\n";
    }

    private function reportIssues(): void
    {
        if (empty($this->issues)) {
            echo "✅ No issues found!\n\n";
            return;
        }

        echo "⚠️  Found " . count($this->issues) . " issues:\n\n";

        $groupedIssues = [];
        foreach ($this->issues as $issue) {
            $groupedIssues[$issue['type']][] = $issue;
        }

        foreach ($groupedIssues as $type => $issues) {
            echo "🔴 " . ucwords(str_replace('_', ' ', $type)) . ":\n";
            foreach ($issues as $issue) {
                echo "   📁 {$issue['file']}: {$issue['message']}\n";
            }
            echo "\n";
        }
    }

    private function suggestFixes(): void
    {
        if (empty($this->issues)) {
            return;
        }

        echo "💡 Suggested fixes:\n\n";

        $fixSuggestions = [
            'duplicate_refresh_database' => [
                'Remove the RefreshDatabase trait from test classes that extend BaseApiTest',
                'BaseApiTest already includes RefreshDatabase, so it\'s not needed'
            ],
            'multiple_database_traits' => [
                'Use only one database trait per test class',
                'Prefer extending BaseApiTest over using individual traits'
            ],
            'direct_sanctum_usage' => [
                'Replace Sanctum::actingAs($user) with $this->actingAsUser($user)',
                'Use the helper method for consistency and better API'
            ],
            'direct_json_calls' => [
                'Replace $this->postJson() with $this->apiPost()',
                'Replace $this->getJson() with $this->apiGet()',
                'Use API helper methods for consistent headers and error handling'
            ]
        ];

        foreach ($this->issues as $issue) {
            $type = $issue['type'];
            if (isset($fixSuggestions[$type])) {
                echo "🔧 For {$type}:\n";
                foreach ($fixSuggestions[$type] as $suggestion) {
                    echo "   • {$suggestion}\n";
                }
                echo "\n";
            }
        }

        echo "🚀 Run the following commands to fix some issues automatically:\n";
        echo "   php artisan test:fix-database-traits\n";
        echo "   php artisan test:fix-api-calls\n\n";
    }

    public function autoFixDatabaseTraits(): bool
    {
        echo "🔧 Auto-fixing database trait issues...\n";
        
        $fixedCount = 0;
        foreach ($this->issues as $issue) {
            if ($issue['type'] === 'duplicate_refresh_database') {
                if ($this->fixDuplicateRefreshDatabase($issue['file'])) {
                    $fixedCount++;
                    $this->fixedFiles[] = $issue['file'];
                }
            }
        }

        if ($fixedCount > 0) {
            echo "✅ Fixed {$fixedCount} files\n";
            return true;
        }

        echo "ℹ️  No files needed fixing\n";
        return false;
    }

    private function fixDuplicateRefreshDatabase(string $relativePath): bool
    {
        $fullPath = getcwd() . DIRECTORY_SEPARATOR . $relativePath;
        $content = file_get_contents($fullPath);

        // Remove RefreshDatabase from use statement if BaseApiTest is extended
        if (preg_match('/extends BaseApiTest/', $content)) {
            $content = preg_replace('/use\s+RefreshDatabase,?\s*/', '', $content);
            $content = preg_replace('/,\s*RefreshDatabase/', '', $content);
            $content = preg_replace('/RefreshDatabase,?\s*/', '', $content);
            
            // Clean up any double commas or trailing commas
            $content = preg_replace('/,\s*,/', ',', $content);
            $content = preg_replace('/use\s*,/', 'use ', $content);
            $content = preg_replace('/,\s*;/', ';', $content);

            file_put_contents($fullPath, $content);
            return true;
        }

        return false;
    }
}

// Run the checker
$checker = new TestConfigurationChecker();
$checker->checkTestConfiguration();

// Auto-fix if --fix argument is provided
if (in_array('--fix', $argv)) {
    echo "\n🛠️  Running auto-fixes...\n\n";
    $checker->autoFixDatabaseTraits();
}

echo "\n✨ Test configuration check complete!\n";
