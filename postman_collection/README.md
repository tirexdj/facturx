# Collection Postman FacturX API

Cette collection Postman contient tous les tests nécessaires pour valider l'API FacturX, une solution SaaS de gestion commerciale et facturation électronique conforme à la réglementation française.

## 📁 Structure de la Collection

La collection est organisée en 11 modules principaux :

### 1. 🔐 Authentification
- **Register** : Création d'un nouveau compte utilisateur et entreprise
- **Login** : Connexion et récupération du token d'authentification
- **Get User Profile** : Récupération des informations utilisateur
- **Logout** : Déconnexion et invalidation du token

### 2. 🏢 Company Management
- **Get Company Details** : Consultation des informations entreprise
- **Update Company** : Mise à jour des informations entreprise
- **Get Company Plans** : Liste des plans disponibles

### 3. 👥 Client Management
- **Create Client** : Création d'un nouveau client (B2B ou B2C)
- **List Clients** : Liste paginée des clients avec filtres
- **Get Client** : Consultation détaillée d'un client
- **Update Client** : Modification des informations client
- **Delete Client** : Suppression d'un client

### 4. 📦 Product Management
- **Create Product** : Création d'un nouveau produit
- **List Products** : Liste des produits avec filtres
- **Get Product** : Consultation détaillée d'un produit
- **Update Product** : Modification d'un produit
- **Create Service** : Création d'un nouveau service
- **List Services** : Liste des services
- **Delete Product** : Suppression d'un produit

### 5. 📄 Quote Management
- **Create Quote** : Création d'un nouveau devis
- **List Quotes** : Liste des devis avec filtres et tri
- **Get Quote** : Consultation détaillée d'un devis avec relations
- **Update Quote** : Modification d'un devis
- **Send Quote** : Envoi du devis par email
- **Convert Quote to Invoice** : Conversion d'un devis en facture
- **Get Quote PDF** : Téléchargement du PDF du devis
- **Delete Quote** : Suppression d'un devis

### 6. 🧾 Invoice Management
- **Create Invoice** : Création d'une nouvelle facture
- **List Invoices** : Liste des factures avec filtres
- **Get Invoice** : Consultation détaillée d'une facture
- **Update Invoice** : Modification d'une facture
- **Send Invoice** : Envoi de la facture par email
- **Generate Electronic Invoice** : Génération aux formats électroniques (UBL, CII, Factur-X)
- **Get Invoice PDF** : Téléchargement du PDF de la facture
- **Get Outstanding Invoices** : Liste des factures impayées
- **Generate Payment Reminder** : Génération de rappel de paiement

### 7. 💰 Payment Management
- **Create Payment** : Enregistrement d'un paiement
- **List Payments** : Liste des paiements avec filtres
- **Get Payment** : Consultation détaillée d'un paiement
- **Update Payment** : Modification d'un paiement
- **Delete Payment** : Suppression d'un paiement

### 8. 📊 E-reporting
- **Get B2C Transactions** : Récupération des transactions B2C
- **Get International Transactions** : Récupération des transactions internationales
- **Generate TVA Declaration Data** : Génération des données pour déclaration TVA
- **Submit E-reporting Data** : Soumission des données e-reporting
- **Get E-reporting Status** : Consultation du statut des soumissions

### 9. 📈 Analytics & Reports
- **Get Dashboard Stats** : Statistiques du tableau de bord
- **Get Sales Report** : Rapport des ventes
- **Get Quote Conversion Report** : Rapport de conversion des devis
- **Export Accounting Data** : Export des données comptables
- **Get Product Performance** : Performance des produits
- **Get Customer Performance** : Performance des clients

### 10. 🔒 Error Handling & Security Tests
- **Test Unauthorized Access** : Test d'accès non autorisé (401)
- **Test Invalid Data Validation** : Test de validation des données (422)
- **Test Resource Not Found** : Test de ressource non trouvée (404)
- **Test Rate Limiting** : Test de limitation de débit
- **Test SQL Injection Protection** : Test de protection contre l'injection SQL

### 11. 🔗 Integration Tests
- **Test PPF Integration** : Test d'intégration avec le Portail Public de Facturation
- **Test PDP Integration** : Test d'intégration avec les Plateformes de Dématérialisation
- **Health Check** : Vérification de l'état de santé de l'API

