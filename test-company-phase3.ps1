# Script de test pour la Phase 3 : Gestion des entreprises (Company Management)
# FacturX API V1 - Version PowerShell

# Configuration
$BaseUrl = "http://localhost:8000/api/v1"
$ContentType = "application/json"
$Accept = "application/json"

# Variables globales
$AccessToken = ""
$CompanyId = ""
$PlanId = ""
$TestUserEmail = "testphase3@facturx.com"
$TestUserPassword = "TestPassword123!"

Write-Host "=== PHASE 3 TESTS: Gestion des entreprises ===" -ForegroundColor Blue
Write-Host "FacturX API V1 - Company Management" -ForegroundColor Blue
Write-Host ""

# Fonction pour afficher les résultats
function Print-Result {
    param(
        [bool]$Success,
        [string]$Message,
        [string]$Response = ""
    )
    
    if ($Success) {
        Write-Host "✓ $Message" -ForegroundColor Green
    } else {
        Write-Host "✗ $Message" -ForegroundColor Red
        if ($Response) {
            Write-Host "Response: $Response" -ForegroundColor Red
        }
        Write-Host ""
    }
}

# Fonction pour extraire des valeurs JSON
function Extract-JsonValue {
    param(
        [string]$Json,
        [string]$Key
    )
    
    try {
        $JsonObject = $Json | ConvertFrom-Json
        return $JsonObject.$Key
    } catch {
        return $null
    }
}

# Fonction pour faire une requête HTTP
function Invoke-ApiRequest {
    param(
        [string]$Method = "GET",
        [string]$Url,
        [hashtable]$Headers = @{},
        [string]$Body = $null
    )
    
    try {
        $DefaultHeaders = @{
            "Content-Type" = $ContentType
            "Accept" = $Accept
        }
        
        $AllHeaders = $DefaultHeaders + $Headers
        
        $params = @{
            Uri = $Url
            Method = $Method
            Headers = $AllHeaders
            UseBasicParsing = $true
        }
        
        if ($Body -and $Method -ne "GET") {
            $params.Body = $Body
        }
        
        $response = Invoke-WebRequest @params
        return @{
            StatusCode = $response.StatusCode
            Content = $response.Content
            Success = $true
        }
    } catch {
        return @{
            StatusCode = $_.Exception.Response.StatusCode.Value__
            Content = $_.Exception.Response
            Success = $false
            Error = $_.Exception.Message
        }
    }
}

# 1. Test de santé de l'API
Write-Host "1. Test de santé de l'API" -ForegroundColor Yellow

$healthResponse = Invoke-ApiRequest -Url "$($BaseUrl)/../health"

if ($healthResponse.Success -and $healthResponse.StatusCode -eq 200) {
    Print-Result -Success $true -Message "API Health Check"
} else {
    Print-Result -Success $false -Message "API Health Check" -Response $healthResponse.Content
    exit 1
}

# 2. Création d'un utilisateur de test
Write-Host ""
Write-Host "2. Création d'un utilisateur de test" -ForegroundColor Yellow

# D'abord, récupérer la liste des plans disponibles
$plansResponse = Invoke-ApiRequest -Url "$BaseUrl/plans"

if ($plansResponse.Success -and $plansResponse.StatusCode -eq 200) {
    Print-Result -Success $true -Message "Récupération des plans disponibles"
    try {
        $plansData = $plansResponse.Content | ConvertFrom-Json
        if ($plansData.data.data -and $plansData.data.data.Count -gt 0) {
            $PlanId = $plansData.data.data[0].id
            Write-Host "Plan ID sélectionné: $PlanId" -ForegroundColor Cyan
        }
    } catch {
        Write-Host "Erreur lors de l'extraction du plan ID" -ForegroundColor Yellow
    }
} else {
    Print-Result -Success $false -Message "Récupération des plans disponibles" -Response $plansResponse.Content
    Write-Host "Utilisation d'un plan par défaut..." -ForegroundColor Yellow
}

