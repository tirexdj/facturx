#!/bin/bash

echo "🚀 Lancement des tests pour la Phase 5 : Catalogue produits/services"
echo "=================================================================="

# Lancer les tests spécifiques à la Phase 5
echo "📝 Tests des Produits..."
php artisan test tests/Feature/Api/V1/Product/ProductTest.php --verbose

echo ""
echo "🔧 Tests des Services..."
php artisan test tests/Feature/Api/V1/Product/ServiceTest.php --verbose

echo ""
echo "📂 Tests des Catégories..."
php artisan test tests/Feature/Api/V1/Product/CategoryTest.php --verbose

echo ""
echo "✅ Tous les tests de la Phase 5 terminés !"
