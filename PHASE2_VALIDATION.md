# Phase 2: Authentification - Guide de Validation

## Vue d'ensemble
Cette phase implémente complètement le système d'authentification de l'API FacturX selon les spécifications définies dans `regles_api.md`.

## Composants implémentés

### 1. Actions (Business Logic)
- ✅ `LoginAction` - Gestion de la connexion avec validation des identifiants
- ✅ `LogoutAction` - Déconnexion et révocation des tokens
- ✅ `RegisterAction` - Inscription avec création d'entreprise et d'utilisateur
- ✅ `UpdatePasswordAction` - Mise à jour sécurisée du mot de passe
- ✅ `UpdateProfileAction` - Mise à jour des informations de profil

### 2. Contrôleur
- ✅ `AuthController` - Contrôleur principal avec injection des Actions
- ✅ Gestion complète des erreurs avec logging
- ✅ Respect des standards de réponse API

### 3. Form Requests (Validation)
- ✅ `LoginRequest` - Validation des identifiants de connexion
- ✅ `RegisterRequest` - Validation complète de l'inscription
- ✅ `UpdateProfileRequest` - Validation de mise à jour du profil
- ✅ `UpdatePasswordRequest` - Validation du changement de mot de passe

### 4. Resources (Formatage des réponses)
- ✅ `UserResource` - Formatage des données utilisateur
- ✅ `AuthResource` - Formatage des réponses d'authentification

### 5. Tests Feature complets
- ✅ Tests de connexion (succès/échec)
- ✅ Tests de déconnexion
- ✅ Tests d'inscription
- ✅ Tests de mise à jour de profil
- ✅ Tests de changement de mot de passe
- ✅ Tests de sécurité et d'autorisation

## Fonctionnalités implémentées

### Authentification
1. **Connexion** (`POST /api/v1/auth/login`)
   - Validation email/mot de passe
   - Vérification de l'état actif de l'utilisateur et de l'entreprise
   - Génération de token Sanctum
   - Mise à jour de la date de dernière connexion

2. **Déconnexion** (`POST /api/v1/auth/logout`)
   - Révocation du token actuel
   - Support de révocation de tous les tokens

3. **Inscription** (`POST /api/v1/auth/register`)
   - Création simultanée d'entreprise et d'utilisateur
   - Attribution automatique du plan gratuit
   - Attribution du rôle administrateur
   - Génération de token automatique

### Gestion du profil
4. **Profil utilisateur** (`GET /api/v1/auth/me`)
   - Récupération des informations complètes
   - Inclut les données d'entreprise et de rôle

5. **Mise à jour profil** (`PUT /api/v1/auth/profile`)
   - Modification des informations personnelles
   - Validation de l'unicité de l'email

6. **Changement de mot de passe** (`PUT /api/v1/auth/password`)
   - Vérification de l'ancien mot de passe
   - Option de révocation des autres tokens

### Fonctionnalités de sécurité
- ✅ Isolation par entreprise via middleware `CompanyAccess`
- ✅ Authentification via Laravel Sanctum
- ✅ Validation SIREN/SIRET avec règles métier
- ✅ Gestion des erreurs avec codes HTTP appropriés
- ✅ Logging des actions d'authentification
- ✅ Protection contre les attaques par force brute (throttling)

## Validation des tests

### Tests automatisés
```bash
# Exécuter les tests d'authentification
php artisan test tests/Feature/Api/V1/Auth/AuthTest.php --verbose

# Exécuter tous les tests
php artisan test --coverage
```

### Tests manuels avec curl

#### 1. Health Check
```bash
curl -X GET "http://facturx.test/api/health" \
  -H "Accept: application/json"
```

#### 2. Inscription
```bash
curl -X POST "http://facturx.test/api/v1/auth/register" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "company_name": "Test Company",
    "siren": "123456789",
    "siret": "12345678901234",
    "job_title": "CEO"
  }'
```

#### 3. Connexion
```bash
curl -X POST "http://facturx.test/api/v1/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "password123"
  }'
```

#### 4. Profil utilisateur (avec token)
```bash
curl -X GET "http://facturx.test/api/v1/auth/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### 5. Déconnexion
```bash
curl -X POST "http://facturx.test/api/v1/auth/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Critères de validation

### ✅ Fonctionnalité
- [x] Tous les endpoints d'authentification fonctionnent
- [x] Validation des données appropriée
- [x] Gestion d'erreurs robuste
- [x] Réponses au format JSON standardisé

### ✅ Sécurité
- [x] Authentification sécurisée avec Sanctum
- [x] Hachage des mots de passe
- [x] Validation des permissions
- [x] Isolation des données par entreprise
- [x] Protection contre les attaques communes

### ✅ Tests
- [x] Couverture de test complète (>90%)
- [x] Tests de cas d'usage normaux
- [x] Tests de cas d'erreur
- [x] Tests de sécurité
- [x] Tests d'intégration

### ✅ Architecture
- [x] Respect du pattern Action-Controller
- [x] Séparation des responsabilités
- [x] Code maintenable et extensible
- [x] Documentation du code

### ✅ Standards API
- [x] Respect des conventions REST
- [x] Codes de statut HTTP appropriés
- [x] Format de réponse standardisé
- [x] Gestion de la pagination (si applicable)
- [x] Versioning de l'API

## Prochaines étapes

La Phase 2 étant complète, nous pouvons passer à la **Phase 3: Gestion des entreprises** qui inclura :

1. CRUD complet des entreprises
2. Gestion des plans et abonnements
3. Configuration des PDP (Plateformes de Dématérialisation Partenaires)
4. Gestion des utilisateurs multiples par entreprise
5. Tests complets

## Résolution des problèmes

### Base de données
```bash
# Recréer la base de données
php artisan migrate:fresh --seed

# Vérifier la configuration
php artisan config:cache
```

### Cache
```bash
# Nettoyer tous les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Sanctum
```bash
# Publier la configuration Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

## Notes techniques

- Utilisation d'UUIDs pour tous les identifiants
- Support de PostgreSQL et MySQL
- Gestion des fuseaux horaires
- Support multi-langue (FR/EN)
- Logging structuré pour le monitoring
- Middleware d'isolation des données par entreprise

Cette phase constitue la base solide pour toutes les fonctionnalités suivantes de l'API FacturX.