# Créer un utilisateur de test
$registerData = @{
    name = "Test User Phase 3"
    email = $TestUserEmail
    password = $TestUserPassword
    password_confirmation = $TestUserPassword
    company_name = "Test Company Phase 3"
    plan_id = $PlanId
} | ConvertTo-Json

$registerResponse = Invoke-ApiRequest -Method "POST" -Url "$BaseUrl/auth/register" -Body $registerData

if ($registerResponse.Success -and $registerResponse.StatusCode -eq 201) {
    Print-Result -Success $true -Message "Création utilisateur de test"
    try {
        $userData = $registerResponse.Content | ConvertFrom-Json
        $AccessToken = $userData.data.access_token
        $CompanyId = $userData.data.user.company_id
        Write-Host "Token: $($AccessToken.Substring(0, 20))..." -ForegroundColor Cyan
        Write-Host "Company ID: $CompanyId" -ForegroundColor Cyan
    } catch {
        Write-Host "Erreur lors de l'extraction des données utilisateur" -ForegroundColor Red
    }
} else {
    Print-Result -Success $false -Message "Création utilisateur de test" -Response $registerResponse.Content
    
    # Tenter une connexion si l'utilisateur existe déjà
    Write-Host "Tentative de connexion avec l'utilisateur existant..." -ForegroundColor Yellow
    
    $loginData = @{
        email = $TestUserEmail
        password = $TestUserPassword
    } | ConvertTo-Json
    
    $loginResponse = Invoke-ApiRequest -Method "POST" -Url "$BaseUrl/auth/login" -Body $loginData
    
    if ($loginResponse.Success -and $loginResponse.StatusCode -eq 200) {
        Print-Result -Success $true -Message "Connexion utilisateur existant"
        try {
            $userData = $loginResponse.Content | ConvertFrom-Json
            $AccessToken = $userData.data.access_token
            $CompanyId = $userData.data.user.company_id
            Write-Host "Token: $($AccessToken.Substring(0, 20))..." -ForegroundColor Cyan
            Write-Host "Company ID: $CompanyId" -ForegroundColor Cyan
        } catch {
            Write-Host "Erreur lors de l'extraction des données utilisateur" -ForegroundColor Red
        }
    } else {
        Print-Result -Success $false -Message "Connexion utilisateur" -Response $loginResponse.Content
        exit 1
    }
}

# 3. Tests des Plans
Write-Host ""
Write-Host "3. Tests des Plans" -ForegroundColor Yellow

# 3.1. Liste des plans
$authHeaders = @{ "Authorization" = "Bearer $AccessToken" }
$plansListResponse = Invoke-ApiRequest -Url "$BaseUrl/plans" -Headers $authHeaders

if ($plansListResponse.Success -and $plansListResponse.StatusCode -eq 200) {
    Print-Result -Success $true -Message "Liste des plans"
} else {
    Print-Result -Success $false -Message "Liste des plans" -Response $plansListResponse.Content
}

# 3.2. Détail d'un plan
if ($PlanId) {
    $planDetailResponse = Invoke-ApiRequest -Url "$BaseUrl/plans/$PlanId" -Headers $authHeaders
    
    if ($planDetailResponse.Success -and $planDetailResponse.StatusCode -eq 200) {
        Print-Result -Success $true -Message "Détail du plan"
    } else {
        Print-Result -Success $false -Message "Détail du plan" -Response $planDetailResponse.Content
    }
}

# 4. Tests de la Company de l'utilisateur
Write-Host ""
Write-Host "4. Tests de la Company (propre entreprise)" -ForegroundColor Yellow

# 4.1. Affichage de sa propre company
$ownCompanyResponse = Invoke-ApiRequest -Url "$BaseUrl/company" -Headers $authHeaders

if ($ownCompanyResponse.Success -and $ownCompanyResponse.StatusCode -eq 200) {
    Print-Result -Success $true -Message "Affichage de sa propre company"
} else {
    Print-Result -Success $false -Message "Affichage de sa propre company" -Response $ownCompanyResponse.Content
}