## 🚀 Installation et Utilisation

### Prérequis
- Postman installé sur votre machine
- Serveur FacturX en cours d'exécution (par défaut sur `http://localhost:8000`)

### Étapes d'installation

1. **Assembler la collection**
   ```bash
   # Sur Linux/Mac
   bash assemble_postman_collection.sh
   
   # Sur Windows (PowerShell)
   .\assemble_postman_collection.ps1
   ```

2. **Importer dans Postman**
   - Ouvrir Postman
   - Cliquer sur "Import"
   - Sélectionner le fichier `FacturX_API_Tests.postman_collection.json`
   - Confirmer l'importation

3. **Configurer les variables**
   - `base_url` : URL de base de l'API (défaut: `http://localhost:8000/api/v1`)
   - Les autres variables (`auth_token`, `company_id`, etc.) sont automatiquement définies par les tests

### 🎯 Ordre d'exécution recommandé

1. **Démarrer par l'authentification**
   - Exécuter "Register" pour créer un compte de test
   - Ou "Login" si vous avez déjà un compte

2. **Tester les modules de base**
   - Company Management
   - Client Management
   - Product Management

3. **Tester les fonctionnalités métier**
   - Quote Management
   - Invoice Management
   - Payment Management

4. **Tester les fonctionnalités avancées**
   - E-reporting
   - Analytics & Reports

5. **Valider la sécurité**
   - Error Handling & Security Tests
   - Integration Tests

## 🔧 Variables Automatiques

La collection utilise des scripts de test pour automatiquement :
- Définir le token d'authentification après login
- Extraire et stocker les IDs des ressources créées
- Passer les données entre les requêtes
- Valider les réponses de l'API

### Variables principales :
- `auth_token` : Token d'authentification (défini automatiquement)
- `company_id` : ID de l'entreprise (défini automatiquement)
- `user_id` : ID de l'utilisateur (défini automatiquement)
- `client_id` : ID du client créé (défini automatiquement)
- `product_id` : ID du produit créé (défini automatiquement)
- `quote_id` : ID du devis créé (défini automatiquement)
- `invoice_id` : ID de la facture créée (défini automatiquement)

## 📋 Tests Automatisés

Chaque requête inclut des tests automatisés qui vérifient :
- Le code de statut HTTP
- La structure de la réponse JSON
- La présence des champs obligatoires
- La cohérence des données
- Les contraintes métier

## 🛡️ Tests de Sécurité

La collection inclut des tests spécifiques pour :
- Authentification et autorisation
- Validation des données d'entrée
- Protection contre l'injection SQL
- Gestion des erreurs
- Limitation de débit (rate limiting)

## 📊 Conformité Réglementaire

Les tests incluent la validation de :
- Génération des formats électroniques (UBL, CII, Factur-X)
- Transmission vers le Portail Public de Facturation (PPF)
- E-reporting des transactions B2C et internationales
- Archivage légal des factures

## 🔍 Debugging

Pour déboguer les requêtes :
1. Vérifier les variables dans l'onglet "Variables"
2. Consulter la console Postman pour les logs
3. Examiner les réponses dans l'onglet "Response"
4. Utiliser l'onglet "Test Results" pour voir les résultats des tests

## 📝 Customisation

Vous pouvez personnaliser la collection en :
- Modifiant les variables globales
- Ajoutant de nouveaux tests
- Personnalisant les scripts pre-request et test
- Créant des environnements spécifiques

## 🆘 Support

En cas de problème :
1. Vérifiez que le serveur FacturX est démarré
2. Contrôlez les variables d'environnement
3. Consultez les logs du serveur
4. Vérifiez la documentation de l'API

## 📚 Documentation API

Pour plus d'informations sur l'API FacturX, consultez :
- Le fichier `regles_api.md` pour les règles de développement
- La documentation Swagger/OpenAPI (si disponible)
- Les spécifications détaillées dans le cahier des charges

---

**Note** : Cette collection est conçue pour fonctionner avec l'API FacturX en développement. Adaptez les URLs et paramètres selon votre environnement.