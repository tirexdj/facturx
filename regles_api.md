# Règles et Structure de l'API FacturX

## Vue d'ensemble
Cette API REST suit les standards Laravel avec une architecture propre, versionnée et testée. Elle implémente le pattern Action-Controller pour une séparation claire des responsabilités.

## Architecture générale

### Structure des dossiers
```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           ├── Auth/
│   │           │   ├── AuthController.php
│   │           │   ├── LoginController.php
│   │           │   └── RegisterController.php
│   │           ├── Company/
│   │           │   ├── CompanyController.php
│   │           │   └── PlanController.php
│   │           ├── Customer/
│   │           │   └── ClientController.php
│   │           ├── Product/
│   │           │   ├── ProductController.php
│   │           │   └── ServiceController.php
│   │           ├── Quote/
│   │           │   └── QuoteController.php
│   │           ├── Invoice/
│   │           │   └── InvoiceController.php
│   │           └── Payment/
│   │               └── PaymentController.php
│   ├── Requests/
│   │   └── Api/
│   │       └── V1/
│   │           ├── Auth/
│   │           │   ├── LoginRequest.php
│   │           │   └── RegisterRequest.php
│   │           ├── Company/
│   │           │   ├── StoreCompanyRequest.php
│   │           │   └── UpdateCompanyRequest.php
│   │           ├── Customer/
│   │           │   ├── StoreClientRequest.php
│   │           │   └── UpdateClientRequest.php
│   │           ├── Product/
│   │           │   ├── StoreProductRequest.php
│   │           │   └── UpdateProductRequest.php
│   │           ├── Quote/
│   │           │   ├── StoreQuoteRequest.php
│   │           │   └── UpdateQuoteRequest.php
│   │           ├── Invoice/
│   │           │   ├── StoreInvoiceRequest.php
│   │           │   └── UpdateInvoiceRequest.php
│   │           └── Payment/
│   │               └── StorePaymentRequest.php
│   ├── Resources/
│   │   └── Api/
│   │       └── V1/
│   │           ├── Auth/
│   │           │   └── UserResource.php
│   │           ├── Company/
│   │           │   ├── CompanyResource.php
│   │           │   ├── CompanyCollection.php
│   │           │   └── PlanResource.php
│   │           ├── Customer/
│   │           │   ├── ClientResource.php
│   │           │   └── ClientCollection.php
│   │           ├── Product/
│   │           │   ├── ProductResource.php
│   │           │   ├── ProductCollection.php
│   │           │   └── ServiceResource.php
│   │           ├── Quote/
│   │           │   ├── QuoteResource.php
│   │           │   └── QuoteCollection.php
│   │           ├── Invoice/
│   │           │   ├── InvoiceResource.php
│   │           │   └── InvoiceCollection.php
│   │           └── Payment/
│   │               └── PaymentResource.php
│   └── Middleware/
│       ├── ApiVersioning.php
│       ├── CompanyAccess.php
│       └── PlanLimits.php
├── Actions/
│   └── Api/
│       └── V1/
│           ├── Auth/
│           │   ├── LoginAction.php
│           │   ├── LogoutAction.php
│           │   └── RegisterAction.php
│           ├── Company/
│           │   ├── CreateCompanyAction.php
│           │   ├── UpdateCompanyAction.php
│           │   ├── DeleteCompanyAction.php
│           │   └── GetCompanyAction.php
│           ├── Customer/
│           │   ├── CreateClientAction.php
│           │   ├── UpdateClientAction.php
│           │   ├── DeleteClientAction.php
│           │   └── GetClientAction.php
│           ├── Product/
│           │   ├── CreateProductAction.php
│           │   ├── UpdateProductAction.php
│           │   ├── DeleteProductAction.php
│           │   └── GetProductAction.php
│           ├── Quote/
│           │   ├── CreateQuoteAction.php
│           │   ├── UpdateQuoteAction.php
│           │   ├── DeleteQuoteAction.php
│           │   ├── ConvertQuoteToInvoiceAction.php
│           │   └── SendQuoteAction.php
│           ├── Invoice/
│           │   ├── CreateInvoiceAction.php
│           │   ├── UpdateInvoiceAction.php
│           │   ├── DeleteInvoiceAction.php
│           │   ├── SendInvoiceAction.php
│           │   └── GenerateElectronicInvoiceAction.php
│           └── Payment/
│               ├── CreatePaymentAction.php
│               └── ProcessPaymentAction.php
└── Tests/
    └── Feature/
        └── Api/
            └── V1/
                ├── Auth/
                │   ├── LoginTest.php
                │   └── RegisterTest.php
                ├── Company/
                │   └── CompanyTest.php
                ├── Customer/
                │   └── ClientTest.php
                ├── Product/
                │   └── ProductTest.php
                ├── Quote/
                │   └── QuoteTest.php
                ├── Invoice/
                │   └── InvoiceTest.php
                └── Payment/
                    └── PaymentTest.php
```

