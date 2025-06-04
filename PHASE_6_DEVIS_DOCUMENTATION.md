# Phase 6 - Module Gestion des Devis - Documentation

## 🎯 Vue d'ensemble

La phase 6 implémente un système complet de gestion des devis avec :
- ✅ Système PDF personnalisable et réutilisable
- ✅ Enums typés pour les statuts de devis
- ✅ Gestion rigoureuse des changements de statut avec événements
- ✅ Support des devis payants et bons de commande
- ✅ Mentions légales obligatoires automatiques
- ✅ Templates PDF entièrement personnalisables

## 🏗️ Architecture

### Enums créés
```php
// Statuts des devis avec transitions contrôlées
App\Domain\Shared\Enums\QuoteStatus

// Types de documents
App\Domain\Shared\Enums\DocumentType
```

### Services principaux
```php
// Génération PDF avec templates personnalisables
App\Domain\Shared\Services\Pdf\PdfGeneratorService

// Gestion des templates PDF
App\Domain\Shared\Services\Pdf\PdfTemplateService

// Logic métier des devis
App\Domain\Quote\Services\QuoteService
```

### Événements et Listeners
```php
// Événements
App\Domain\Quote\Events\QuoteCreated
App\Domain\Quote\Events\QuoteStatusChanged
App\Domain\Quote\Events\QuoteSent
App\Domain\Quote\Events\QuoteAccepted

// Listeners
App\Domain\Quote\Listeners\HandleQuoteCreated
App\Domain\Quote\Listeners\HandleQuoteStatusChange
```

## 🔧 Utilisation

### 1. Création d'un devis

```php
use App\Domain\Quote\Services\QuoteService;

$quoteService = app(QuoteService::class);

$quote = $quoteService->createQuote([
    'company_id' => $company->id,
    'client_id' => $client->id,
    'title' => 'Prestation de conseil',
    'is_billable' => true,
    'deposit_percentage' => 30,
    'lines' => [
        [
            'title' => 'Consultation stratégique',
            'description' => 'Analyse et recommandations',
            'quantity' => 5,
            'unit_price_net' => 150.00,
            'vat_rate_id' => $vatRate->id,
        ]
    ]
]);
```

### 2. Gestion des statuts

```php
// Changement de statut avec validation automatique
$quote->updateStatus(QuoteStatus::SENT, 'Envoyé au client par email');

// Vérifications disponibles
$quote->canBeEdited();      // true si DRAFT
$quote->canBeSent();        // true si DRAFT ou SENT
$quote->canBeConverted();   // true si ACCEPTED
```

### 3. Génération PDF

```php
use App\Domain\Shared\Services\Pdf\PdfGeneratorService;

$pdfGenerator = app(PdfGeneratorService::class);

// Génération PDF standard
$pdfPath = $pdfGenerator->generateQuotePdf($quote);

// Génération avec options personnalisées
$pdfPath = $pdfGenerator->generateQuotePdf($quote, [
    'template' => 'modern',
    'margins' => [
        'top' => 25,
        'bottom' => 25,
        'left' => 20,
        'right' => 20,
    ]
]);

// Aperçu sans sauvegarde
$pdfContent = $pdfGenerator->generatePreview($quote);
```

### 4. Personalisation des templates

```php
use App\Domain\Shared\Services\Pdf\PdfTemplateService;

$templateService = app(PdfTemplateService::class);

// Sauvegarder une configuration personnalisée
$templateService->saveTemplateConfig($company, DocumentType::QUOTE, [
    'colors' => [
        'primary' => '#FF6B35',
        'secondary' => '#4A5568',
    ],
    'header' => [
        'show_logo' => true,
        'height' => 100,
        'custom_content' => 'Votre expert en solutions digitales',
    ],
    'body' => [
        'font_size' => 11,
        'table_style' => 'zebra',
        'show_descriptions' => true,
    ]
]);

// Récupérer la configuration
$config = $templateService->getTemplateConfig($company, DocumentType::QUOTE);
```

### 5. Conversion en facture

```php
// Conversion automatique d'un devis accepté
if ($quote->canBeConverted()) {
    $invoice = $quoteService->convertToInvoice($quote);
}
```

### 6. Expiration automatique

```php
// Via le service
$expiredCount = $quoteService->expireQuotes();

// Via la commande Artisan
php artisan quotes:expire

// Dry run pour voir ce qui serait expiré
php artisan quotes:expire --dry-run

// Pour une entreprise spécifique
php artisan quotes:expire --company=123
```

## 📋 Nouveaux champs du modèle Quote

```php
// Champs ajoutés dans la migration
'consultation_token',      // Token sécurisé pour consultation
'is_purchase_order',       // Bon de commande ou devis
'is_billable',            // Devis payant
'deposit_percentage',      // Pourcentage d'acompte
'deposit_amount',         // Montant d'acompte calculé
'payment_terms',          // Conditions de paiement
'template_name',          // Template PDF utilisé
'template_config',        // Configuration JSON du template
'legal_mentions',         // Mentions légales JSON
'internal_notes',         // Notes internes
'public_notes',           // Notes publiques
```

## 🎨 Templates PDF

### Templates disponibles
- **Modern** : Design épuré avec couleurs vives
- **Classic** : Style traditionnel professionnel
- **Minimal** : Design minimaliste
- **Corporate** : Style entreprise formel
- **Creative** : Design créatif avec éléments graphiques

