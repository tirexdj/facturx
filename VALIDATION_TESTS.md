# Validation des Corrections - Guide de Test

## Commandes de Test

### 1. Tests spécifiques aux corrections

```bash
# Test du health endpoint qui avait l'erreur de parsing de date
php artisan test tests/Feature/Api/V1/ApiInfrastructureTest.php::test_health_check_endpoint_works

# Test d'authentification qui avait les annotations deprecated
php artisan test tests/Feature/Api/V1/Auth/AuthTest.php::test_user_can_login_with_valid_credentials
```

### 2. Tests complets

```bash
# Tous les tests d'authentification
php artisan test tests/Feature/Api/V1/Auth/AuthTest.php --verbose

# Tous les tests d'infrastructure
php artisan test tests/Feature/Api/V1/ApiInfrastructureTest.php --verbose

# Tous les tests
php artisan test --stop-on-failure
```

### 3. Vérification que les factories fonctionnent

```bash
# Tester la création d'une entreprise via la factory
php artisan tinker
> \App\Domain\Company\Models\Company::factory()->create()
> exit
```

### 4. Test des endpoints en live

```bash
# Health check
curl http://facturx.test/api/health

# Test d'inscription (vérifie que les factories fonctionnent)
curl -X POST http://facturx.test/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "first_name": "Test",
    "last_name": "User", 
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "company_name": "Test Company"
  }'
```

## Résultats attendus

### ✅ Tests qui devraient maintenant passer
- `ApiInfrastructureTest::test_health_check_endpoint_works` - Plus d'erreur "01-01"
- `AuthTest::test_user_can_login_with_valid_credentials` - Plus d'avertissement PHPUnit
- Tous les autres tests d'authentification

### ✅ Réponses attendues

**Health endpoint (/api/health) :**
```json
{
  "status": "ok",
  "timestamp": "2025-01-XX:XX:XX.XXXZ",
  "version": "1.0.0"
}
```

**Inscription réussie (/api/v1/auth/register) :**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": "...",
      "first_name": "Test",
      "last_name": "User",
      "email": "test@example.com",
      "company": {...}
    },
    "token": "...",
    "token_type": "Bearer",
    "expires_at": "..."
  }
}
```

## Si les tests échouent encore

### 1. Nettoyer complètement

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

### 2. Recréer la base de données

```bash
php artisan migrate:fresh --seed --env=testing
```

### 3. Vérifier la configuration

```bash
# Vérifier que la base de données de test existe
php artisan migrate:status --env=testing

# Vérifier la configuration de l'app
php artisan config:show app
```

### 4. Debug avancé

```bash
# Lancer les tests avec debug
php artisan test --verbose --debug

# Lancer un test spécifique avec output détaillé
php artisan test tests/Feature/Api/V1/ApiInfrastructureTest.php::test_health_check_endpoint_works --verbose --stop-on-failure
```

---

## Résumé des corrections

| ❌ Problème Original | ✅ Solution Appliquée |
|---------------------|---------------------|
| `WARN Metadata found in doc-comment...` | Remplacement des `/** @test */` par `#[Test]` |
| `Could not parse '01-01'...` | Remplacement de `'01-01'` par `now()->startOfYear()` |

Les corrections sont minimales et ciblées, ne touchant que les erreurs techniques sans affecter la logique métier.