## Standards et conventions

### 1. Versioning de l'API
- **URL Structure**: `/api/v1/...`
- **Version actuelle**: v1
- **Middleware de versioning**: `ApiVersioning`
- **Headers requis**: `Accept: application/json` et `Content-Type: application/json`

### 2. Authentification
- **Méthode**: Laravel Sanctum pour l'authentification API
- **Tokens**: Personal Access Tokens pour l'authentification
- **Middleware**: `auth:sanctum` pour les routes protégées

### 3. Structure des Controllers
```php
<?php

namespace App\Http\Controllers\Api\V1\[Domain];

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\[Domain]\[Operation]Request;
use App\Http\Resources\Api\V1\[Domain]\[Resource];
use App\Actions\Api\V1\[Domain]\[Operation]Action;

class [Entity]Controller extends Controller
{
    public function index()
    {
        // Liste paginée des ressources
    }

    public function store([Operation]Request $request)
    {
        // Création d'une nouvelle ressource
    }

    public function show(string $id)
    {
        // Affichage d'une ressource spécifique
    }

    public function update([Operation]Request $request, string $id)
    {
        // Mise à jour d'une ressource
    }

    public function destroy(string $id)
    {
        // Suppression d'une ressource
    }
}
```

### 4. Structure des Actions
```php
<?php

namespace App\Actions\Api\V1\[Domain];

use App\Domain\[Domain]\Models\[Entity];

class [Operation]Action
{
    public function execute(array $data): [Entity]
    {
        // Logique métier
        // Validation des données
        // Création/modification/suppression
        // Retour du résultat
    }
}
```

### 5. Structure des Requests
```php
<?php

namespace App\Http\Requests\Api\V1\[Domain];

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class [Operation]Request extends FormRequest
{
    public function authorize(): bool
    {
        // Logique d'autorisation basée sur l'utilisateur et la company
    }

    public function rules(): array
    {
        // Règles de validation
    }

    public function messages(): array
    {
        // Messages d'erreur personnalisés
    }
}
```

### 6. Structure des Resources
```php
<?php

namespace App\Http\Resources\Api\V1\[Domain];

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class [Entity]Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Autres attributs transformés
        ];
    }
}
```

### 7. Structure des Tests
```php
<?php

namespace Tests\Feature\Api\V1\[Domain];

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Company\Models\Company;
use App\Domain\Auth\Models\User;

class [Entity]Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->for($this->company)->create();
    }

    public function test_can_list_[entities](): void
    {
        // Test de listing
    }

    public function test_can_create_[entity](): void
    {
        // Test de création
    }

    public function test_can_show_[entity](): void
    {
        // Test d'affichage
    }

    public function test_can_update_[entity](): void
    {
        // Test de mise à jour
    }

    public function test_can_delete_[entity](): void
    {
        // Test de suppression
    }

    public function test_unauthorized_access_returns_401(): void
    {
        // Test d'accès non autorisé
    }

    public function test_forbidden_access_returns_403(): void
    {
        // Test d'accès interdit (mauvaise company)
    }
}
```

## Règles de développement

### 1. Gestion des erreurs
- **Codes HTTP standards**: 200, 201, 204, 400, 401, 403, 404, 422, 500
- **Format des erreurs**: JSON avec structure standardisée
- **Validation**: Utilisation des Form Requests Laravel
- **Exceptions**: Handler personnalisé pour l'API