# 4.2. Mise à jour de sa propre company
$updateOwnCompanyData = @{
    name = "Test Company Updated"
    legal_name = "Test Company SARL Updated"
    website = "https://updated-test-company.com"
    address = @{
        line_1 = "123 Updated Street"
        city = "Updated City"
        postal_code = "75002"
        country_code = "FR"
    }
} | ConvertTo-Json -Depth 3

$updateOwnCompanyResponse = Invoke-ApiRequest -Method "PUT" -Url "$BaseUrl/company" -Headers $authHeaders -Body $updateOwnCompanyData

if ($updateOwnCompanyResponse.Success -and $updateOwnCompanyResponse.StatusCode -eq 200) {
    Print-Result -Success $true -Message "Mise à jour de sa propre company"
} else {
    Print-Result -Success $false -Message "Mise à jour de sa propre company" -Response $updateOwnCompanyResponse.Content
}

# 5. Tests des Companies (gestion administrative)
Write-Host ""
Write-Host "5. Tests des Companies (gestion administrative)" -ForegroundColor Yellow

# 5.1. Liste des companies
$companiesListResponse = Invoke-ApiRequest -Url "$BaseUrl/companies" -Headers $authHeaders

if ($companiesListResponse.Success -and $companiesListResponse.StatusCode -eq 200) {
    Print-Result -Success $true -Message "Liste des companies (admin)"
} elseif ($companiesListResponse.StatusCode -eq 403) {
    Print-Result -Success $true -Message "Liste des companies refusée (normal, pas d'autorisation admin)"
} else {
    Print-Result -Success $false -Message "Liste des companies" -Response $companiesListResponse.Content
}

# 5.2. Création d'une nouvelle company
$createCompanyData = @{
    name = "New Test Company"
    legal_name = "New Test Company SARL"
    siren = "123456789"
    siret = "12345678901234"
    plan_id = $PlanId
    address = @{
        line_1 = "456 New Street"
        city = "New City"
        postal_code = "75003"
        country_code = "FR"
    }
} | ConvertTo-Json -Depth 3

$createCompanyResponse = Invoke-ApiRequest -Method "POST" -Url "$BaseUrl/companies" -Headers $authHeaders -Body $createCompanyData

if ($createCompanyResponse.Success -and $createCompanyResponse.StatusCode -eq 201) {
    Print-Result -Success $true -Message "Création d'une nouvelle company (admin)"
    try {
        $newCompanyData = $createCompanyResponse.Content | ConvertFrom-Json
        $NewCompanyId = $newCompanyData.data.id
        Write-Host "Nouvelle Company ID: $NewCompanyId" -ForegroundColor Cyan
    } catch {
        Write-Host "Erreur lors de l'extraction de l'ID de la nouvelle company" -ForegroundColor Yellow
    }
} elseif ($createCompanyResponse.StatusCode -eq 403) {
    Print-Result -Success $true -Message "Création de company refusée (normal, pas d'autorisation admin)"
} else {
    Print-Result -Success $false -Message "Création d'une nouvelle company" -Response $createCompanyResponse.Content
}

# 6. Tests de validation
Write-Host ""
Write-Host "6. Tests de validation" -ForegroundColor Yellow

# 6.1. Création de company avec données invalides
$invalidCompanyData = @{
    name = ""
    siren = "123"
    siret = "invalid"
    plan_id = "invalid-uuid"
    website = "not-a-url"
} | ConvertTo-Json

$invalidCompanyResponse = Invoke-ApiRequest -Method "POST" -Url "$BaseUrl/companies" -Headers $authHeaders -Body $invalidCompanyData

if ($invalidCompanyResponse.StatusCode -eq 422) {
    Print-Result -Success $true -Message "Validation des données invalides (erreur 422 attendue)"
} elseif ($invalidCompanyResponse.StatusCode -eq 403) {
    Print-Result -Success $true -Message "Validation refusée par autorisation (normal)"
} else {
    Print-Result -Success $false -Message "Test de validation" -Response $invalidCompanyResponse.Content
}

