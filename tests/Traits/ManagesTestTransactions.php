<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Exception;

trait ManagesTestTransactions
{
    /**
     * Setup hook pour nettoyer les transactions.
     */
    protected function setUpManagesTestTransactions(): void
    {
        $this->cleanupActiveTransactions();
        $this->configureSqliteForTesting();
        $this->ensureFreshDatabase();
    }

    /**
     * Teardown hook pour nettoyer les transactions.
     */
    protected function tearDownManagesTestTransactions(): void
    {
        $this->cleanupActiveTransactions();
    }

    /**
     * S'assurer que la base de données est fraîche.
     */
    protected function ensureFreshDatabase(): void
    {
        if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:') {
            try {
                // Pour SQLite en mémoire, vider les données mais garder la structure
                $this->truncateAllTables();
            } catch (Exception $e) {
                // En cas d'erreur, purger complètement la connexion
                DB::purge();
                DB::reconnect();
            }
        }
    }
    
    /**
     * Vide toutes les tables sans les supprimer.
     */
    protected function truncateAllTables(): void
    {
        try {
            // Désactiver temporairement les clés étrangères
            DB::statement('PRAGMA foreign_keys=OFF');
            
            // Obtenir toutes les tables utilisateur
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations'");
            
            // Vider chaque table
            foreach ($tables as $table) {
                DB::statement("DELETE FROM `{$table->name}`");
            }
            
            // Réactiver les clés étrangères
            DB::statement('PRAGMA foreign_keys=ON');
        } catch (Exception $e) {
            // En cas d'erreur, réactiver les clés étrangères et relancer l'exception
            DB::statement('PRAGMA foreign_keys=ON');
            throw $e;
        }
    }

    /**
     * Configure SQLite pour les tests.
     */
    protected function configureSqliteForTesting(): void
    {
        if (config('database.default') === 'sqlite') {
            try {
                // Activer les clés étrangères pour SQLite
                DB::statement('PRAGMA foreign_keys=ON');
                // Optimiser SQLite pour les tests
                DB::statement('PRAGMA synchronous=OFF');
                DB::statement('PRAGMA journal_mode=MEMORY');
                DB::statement('PRAGMA temp_store=MEMORY');
                DB::statement('PRAGMA cache_size=10000');
            } catch (Exception $e) {
                // Ignorer les erreurs de configuration SQLite
            }
        }
    }

    /**
     * Nettoie toutes les transactions actives.
     */
    protected function cleanupActiveTransactions(): void
    {
        try {
            // Fermer toutes les transactions ouvertes
            $attempts = 0;
            while (DB::transactionLevel() > 0 && $attempts < 10) {
                DB::rollBack();
                $attempts++;
            }
            
            // Si encore des transactions ouvertes, reconnecter
            if (DB::transactionLevel() > 0) {
                DB::disconnect();
                DB::reconnect();
                $this->configureSqliteForTesting();
            }
        } catch (Exception $e) {
            // Reconnecter en cas d'erreur
            try {
                DB::disconnect();
                DB::reconnect();
                $this->configureSqliteForTesting();
            } catch (Exception $reconnectException) {
                // Dernière tentative avec purge complète
                $this->resetDatabaseConnection();
            }
        }
    }

    /**
     * Reset complet de la connexion de base de données.
     */
    protected function resetDatabaseConnection(): void
    {
        try {
            DB::purge();
            DB::reconnect();
            $this->configureSqliteForTesting();
        } catch (Exception $e) {
            // Si tout échoue, on continue avec l'avertissement
            $this->markTestSkipped('Impossible de réinitialiser la connexion de base de données: ' . $e->getMessage());
        }
    }

    /**
     * Démarrer une transaction proprement.
     */
    protected function beginCleanTransaction(): void
    {
        $this->cleanupActiveTransactions();
        DB::beginTransaction();
    }

    /**
     * Rollback sécurisé.
     */
    protected function safeRollback(): void
    {
        try {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        } catch (Exception $e) {
            $this->resetDatabaseConnection();
        }
    }

    /**
     * Commit sécurisé.
     */
    protected function safeCommit(): void
    {
        try {
            if (DB::transactionLevel() > 0) {
                DB::commit();
            }
        } catch (Exception $e) {
            $this->safeRollback();
            throw $e;
        }
    }

    /**
     * Vérifie si la base de données est accessible.
     */
    protected function isDatabaseAccessible(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Vérification de la santé de la connexion DB avant un test.
     */
    protected function ensureDatabaseHealth(): void
    {
        if (!$this->isDatabaseAccessible()) {
            $this->resetDatabaseConnection();
            
            if (!$this->isDatabaseAccessible()) {
                $this->markTestSkipped('Base de données inaccessible');
            }
        }
    }

    /**
     * Nettoie complètement la base de données de test.
     */
    protected function wipeDatabase(): void
    {
        if (config('database.default') === 'sqlite') {
            try {
                // Vider les données au lieu de supprimer les tables
                $this->truncateAllTables();
            } catch (Exception $e) {
                // En cas d'erreur, reconnecter
                $this->resetDatabaseConnection();
            }
        }
    }
    
    /**
     * Supprime complètement toutes les tables SQLite (à utiliser seulement en cas d'urgence).
     */
    protected function dropAllSqliteTables(): void
    {
        if (config('database.default') === 'sqlite') {
            try {
                // Obtenir toutes les tables
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                
                // Supprimer toutes les tables
                foreach ($tables as $table) {
                    DB::statement("DROP TABLE IF EXISTS `{$table->name}`");
                }
            } catch (Exception $e) {
                // En cas d'erreur, reconnecter
                $this->resetDatabaseConnection();
            }
        }
    }
}
