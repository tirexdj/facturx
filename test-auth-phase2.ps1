# FacturX - Tests Authentification Phase 2
# Script PowerShell pour Windows

Write-Host "===================================" -ForegroundColor Green
Write-Host "   FacturX - Tests Authentification" -ForegroundColor Green
Write-Host "===================================" -ForegroundColor Green
Write-Host ""

Write-Host "1. Vérification de l'environnement..." -ForegroundColor Yellow
php --version
Write-Host ""

Write-Host "2. Installation des dépendances..." -ForegroundColor Yellow
composer install --no-interaction --prefer-dist --optimize-autoloader
Write-Host ""

Write-Host "3. Génération de la clé d'application..." -ForegroundColor Yellow
php artisan key:generate --ansi
Write-Host ""

Write-Host "4. Exécution des migrations et seeders..." -ForegroundColor Yellow
php artisan migrate:fresh --seed --force
Write-Host ""

Write-Host "5. Configuration de Laravel Sanctum..." -ForegroundColor Yellow
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force
Write-Host ""

Write-Host "6. Nettoyage du cache..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
php artisan route:clear
Write-Host ""

Write-Host "7. Exécution des tests d'authentification..." -ForegroundColor Yellow
Write-Host "==========================================" -ForegroundColor Green
php artisan test tests/Feature/Api/V1/Auth/AuthTest.php --verbose
Write-Host ""

Write-Host "8. Test manuel des endpoints d'authentification..." -ForegroundColor Yellow
Write-Host "==================================================" -ForegroundColor Green
Write-Host ""

Write-Host "Test 1: Health check" -ForegroundColor Cyan
try {
    $response = Invoke-RestMethod -Uri "http://facturx.test/api/health" -Method GET -ContentType "application/json"
    Write-Host "✅ Health check OK: $($response | ConvertTo-Json -Depth 2)" -ForegroundColor Green
} catch {
    Write-Host "❌ Health check failed: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

Write-Host "Test 2: Registration" -ForegroundColor Cyan
$registerBody = @{
    first_name = "John"
    last_name = "Doe"
    email = "john.doe@example.com"
    password = "password123"
    password_confirmation = "password123"
    company_name = "Test Company"
    siren = "123456789"
    siret = "12345678901234"
    job_title = "CEO"
} | ConvertTo-Json

try {
    $registerResponse = Invoke-RestMethod -Uri "http://facturx.test/api/v1/auth/register" -Method POST -Body $registerBody -ContentType "application/json"
    Write-Host "✅ Registration OK" -ForegroundColor Green
    $token = $registerResponse.data.token
    Write-Host "Token: $token" -ForegroundColor Blue
} catch {
    Write-Host "❌ Registration failed: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

Write-Host "Test 3: Login" -ForegroundColor Cyan
$loginBody = @{
    email = "john.doe@example.com"
    password = "password123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "http://facturx.test/api/v1/auth/login" -Method POST -Body $loginBody -ContentType "application/json"
    Write-Host "✅ Login OK" -ForegroundColor Green
    $loginToken = $loginResponse.data.token
    Write-Host "Login Token: $loginToken" -ForegroundColor Blue
} catch {
    Write-Host "❌ Login failed: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

if ($loginToken) {
    Write-Host "Test 4: Get Profile" -ForegroundColor Cyan
    try {
        $headers = @{
            "Authorization" = "Bearer $loginToken"
            "Accept" = "application/json"
        }
        $profileResponse = Invoke-RestMethod -Uri "http://facturx.test/api/v1/auth/me" -Method GET -Headers $headers
        Write-Host "✅ Profile retrieval OK" -ForegroundColor Green
        Write-Host "User: $($profileResponse.data.first_name) $($profileResponse.data.last_name)" -ForegroundColor Blue
        Write-Host "Company: $($profileResponse.data.company.name)" -ForegroundColor Blue
    } catch {
        Write-Host "❌ Profile retrieval failed: $($_.Exception.Message)" -ForegroundColor Red
    }
    Write-Host ""

    Write-Host "Test 5: Logout" -ForegroundColor Cyan
    try {
        $logoutResponse = Invoke-RestMethod -Uri "http://facturx.test/api/v1/auth/logout" -Method POST -Headers $headers
        Write-Host "✅ Logout OK" -ForegroundColor Green
    } catch {
        Write-Host "❌ Logout failed: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "===================================" -ForegroundColor Green
Write-Host "   Tests terminés !" -ForegroundColor Green
Write-Host "===================================" -ForegroundColor Green
Write-Host ""
Write-Host "Pour plus d'informations, consultez PHASE2_VALIDATION.md" -ForegroundColor Yellow
