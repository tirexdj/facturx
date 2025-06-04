# Script PowerShell pour assembler la collection Postman à partir des fichiers .part
# Usage: .\assemble_postman_collection.ps1

# Configuration
$PartsDir = "postman_collection"
$OutputFile = "FacturX_API_Tests.postman_collection.json"

Write-Host "🔧 Assemblage de la collection Postman FacturX..." -ForegroundColor Cyan

# Vérifier que le répertoire existe
if (-not (Test-Path $PartsDir)) {
    Write-Host "❌ Erreur: Le répertoire $PartsDir n'existe pas" -ForegroundColor Red
    exit 1
}

# Supprimer le fichier de sortie s'il existe déjà
if (Test-Path $OutputFile) {
    Remove-Item $OutputFile
    Write-Host "🗑️  Suppression de l'ancien fichier $OutputFile" -ForegroundColor Yellow
}

Write-Host "📦 Assemblage des parties..." -ForegroundColor Green

# Créer le fichier de sortie et assembler les parties
$Content = ""

# Header
$Content += Get-Content "$PartsDir\01_header.part" -Raw

# Tous les modules
$Content += Get-Content "$PartsDir\02_auth.part" -Raw
$Content += Get-Content "$PartsDir\03_company.part" -Raw
$Content += Get-Content "$PartsDir\04_clients.part" -Raw
$Content += Get-Content "$PartsDir\05_products.part" -Raw
$Content += Get-Content "$PartsDir\06_quotes.part" -Raw
$Content += Get-Content "$PartsDir\07_invoices.part" -Raw
$Content += Get-Content "$PartsDir\08_payments.part" -Raw
$Content += Get-Content "$PartsDir\09_e_reporting.part" -Raw
$Content += Get-Content "$PartsDir\10_analytics.part" -Raw
$Content += Get-Content "$PartsDir\11_security_tests.part" -Raw

# Footer
$Content += Get-Content "$PartsDir\12_footer.part" -Raw

# Écrire le contenu final
$Content | Out-File -FilePath $OutputFile -Encoding UTF8

Write-Host "✅ Collection assemblée avec succès dans $OutputFile" -ForegroundColor Green

# Vérifier la validité du JSON si possible
try {
    $JsonContent = Get-Content $OutputFile -Raw | ConvertFrom-Json
    Write-Host "✅ Le fichier JSON est valide" -ForegroundColor Green
    
    # Afficher les statistiques
    $TotalFolders = $JsonContent.item.Count
    $TotalRequests = 0
    foreach ($folder in $JsonContent.item) {
        if ($folder.item) {
            $TotalRequests += $folder.item.Count
        }
    }
    
    Write-Host "📊 Statistiques:" -ForegroundColor Cyan
    Write-Host "   - Dossiers: $TotalFolders" -ForegroundColor White
    Write-Host "   - Requêtes: $TotalRequests" -ForegroundColor White
}
catch {
    Write-Host "❌ Le fichier JSON contient des erreurs: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "🎉 Collection Postman FacturX prête à être importée dans Postman !" -ForegroundColor Green
Write-Host "📁 Fichier: $OutputFile" -ForegroundColor White
Write-Host ""
Write-Host "📋 Instructions pour utiliser la collection:" -ForegroundColor Cyan
Write-Host "1. Ouvrir Postman" -ForegroundColor White
Write-Host "2. Cliquer sur 'Import' dans la barre de navigation" -ForegroundColor White
Write-Host "3. Sélectionner le fichier $OutputFile" -ForegroundColor White
Write-Host "4. Configurer les variables d'environnement si nécessaire" -ForegroundColor White
Write-Host "5. Lancer la collection ou des requêtes individuelles" -ForegroundColor White
Write-Host ""
Write-Host "⚙️  Variables importantes à configurer:" -ForegroundColor Cyan
Write-Host "   - base_url: http://localhost:8000/api/v1 (par défaut)" -ForegroundColor White
Write-Host "   - auth_token: sera automatiquement défini après login" -ForegroundColor White
Write-Host ""
Write-Host "🧪 Pour tester l'API complète:" -ForegroundColor Cyan
Write-Host "1. Commencer par 'Register' ou 'Login' dans le dossier 'Authentification'" -ForegroundColor White
Write-Host "2. Exécuter les tests dans l'ordre des dossiers" -ForegroundColor White
Write-Host "3. Les variables (IDs) seront automatiquement définies par les tests" -ForegroundColor White

# Pause pour permettre à l'utilisateur de lire
Write-Host ""
Write-Host "Appuyez sur une touche pour continuer..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")