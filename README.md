# FacturX

Application de gestion commerciale et facturation électronique pour TPE/PME.

## À propos

FacturX est une solution SaaS conçue pour permettre aux TPE/PME françaises de gérer leur cycle commercial et de se conformer à la réglementation française sur la facturation électronique.

## Calendrier réglementaire

- 1er septembre 2026 : Obligation de réception des factures électroniques pour toutes les entreprises
- 1er septembre 2027 : Obligation d'émission des factures électroniques pour les TPE/PME

## Technologies

- Backend : Laravel 10+ avec PHP 8.4
- Frontend : Vue.js 3 avec Nuxt 3
- Base de données : PostgreSQL

## Fonctionnalités

- Gestion complète du cycle commercial (clients, produits, devis, factures)
- Conformité avec la réglementation de facturation électronique française
- Connexion aux Plateformes de Dématérialisation Partenaires (PDP)
- Génération de factures aux formats réglementaires (UBL, CII, Factur-X)
- Tableaux de bord et rapports d'analyse

## Installation

```bash
# Cloner le dépôt
git clone https://github.com/tirexdj/facturx.git

# Se déplacer dans le répertoire
cd facturx

# Installer les dépendances
composer install
npm install

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Configurer votre base de données dans .env puis migrer
php artisan migrate

# Compiler les assets
npm run dev
```

## Tests

```bash
php artisan test
```

## A propos de Laravel

FacturX est construit avec Laravel, un framework d'application web avec une syntaxe élégante et expressive. Laravel facilite le développement en simplifiant les tâches courantes utilisées dans de nombreux projets web.

Laravel est accessible, puissant et fournit les outils nécessaires pour les applications robustes et à grande échelle.

## Licence

Informations sur la licence à définir.