### Personnalisation

Les templates sont entièrement personnalisables :

#### En-tête
- Affichage/masquage du logo
- Informations société et contact
- Contenu personnalisé
- Hauteur et couleur de fond

#### Corps
- Police et taille
- Style de tableau (bordures, zébré, minimal)
- Affichage des images produits
- Affichage des descriptions

#### Pied de page
- Numérotation des pages
- Informations société
- Contenu personnalisé

#### Couleurs
- Couleur principale (titres, bordures)
- Couleur secondaire (texte léger)
- Couleur d'accent (totaux, highlights)
- Couleur du texte
- Couleur de fond

#### Mise en page
- Marges personnalisables
- Format papier (A4, A3, Letter, Legal)
- Orientation (portrait, paysage)

## 📜 Mentions légales automatiques

Le système génère automatiquement les mentions légales obligatoires :

### Pour les devis
- Durée de validité
- Conditions d'acceptation
- Conditions de paiement
- Mentions d'acompte si applicable
- Mentions TVA selon le régime

### Pour les bons de commande
- Engagement d'achat
- Conditions de livraison
- Modalités de commande

### Mentions communes
- Informations société (SIRET, RCS, APE, TVA)
- Capital social
- Forme juridique
- Assurance professionnelle si applicable
- Mentions profession réglementée si applicable

## 🔄 Gestion des événements

### Événements déclenchés

1. **QuoteCreated** : À la création
   - Calcul automatique des totaux
   - Application des paramètres par défaut
   - Génération PDF initiale

2. **QuoteStatusChanged** : À chaque changement de statut
   - Validation des transitions
   - Historisation des changements
   - Régénération PDF si nécessaire
   - Actions spécifiques selon le statut

3. **QuoteSent** : À l'envoi
   - Préparation pour l'email
   - Mise à jour du statut
   - Génération du lien de consultation

4. **QuoteAccepted** : À l'acceptation
   - Sauvegarde de la signature
   - Préparation conversion facture

### Listeners disponibles

Les listeners effectuent automatiquement :
- Génération/régénération des PDFs
- Calcul des totaux
- Application des paramètres d'entreprise
- Logging des actions importantes
- Notifications (à implémenter)

## 📊 Statistiques et métriques

```php
// Obtenir les statistiques des devis
$stats = $quoteService->getQuoteStatistics($companyId, [
    'start' => '2024-01-01',
    'end' => '2024-12-31'
]);

// Retourne :
[
    'total_quotes' => 150,
    'total_amount' => 125000.00,
    'average_amount' => 833.33,
    'by_status' => [
        'draft' => 12,
        'sent' => 25,
        'accepted' => 78,
        'rejected' => 18,
        'expired' => 15,
        'cancelled' => 2
    ],
    'conversion_rate' => 65.0,
    'average_processing_time' => 5.2 // jours
]
```

## 🔧 Configuration

### Fichier config/pdf.php

Configuration centralisée pour :
- Formats de papier supportés
- Templates disponibles
- Options DomPDF
- Chemins de stockage
- Styles de tableau
- Polices disponibles
- Mentions légales par défaut
- Paramètres de qualité et sécurité

### Variables d'environnement

```env
# PDF
PDF_DEFAULT_TEMPLATE=modern
PDF_STORAGE_DISK=local
PDF_CACHE_ENABLED=true
PDF_QUALITY_DPI=150

# Devis
QUOTE_DEFAULT_VALIDITY_DAYS=30
QUOTE_AUTO_EXPIRE=true
QUOTE_BILLABLE_BY_DEFAULT=false
```

## 🧪 Tests

```bash
# Tests des devis
php artisan test tests/Feature/Quote/

# Tests du système PDF
php artisan test tests/Feature/Pdf/

# Tests des événements
php artisan test tests/Feature/Quote/Events/
```

## 📚 Commandes disponibles

```bash
# Expirer les devis
php artisan quotes:expire

# Dry run (aperçu)
php artisan quotes:expire --dry-run

# Pour une entreprise
php artisan quotes:expire --company=123

# Statistiques des devis
php artisan quotes:stats

# Nettoyage des PDFs anciens
php artisan pdf:cleanup --older-than=30
```

## 🔄 Tâches planifiées

Ajouter dans `app/Console/Kernel.php` :

```php
protected function schedule(Schedule $schedule)
{
    // Expirer les devis tous les jours à 1h du matin
    $schedule->job(ExpireQuotesJob::class)
             ->dailyAt('01:00')
             ->name('expire-quotes')
             ->emailOutputOnFailure('admin@example.com');
    
    // Nettoyage des PDFs anciens une fois par semaine
    $schedule->command('pdf:cleanup --older-than=90')
             ->weekly()
             ->sundays()
             ->at('02:00');
}
```

## 🚀 Prochaines étapes

1. **API REST** : Création des endpoints pour les devis
2. **Interface utilisateur** : Vue de gestion des devis
3. **Emails** : Templates d'envoi de devis
4. **Signatures électroniques** : Intégration DocuSign/Adobe Sign
5. **Workflows** : Validation multi-niveaux
6. **Rapports** : Tableaux de bord analytics
7. **Intégrations** : CRM, comptabilité

Cette implémentation fournit une base solide et extensible pour la gestion complète des devis avec un système PDF professionnel et personnalisable.
