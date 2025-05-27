-- Script d'initialisation PostgreSQL pour FacturX
-- Ce script est exécuté lors de la première création du conteneur PostgreSQL

-- Création de la base de données de test
CREATE DATABASE facturx_test;

-- Création d'un utilisateur pour les tests
CREATE USER facturx_test WITH PASSWORD 'test_password';
GRANT ALL PRIVILEGES ON DATABASE facturx_test TO facturx_test;

-- Configuration pour améliorer les performances
ALTER SYSTEM SET shared_preload_libraries = 'pg_stat_statements';
ALTER SYSTEM SET pg_stat_statements.track = 'all';
ALTER SYSTEM SET log_statement = 'all';
ALTER SYSTEM SET log_min_duration_statement = 1000;

-- Configuration spécifique pour FacturX
-- Extensions utiles pour l'application
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "unaccent";

-- Configuration pour les recherches full-text en français
CREATE TEXT SEARCH CONFIGURATION french_facturx ( COPY = french );

-- Accordar les privilèges sur les extensions
GRANT USAGE ON SCHEMA public TO facturx;
GRANT USAGE ON SCHEMA public TO facturx_test;

-- Configuration des paramètres de performance
ALTER DATABASE facturx SET timezone TO 'Europe/Paris';
ALTER DATABASE facturx_test SET timezone TO 'Europe/Paris';

-- Index pour améliorer les performances des requêtes communes
-- Ces index seront créés automatiquement par les migrations Laravel
-- mais on peut préparer quelques optimisations globales

-- Configuration de logging pour le développement
ALTER SYSTEM SET log_destination = 'stderr';
ALTER SYSTEM SET logging_collector = on;
ALTER SYSTEM SET log_directory = 'pg_log';
ALTER SYSTEM SET log_filename = 'postgresql-%Y-%m-%d_%H%M%S.log';
ALTER SYSTEM SET log_rotation_age = '1d';
ALTER SYSTEM SET log_rotation_size = '100MB';

-- Rechargement de la configuration
SELECT pg_reload_conf();

-- Informations de débogage
\echo 'Base de données FacturX initialisée avec succès'
\echo 'Extensions disponibles:'
SELECT name FROM pg_available_extensions WHERE name IN ('uuid-ossp', 'pg_trgm', 'unaccent');
