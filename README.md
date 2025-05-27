# FacturX 🧾

[![Laravel Version](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://github.com/votre-username/facturx/workflows/Laravel%20CI/badge.svg)](https://github.com/votre-username/facturx/actions)

**FacturX** est une solution SaaS moderne de gestion commerciale et facturation électronique conçue spécifiquement pour les TPE/PME françaises. Elle permet de se conformer à la réglementation française sur la facturation électronique tout en simplifiant la gestion quotidienne.

## 🎯 Objectifs

- 📋 **Gestion complète** : Clients, produits, devis, factures
- ⚡ **Facturation électronique** : Conformité totale avec la réglementation française
- 🔄 **E-reporting** : Transmission automatique des données fiscales
- 📊 **Analytics** : Tableaux de bord et rapports détaillés
- 🔐 **Sécurité** : Conformité RGPD et sécurité renforcée

## 🏗️ Architecture

### Stack technique

- **Backend** : Laravel 12, PHP 8.4
- **Frontend** : Vue.js 3, Nuxt 3, TypeScript
- **Base de données** : PostgreSQL 16
- **Cache** : Redis 7
- **Infrastructure** : Docker, Kubernetes
- **CI/CD** : GitHub Actions

### Conformité réglementaire

- ✅ **Formats normalisés** : UBL, CII, Factur-X
- ✅ **PPF** : Intégration avec le Portail Public de Facturation
- ✅ **PDP** : Connexion aux Plateformes de Dématérialisation Partenaires
- ✅ **E-reporting** : Transmission automatique des données B2C et internationales
- ✅ **Archivage** : Conformité légale (10 ans)

## 🚀 Installation rapide

### Prérequis

- Docker et Docker Compose
- Git
- Make (optionnel mais recommandé)

### Installation avec Make

```bash
# Cloner le repository
git clone https://github.com/votre-username/facturx.git
cd facturx

# Installation complète
make install

# Démarrer l'environnement de développement
make dev
```

### Installation manuelle

```bash
# Construire et démarrer les conteneurs
docker-compose up -d

# Installer les dépendances PHP
docker-compose exec app composer install

# Configurer l'environnement
docker-compose exec app cp .env.example .env
docker-compose exec app php artisan key:generate

# Migrer la base de données
docker-compose exec app php artisan migrate --seed

# Construire les assets frontend
docker-compose exec node npm install
docker-compose exec node npm run build
```

L'application sera accessible sur : [http://localhost:8000](http://localhost:8000)

## 📋 Fonctionnalités principales

### 👥 Gestion des clients
- Fiches clients complètes (B2B et B2C)
- Validation automatique SIREN/SIRET via l'annuaire PPF
- Historique des interactions et transactions
- Segmentation et catégorisation

### 📦 Catalogue produits/services
- Gestion illimitée de produits et services
- Multi-tarifs et remises
- Catégorisation avancée
- Gestion des variantes

### 💰 Devis et facturation
- Création intuitive de devis et factures
- Conversion automatique devis → facture
- Facturation récurrente
- Templates personnalisables

### ⚡ Facturation électronique
- **Conformité 2026/2027** : Respect du calendrier réglementaire
- **Formats normalisés** : UBL, CII, Factur-X automatiques
- **Transmission automatique** : Via PDP partenaires
- **Cycle de vie complet** : Suivi des statuts obligatoires
- **Archivage légal** : Conservation conforme (10 ans)

### 📊 E-reporting automatique
- **Transactions B2C** : Agrégation quotidienne automatique
- **Opérations internationales** : Gestion UE/hors UE
- **Transmission périodique** : Selon votre régime fiscal
- **Validation** : Contrôles avant envoi au PPF

### 📈 Analyses et rapports
- Tableaux de bord personnalisables
- Analyses de performance
- Exports comptables
- Rapports de conformité

## 🔧 Commandes utiles

```bash
# Gestion de l'environnement
make start          # Démarre les services
make stop           # Arrête les services
make restart        # Redémarre les services
make logs           # Affiche les logs

# Base de données
make migrate        # Exécute les migrations
make seed           # Charge les données de test
make fresh          # Recrée la base complète

# Tests et qualité
make test           # Exécute tous les tests
make lint           # Vérifie le style de code
make analyze        # Analyse statique (PHPStan)

# Production
make prod-build     # Construit l'image de production
make deploy         # Déploie en production

# Facturation électronique
make ppf-connect    # Teste la connexion au PPF
make pdp-setup      # Configure une PDP
make invoice-test   # Teste la génération de factures

# Aide
make help           # Affiche toutes les commandes
```

## 🧪 Tests

Le projet utilise une approche de test complète :

```bash
# Tous les tests
make test

# Tests par type
make test-unit       # Tests unitaires
make test-feature    # Tests fonctionnels
make test-coverage   # Avec couverture de code
```

### Structure des tests

```
tests/
├── Unit/           # Tests unitaires
│   ├── Models/
│   ├── Services/
│   └── Utils/
├── Feature/        # Tests fonctionnels
│   ├── Auth/
│   ├── Invoice/
│   ├── Client/
│   └── Reporting/
└── Browser/        # Tests E2E (Dusk)
```

## 🔐 Sécurité

### Authentification
- Authentification multi-facteur (MFA)
- Gestion des sessions sécurisées
- Politique de mots de passe robuste

### Protection des données
- Chiffrement des données sensibles
- Conformité RGPD complète
- Isolation multi-tenant
- Audit logs complets

### Vérifications de sécurité
```bash
make security-check  # Audit des dépendances
make permissions-fix # Correction des permissions
```

## 📚 Documentation

### API
La documentation de l'API est générée automatiquement avec Swagger/OpenAPI :

```bash
make docs-build     # Génère la documentation
make docs-serve     # Lance le serveur de documentation
```

Accès : [http://localhost:8000/docs](http://localhost:8000/docs)

### Structure du projet

```
app/
├── Console/           # Commandes Artisan
├── Http/
│   ├── Controllers/   # Contrôleurs
│   ├── Middleware/    # Middlewares
│   └── Requests/      # Form Requests
├── Models/            # Modèles Eloquent
├── Services/          # Services métier
│   ├── Invoice/       # Gestion facturation
│   ├── PPF/          # Intégration PPF
│   ├── PDP/          # Intégration PDP
│   └── Reporting/     # E-reporting
├── Jobs/              # Jobs en arrière-plan
├── Events/            # Événements
└── Listeners/         # Écouteurs d'événements
```

## 🌍 Environnements

### Développement
```bash
make dev            # Environnement complet
```
- Hot reload activé
- Debug mode
- Base de données locale
- Mailpit pour les emails

### Test
```bash
make test           # Environnement de test
```
- Base PostgreSQL dédiée
- Cache Redis isolé
- Mocks des services externes

### Production
```bash
make prod-deploy    # Déploiement production
```
- Optimisations activées
- Cache préchargé
- Monitoring intégré

## 🔄 CI/CD

Le projet utilise GitHub Actions pour :

### Tests automatiques
- Tests PHP (unitaires, fonctionnels)
- Analyse statique (PHPStan)
- Vérification du style (PHP CS Fixer)
- Tests de sécurité

### Déploiement
- Build automatique sur `main`
- Tests de performance
- Déploiement sécurisé

Configuration dans `.github/workflows/`

## 📋 Roadmap

### Phase 1 : MVP ✅
- [x] Gestion clients/produits
- [x] Devis et factures
- [x] Formats électroniques de base

### Phase 2 : Conformité 🚧
- [x] Intégration PPF
- [x] Connexion PDP
- [x] E-reporting B2C
- [ ] Tests de conformité complets

### Phase 3 : Premium 📅
- [ ] Paiements en ligne
- [ ] API complète
- [ ] Multi-entreprise
- [ ] Intégrations avancées

### Phase 4 : Scale 🔮
- [ ] Mobile (PWA)
- [ ] Multi-langue
- [ ] Intelligence artificielle
- [ ] Marketplace d'intégrations

## 🤝 Contribution

### Développement local

1. **Fork** le repository
2. **Clone** votre fork localement
3. **Créer** une branche feature : `git checkout -b feature/ma-feature`
4. **Commiter** vos changements : `git commit -am 'Ajoute ma feature'`
5. **Pusher** vers la branche : `git push origin feature/ma-feature`
6. **Créer** une Pull Request

### Standards de code

```bash
make lint           # Vérification style
make analyze        # Analyse statique
make test           # Tests complets
```

## 📞 Support

### Documentation
- [Wiki du projet](https://github.com/votre-username/facturx/wiki)
- [FAQ](https://github.com/votre-username/facturx/wiki/FAQ)
- [Guides utilisateur](https://docs.facturx.com)

### Communauté
- [Discussions GitHub](https://github.com/votre-username/facturx/discussions)
- [Issues](https://github.com/votre-username/facturx/issues)
- [Discord](https://discord.gg/facturx)

### Commercial
- Email : support@facturx.com
- Téléphone : +33 (0)1 XX XX XX XX

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🙏 Remerciements

- [Laravel](https://laravel.com) - Framework PHP
- [Vue.js](https://vuejs.org) - Framework JavaScript
- [Communauté française](https://laravel.fr) - Support et conseils
- [DGFIP](https://www.impots.gouv.fr) - Documentation réglementaire

---

**FacturX** - Simplifions la facturation électronique pour tous 🚀

Made with ❤️ in France 🇫🇷