### 2. Pagination
- **Méthode**: `paginate()` de Laravel
- **Paramètres**: `page`, `per_page` (max 100)
- **Métadonnées**: Incluses dans la réponse JSON

### 3. Filtrage et tri
- **Query parameters**: `filter[field]=value`
- **Tri**: `sort=field` ou `sort=-field` (descendant)
- **Recherche**: `search=term`

### 4. Relations
- **Eager loading**: Contrôlé via query parameter `include`
- **Relations disponibles**: Définies dans chaque Resource
- **Optimisation**: N+1 queries évitées

### 5. Sécurité
- **Authorization**: Policies Laravel pour chaque model
- **Company isolation**: Middleware `CompanyAccess`
- **Plan limits**: Middleware `PlanLimits`
- **Rate limiting**: Implémenté pour l'API
- **CORS**: Configuré selon les besoins

### 6. Monitoring
- **Logs**: Structured logging avec contexte
- **Métriques**: Performance et utilisation
- **Health checks**: Endpoint `/api/v1/health`

## Étapes de développement

### Phase 1: Infrastructure de base
1. ✅ Configuration du routing API versionné
2. ✅ Création des middlewares de base
3. ✅ Configuration de l'authentification Sanctum
4. ✅ Handler d'exceptions pour l'API
5. ✅ Structure des tests de base

### Phase 2: Authentification
1. ✅ Endpoint de login/logout
2. ✅ Endpoint de registration
3. ✅ Gestion des tokens
4. ✅ Tests d'authentification

### Phase 3: Gestion des entreprises
1. ✅ CRUD Company
2. ✅ Gestion des plans
3. ✅ Middleware d'isolation par company
4. ✅ Tests Company

### Phase 4: Gestion des clients
1. ✅ CRUD Clients
2. ✅ Relations avec Company
3. ✅ Gestion des adresses
4. ✅ Tests Clients

### Phase 5: Catalogue produits/services
1. ✅ CRUD Produits
2. ✅ CRUD Services
3. ✅ Gestion des catégories
4. ✅ Tests Produits/Services

### Phase 6: Devis
1. ✅ CRUD Devis
2. ✅ Gestion des lignes de devis
3. ✅ Conversion devis → facture
4. ✅ Envoi de devis
5. ✅ Tests Devis

### Phase 7: Factures
1. ✅ CRUD Factures
2. ✅ Génération PDF
3. ✅ Génération formats électroniques
4. ✅ Intégration PDP
5. ✅ Tests Factures

### Phase 8: Paiements
1. ✅ Enregistrement des paiements
2. ✅ Suivi des échéances
3. ✅ Relances automatiques
4. ✅ Tests Paiements

### Phase 9: Rapports et analytics
1. ✅ Tableaux de bord
2. ✅ Exports comptables
3. ✅ Statistiques
4. ✅ Tests Analytics

### Phase 10: Optimisation et documentation
1. ✅ Optimisation des performances
2. ✅ Documentation OpenAPI/Swagger
3. ✅ Tests d'intégration complets
4. ✅ Monitoring et observabilité

## Commandes utiles

### Création d'un nouveau endpoint
```bash
# Controller
php artisan make:controller Api/V1/[Domain]/[Entity]Controller --api

# Request
php artisan make:request Api/V1/[Domain]/Store[Entity]Request
php artisan make:request Api/V1/[Domain]/Update[Entity]Request

# Resource
php artisan make:resource Api/V1/[Domain]/[Entity]Resource
php artisan make:resource Api/V1/[Domain]/[Entity]Collection

# Action (manuel)
# Test
php artisan make:test Api/V1/[Domain]/[Entity]Test

# Policy
php artisan make:policy [Entity]Policy --model=[Entity]
```

### Tests
```bash
# Tous les tests API
php artisan test tests/Feature/Api/

# Tests spécifiques
php artisan test tests/Feature/Api/V1/[Domain]/

# Coverage
php artisan test --coverage
```

Cette structure garantit une API robuste, maintenable et extensible, respectant les meilleures pratiques Laravel et les standards REST.