# 6.2. Mise à jour avec données invalides
$invalidUpdateData = @{
    website = "not-a-url"
    siren = "123"
} | ConvertTo-Json

$invalidUpdateResponse = Invoke-ApiRequest -Method "PUT" -Url "$BaseUrl/company" -Headers $authHeaders -Body $invalidUpdateData

if ($invalidUpdateResponse.StatusCode -eq 422) {
    Print-Result -Success $true -Message "Validation de mise à jour invalide (erreur 422 attendue)"
} else {
    Print-Result -Success $false -Message "Test de validation mise à jour" -Response $invalidUpdateResponse.Content
}

# 7. Tests d'autorisation
Write-Host ""
Write-Host "7. Tests d'autorisation" -ForegroundColor Yellow

# 7.1. Accès sans token
$noAuthResponse = Invoke-ApiRequest -Url "$BaseUrl/company"

if ($noAuthResponse.StatusCode -eq 401) {
    Print-Result -Success $true -Message "Accès sans authentification refusé (401 attendu)"
} else {
    Print-Result -Success $false -Message "Test d'accès sans authentification" -Response "Code: $($noAuthResponse.StatusCode)"
}

# 7.2. Accès avec token invalide
$invalidTokenHeaders = @{ "Authorization" = "Bearer invalid-token" }
$invalidTokenResponse = Invoke-ApiRequest -Url "$BaseUrl/company" -Headers $invalidTokenHeaders

if ($invalidTokenResponse.StatusCode -eq 401) {
    Print-Result -Success $true -Message "Accès avec token invalide refusé (401 attendu)"
} else {
    Print-Result -Success $false -Message "Test d'accès avec token invalide" -Response "Code: $($invalidTokenResponse.StatusCode)"
}

# 8. Tests de filtrage et recherche
Write-Host ""
Write-Host "8. Tests de filtrage et recherche" -ForegroundColor Yellow

# 8.1. Filtrage des plans par statut public
$publicPlansResponse = Invoke-ApiRequest -Url "$BaseUrl/plans?is_public=true" -Headers $authHeaders

if ($publicPlansResponse.Success -and $publicPlansResponse.StatusCode -eq 200) {
    Print-Result -Success $true -Message "Filtrage des plans publics"
} else {
    Print-Result -Success $false -Message "Filtrage des plans publics" -Response $publicPlansResponse.Content
}

# 8.2. Récupération du plan avec statistiques
if ($PlanId) {
    $planStatsResponse = Invoke-ApiRequest -Url "$BaseUrl/plans/$PlanId?include_stats=1" -Headers $authHeaders
    
    if ($planStatsResponse.Success -and $planStatsResponse.StatusCode -eq 200) {
        Print-Result -Success $true -Message "Plan avec statistiques"
    } else {
        Print-Result -Success $false -Message "Plan avec statistiques" -Response $planStatsResponse.Content
    }
}

# 9. Nettoyage
Write-Host ""
Write-Host "9. Nettoyage" -ForegroundColor Yellow

# Déconnexion
$logoutResponse = Invoke-ApiRequest -Method "POST" -Url "$BaseUrl/auth/logout" -Headers $authHeaders

if ($logoutResponse.Success -and $logoutResponse.StatusCode -eq 200) {
    Print-Result -Success $true -Message "Déconnexion"
} else {
    Print-Result -Success $false -Message "Déconnexion" -Response "Code: $($logoutResponse.StatusCode)"
}

Write-Host ""
Write-Host "=== FIN DES TESTS PHASE 3 ===" -ForegroundColor Blue
Write-Host "Tous les tests de la Phase 3 (Gestion des entreprises) sont terminés !" -ForegroundColor Green
Write-Host "Vérifiez les résultats ci-dessus pour identifier les éventuels problèmes." -ForegroundColor Yellow
Write-Host ""
