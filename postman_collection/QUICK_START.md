# 🚀 Quick Start - Collection Postman FacturX

## Assemblage et import en 3 étapes

### 1. Assembler la collection

**Windows (PowerShell) :**
```powershell
.\assemble_postman_collection.ps1
```

**Linux/Mac (Bash) :**
```bash
bash assemble_postman_collection.sh
```

### 2. Importer dans Postman

1. Ouvrir Postman
2. Cliquer sur **"Import"**
3. Sélectionner `FacturX_API_Tests.postman_collection.json`
4. Importer également les environnements :
   - `FacturX-Development.postman_environment.json`
   - `FacturX-Production.postman_environment.json`

### 3. Première utilisation

1. **Sélectionner l'environnement** "FacturX Development"
2. **Tester la connexion** avec "Health Check"
3. **S'authentifier** avec "Register" ou "Login"
4. **Créer des données de test** dans l'ordre :
   - Client
   - Produit
   - Devis
   - Facture

## 🎯 Tests essentiels à exécuter

### Flux complet de test :
```
1. Authentification > Register
2. Company Management > Get Company Details
3. Client Management > Create Client
4. Product Management > Create Product
5. Quote Management > Create Quote
6. Quote Management > Convert Quote to Invoice
7. Payment Management > Create Payment
8. Analytics & Reports > Get Dashboard Stats
```

## 🔧 Variables importantes

Ces variables sont automatiquement définies par les tests :
- `auth_token` - Token d'authentification
- `company_id` - ID de l'entreprise
- `client_id` - ID du client créé
- `product_id` - ID du produit créé
- `quote_id` - ID du devis créé
- `invoice_id` - ID de la facture créée

## 📊 Statistiques de la collection

- **11 dossiers** de tests
- **80+ requêtes** avec tests automatisés
- **Tests de sécurité** inclus
- **Tests d'intégration** PPF/PDP
- **Validation** de la facturation électronique

## 🆘 En cas de problème

1. Vérifier que le serveur Laravel est démarré
2. Contrôler l'URL dans les variables d'environnement
3. S'assurer que la base de données est accessible
4. Consulter la console Postman pour les erreurs

---
✅ **Collection prête à l'emploi pour tester l'API FacturX !**