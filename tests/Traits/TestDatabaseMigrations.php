<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait TestDatabaseMigrations
{
    /**
     * Ensure database is fresh and migrations are run.
     */
    protected function setupTestDatabase(): void
    {
        $this->ensureDatabaseIsClean();
        $this->runMigrations();
    }

    /**
     * Run migrations for tests.
     */
    protected function runMigrations(): void
    {
        try {
            // Force run migrations for testing environment
            Artisan::call('migrate', [
                '--env' => 'testing',
                '--force' => true,
                '--no-interaction' => true,
            ]);
            
            // Run test seeders
            $this->runTestSeeders();
            
        } catch (\Exception $e) {
            // If migration fails, try fresh migration
            Artisan::call('migrate:fresh', [
                '--env' => 'testing',
                '--force' => true,
                '--no-interaction' => true,
            ]);
            
            // Run test seeders after fresh migration
            $this->runTestSeeders();
        }
    }

    /**
     * Ensure database is clean before tests.
     */
    protected function ensureDatabaseIsClean(): void
    {
        if (config('database.default') === 'sqlite' && 
            config('database.connections.sqlite.database') === ':memory:') {
            // For in-memory SQLite, database is always clean
            return;
        }

        try {
            // Drop all tables to ensure clean state
            $this->dropAllTables();
        } catch (\Exception $e) {
            // Ignore errors when dropping tables that don't exist
        }
    }

    /**
     * Drop all tables in the database.
     */
    protected function dropAllTables(): void
    {
        $connection = config('database.default');
        
        if ($connection === 'sqlite') {
            $this->dropSqliteTables();
        } elseif ($connection === 'mysql') {
            $this->dropMysqlTables();
        } elseif ($connection === 'pgsql') {
            $this->dropPostgresTables();
        }
    }

    /**
     * Drop all SQLite tables.
     */
    protected function dropSqliteTables(): void
    {
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        
        Schema::disableForeignKeyConstraints();
        
        foreach ($tables as $table) {
            Schema::dropIfExists($table->name);
        }
        
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Drop all MySQL tables.
     */
    protected function dropMysqlTables(): void
    {
        $database = config('database.connections.mysql.database');
        $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$database]);
        
        Schema::disableForeignKeyConstraints();
        
        foreach ($tables as $table) {
            Schema::dropIfExists($table->table_name);
        }
        
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Drop all PostgreSQL tables.
     */
    protected function dropPostgresTables(): void
    {
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        
        Schema::disableForeignKeyConstraints();
        
        foreach ($tables as $table) {
            Schema::dropIfExists($table->tablename);
        }
        
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Check if essential tables exist.
     */
    protected function ensureEssentialTablesExist(): bool
    {
        $essentialTables = [
            'users',
            'companies',
            'plans',
            'roles',
            'permissions',
        ];

        foreach ($essentialTables as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Run test seeders.
     */
    protected function runTestSeeders(): void
    {
        try {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\TestSeeder',
                '--env' => 'testing',
                '--force' => true,
                '--no-interaction' => true,
            ]);
        } catch (\Exception $e) {
            // Ignore seeder errors for now - they might not be critical
            // In production, you might want to log this
        }
    }

    /**
     * Verify database health.
     */
    protected function verifyDatabaseHealth(): void
    {
        try {
            // Test basic database connection
            DB::connection()->getPdo();
            
            // Ensure essential tables exist
            if (!$this->ensureEssentialTablesExist()) {
                throw new \Exception('Essential tables are missing');
            }
        } catch (\Exception $e) {
            throw new \Exception("Database health check failed: " . $e->getMessage());
        }
    }
}
