#!/bin/bash

# Script pour assembler la collection Postman à partir des fichiers .part
# Usage: bash assemble_postman_collection.sh

# Répertoire contenant les fichiers .part
PARTS_DIR="postman_collection"
OUTPUT_FILE="FacturX_API_Tests.postman_collection.json"

echo "🔧 Assemblage de la collection Postman FacturX..."

# Vérifier que le répertoire existe
if [ ! -d "$PARTS_DIR" ]; then
    echo "❌ Erreur: Le répertoire $PARTS_DIR n'existe pas"
    exit 1
fi

# Supprimer le fichier de sortie s'il existe déjà
if [ -f "$OUTPUT_FILE" ]; then
    rm "$OUTPUT_FILE"
    echo "🗑️  Suppression de l'ancien fichier $OUTPUT_FILE"
fi

# Assembler les fichiers dans l'ordre
echo "📦 Assemblage des parties..."

# Header
cat "$PARTS_DIR/01_header.part" >> "$OUTPUT_FILE"

# Tous les modules (en gardant la virgule entre chaque)
cat "$PARTS_DIR/02_auth.part" >> "$OUTPUT_FILE"
cat "$PARTS_DIR/03_company.part" >> "$OUTPUT_FILE"
cat "$PARTS_DIR/04_clients.part" >> "$OUTPUT_FILE"
cat "$PARTS_DIR/05_products.part" >> "$OUTPUT_FILE"
cat "$PARTS_DIR/06_quotes.part" >> "$OUTPUT_FILE"
cat "$PARTS_DIR/07_invoices.part" >> "$OUTPUT_FILE"
cat "$PARTS_DIR/08_payments.part" >> "$OUTPUT_FILE"
cat "$PARTS_DIR/09_e_reporting.part" >> "$OUTPUT_FILE"
cat "$PARTS_DIR/10_analytics.part" >> "$OUTPUT_FILE"
cat "$PARTS_DIR/11_security_tests.part" >> "$OUTPUT_FILE"

# Footer (ferme le JSON)
cat "$PARTS_DIR/12_footer.part" >> "$OUTPUT_FILE"

echo "✅ Collection assemblée avec succès dans $OUTPUT_FILE"

# Vérifier la validité du JSON
if command -v jq &> /dev/null; then
    echo "🔍 Vérification de la validité JSON..."
    if jq empty "$OUTPUT_FILE" 2>/dev/null; then
        echo "✅ Le fichier JSON est valide"
    else
        echo "❌ Le fichier JSON contient des erreurs"
        exit 1
    fi
else
    echo "⚠️  jq n'est pas installé, impossible de vérifier la validité JSON"
fi

# Afficher les statistiques
if command -v jq &> /dev/null; then
    TOTAL_REQUESTS=$(jq '[.. | objects | select(has("request"))] | length' "$OUTPUT_FILE")
    TOTAL_FOLDERS=$(jq '[.item[] | select(has("item"))] | length' "$OUTPUT_FILE")
    echo "📊 Statistiques:"
    echo "   - Dossiers: $TOTAL_FOLDERS"
    echo "   - Requêtes: $TOTAL_REQUESTS"
fi

echo ""
echo "🎉 Collection Postman FacturX prête à être importée dans Postman !"
echo "📁 Fichier: $OUTPUT_FILE"
echo ""
echo "📋 Instructions pour utiliser la collection:"
echo "1. Ouvrir Postman"
echo "2. Cliquer sur 'Import' dans la barre de navigation"
echo "3. Sélectionner le fichier $OUTPUT_FILE"
echo "4. Configurer les variables d'environnement si nécessaire"
echo "5. Lancer la collection ou des requêtes individuelles"
echo ""
echo "⚙️  Variables importantes à configurer:"
echo "   - base_url: http://localhost:8000/api/v1 (par défaut)"
echo "   - auth_token: sera automatiquement défini après login"
echo ""
echo "🧪 Pour tester l'API complète:"
echo "1. Commencer par 'Register' ou 'Login' dans le dossier 'Authentification'"
echo "2. Exécuter les tests dans l'ordre des dossiers"
echo "3. Les variables (IDs) seront automatiquement définies par les tests"