# Architecture de FacturX

Ce projet suit une architecture orientée domaine (Domain-Driven Design) avec une structure organisée autour des domaines métier.

## Structure des Dossiers

```
app/
├── Console/              # Commandes Artisan
├── Domain/               # Cœur métier organisé par domaines fonctionnels
│   ├── Analytics/        # Domaine d'analyse et statistiques
│   │   └── Models/       # Modèles du domaine Analytics (Feature, FeatureUsage, ActivityLog, etc.)
│   ├── Auth/             # Domaine d'authentification
│   │   └── Models/       # Modèles du domaine Auth (User, Role, Permission)
│   ├── Company/          # Domaine entreprise
│   │   └── Models/       # Modèles du domaine Company (Company, Plan)
│   ├── Customer/         # Domaine client
│   │   └── Models/       # Modèles du domaine Customer (Client, Category)
│   ├── EReporting/       # Domaine e-reporting (facturation électronique)
│   │   └── Models/       # Modèles du domaine EReporting (EReportingTransmission, PdpConfiguration)
│   ├── Invoice/          # Domaine facture
│   │   └── Models/       # Modèles du domaine Invoice (Invoice, InvoiceLine, PaymentTerm, etc.)
│   ├── Payment/          # Domaine paiement
│   │   └── Models/       # Modèles du domaine Payment (Payment)
│   ├── Product/          # Domaine produit
│   │   └── Models/       # Modèles du domaine Product (Product, Service, Unit, VatRate, etc.)
│   ├── Quote/            # Domaine devis
│   │   └── Models/       # Modèles du domaine Quote (Quote, QuoteLine)
│   └── Shared/           # Modèles et services partagés entre domaines
│       └── Models/       # Modèles partagés (Address, PhoneNumber, Email, Contact)
│
├── Exceptions/           # Gestionnaires d'exceptions
├── Http/                 # Contrôleurs, Middleware, Requests, Resources
│   ├── Controllers/      # Contrôleurs HTTP
│   ├── Middleware/       # Middleware
│   ├── Requests/         # Validation des requêtes
│   └── Resources/        # Transformateurs de ressources API
│
├── Models/               # Anciens modèles (migration en cours vers Domain)
│
└── Providers/            # Fournisseurs de services
```

## Principes Architecturaux

### 1. Séparation par Domaines Métier

L'application est divisée en domaines métier distincts, chacun représentant un aspect spécifique de l'entreprise. Cette organisation facilite la compréhension du code et la collaboration entre les équipes.

### 2. Modèles Riches

Nos modèles ne sont pas de simples structures de données. Ils encapsulent le comportement et les règles métier, assurant ainsi l'intégrité des données et la logique métier.

### 3. Utilisation des Traits Laravel

Les modèles utilisent des traits Laravel pour étendre leurs fonctionnalités :
- `HasFactory` pour la génération de données de test
- `HasUuids` pour utiliser des identifiants UUID au lieu d'auto-increment
- `SoftDeletes` pour la suppression logique des enregistrements
- `InteractsWithMedia` pour la gestion des médias (via Spatie Media Library)

### 4. Relations Explicites

Les relations entre modèles sont clairement définies et documentées via les méthodes de relation d'Eloquent :
- `BelongsTo`
- `HasMany`
- `BelongsToMany`
- `MorphMany`
- `MorphTo`

### 5. Scopes et Accesseurs

Les modèles utilisent des scopes pour encapsuler des requêtes courantes et des accesseurs pour dériver des propriétés calculées.

## Domaines Principaux

### Auth
Gestion des utilisateurs, authentification, rôles et permissions.

### Company
Gestion des entreprises cliente et des plans d'abonnement.

### Customer
Gestion des clients, contacts et catégories.

### Product
Gestion des produits, services, unités, taux de TVA et attributs.

### Quote
Gestion des devis et de leurs lignes.

### Invoice
Gestion des factures, lignes, conditions de paiement et récurrences.

### Payment
Gestion des paiements et méthodes de paiement.

### EReporting
Gestion de la facturation électronique et des transmissions réglementaires.

### Analytics
Suivi de l'utilisation des fonctionnalités et journalisation des activités.

### Shared
Modèles partagés entre plusieurs domaines (adresses, emails, téléphones, contacts).

## Gestion des Médias

FacturX utilise la bibliothèque [Spatie MediaLibrary](https://github.com/spatie/laravel-medialibrary) pour gérer les fichiers et médias associés aux modèles :

- Images de produits et services
- Logos d'entreprises et clients
- Documents attachés aux factures et devis
- PDFs générés
- Factures électroniques

Les modèles qui gèrent des médias implémentent l'interface `HasMedia` et utilisent le trait `InteractsWithMedia`.

## Flux d'une Requête Typique

1. Une requête HTTP atteint un contrôleur (`Http/Controllers/InvoiceController`)
2. Le contrôleur valide la requête à l'aide d'une classe Request (`Http/Requests/StoreInvoiceRequest`)
3. Le contrôleur interagit avec les modèles du domaine pour effectuer les opérations nécessaires
4. Les événements appropriés sont déclenchés
5. Le contrôleur retourne une réponse, souvent en utilisant une Resource (`Http/Resources/InvoiceResource`)

## Tests

- Tests unitaires pour tester des fonctionnalités spécifiques
- Tests de fonctionnalités pour tester des flux complets
- Tests API pour tester les endpoints d'API
- Factories pour générer des données de test réalistes
