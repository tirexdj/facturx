#!/bin/bash

# Script de test pour la Phase 3 : Gestion des entreprises (Company Management)
# FacturX API V1

# Configuration
BASE_URL="http://localhost:8000/api/v1"
CONTENT_TYPE="Content-Type: application/json"
ACCEPT="Accept: application/json"

# Couleurs pour l'affichage
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables globales
ACCESS_TOKEN=""
COMPANY_ID=""
PLAN_ID=""
TEST_USER_EMAIL="testphase3@facturx.com"
TEST_USER_PASSWORD="TestPassword123!"

echo -e "${BLUE}=== PHASE 3 TESTS: Gestion des entreprises ===${NC}"
echo -e "${BLUE}FacturX API V1 - Company Management${NC}\n"

# Fonction pour afficher les résultats
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ $2${NC}"
    else
        echo -e "${RED}✗ $2${NC}"
        echo -e "${RED}Response: $3${NC}\n"
    fi
}

# Fonction pour extraire des données JSON
extract_json_value() {
    echo $1 | grep -o "\"$2\":\"[^\"]*" | cut -d'"' -f4
}

extract_json_id() {
    echo $1 | grep -o "\"id\":\"[^\"]*" | head -1 | cut -d'"' -f4
}

# 1. Test de santé de l'API
echo -e "${YELLOW}1. Test de santé de l'API${NC}"
HEALTH_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp "${BASE_URL}/../health")
HTTP_CODE="${HEALTH_RESPONSE: -3}"
RESPONSE_BODY=$(cat response.tmp)

if [ "$HTTP_CODE" = "200" ]; then
    print_result 0 "API Health Check"
else
    print_result 1 "API Health Check" "$RESPONSE_BODY"
    exit 1
fi

# 2. Création d'un utilisateur de test
echo -e "\n${YELLOW}2. Création d'un utilisateur de test${NC}"

# D'abord, récupérer la liste des plans disponibles
PLANS_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    "${BASE_URL}/plans")

HTTP_CODE="${PLANS_RESPONSE: -3}"
RESPONSE_BODY=$(cat response.tmp)

if [ "$HTTP_CODE" = "200" ]; then
    print_result 0 "Récupération des plans disponibles"
    # Extraire le premier plan ID
    PLAN_ID=$(echo "$RESPONSE_BODY" | grep -o "\"id\":\"[^\"]*" | head -1 | cut -d'"' -f4)
    echo -e "Plan ID sélectionné: $PLAN_ID"
else
    print_result 1 "Récupération des plans disponibles" "$RESPONSE_BODY"
    # Utiliser un plan par défaut (à adapter selon votre base de données)
    echo -e "${YELLOW}Utilisation d'un plan par défaut...${NC}"
fi

# Créer un utilisateur de test
REGISTER_DATA='{
    "name": "Test User Phase 3",
    "email": "'$TEST_USER_EMAIL'",
    "password": "'$TEST_USER_PASSWORD'",
    "password_confirmation": "'$TEST_USER_PASSWORD'",
    "company_name": "Test Company Phase 3",
    "plan_id": "'$PLAN_ID'"
}'

REGISTER_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -X POST \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -d "$REGISTER_DATA" \
    "${BASE_URL}/auth/register")

HTTP_CODE="${REGISTER_RESPONSE: -3}"
RESPONSE_BODY=$(cat response.tmp)

if [ "$HTTP_CODE" = "201" ]; then
    print_result 0 "Création utilisateur de test"
    ACCESS_TOKEN=$(extract_json_value "$RESPONSE_BODY" "access_token")
    COMPANY_ID=$(echo "$RESPONSE_BODY" | grep -o "\"company_id\":\"[^\"]*" | cut -d'"' -f4)
    echo -e "Token: ${ACCESS_TOKEN:0:20}..."
    echo -e "Company ID: $COMPANY_ID"
else
    print_result 1 "Création utilisateur de test" "$RESPONSE_BODY"
    
    # Tenter une connexion si l'utilisateur existe déjà
    echo -e "${YELLOW}Tentative de connexion avec l'utilisateur existant...${NC}"
    
    LOGIN_DATA='{
        "email": "'$TEST_USER_EMAIL'",
        "password": "'$TEST_USER_PASSWORD'"
    }'
    
    LOGIN_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
        -X POST \
        -H "$CONTENT_TYPE" \
        -H "$ACCEPT" \
        -d "$LOGIN_DATA" \
        "${BASE_URL}/auth/login")
    
    HTTP_CODE="${LOGIN_RESPONSE: -3}"
    RESPONSE_BODY=$(cat response.tmp)
    
    if [ "$HTTP_CODE" = "200" ]; then
        print_result 0 "Connexion utilisateur existant"
        ACCESS_TOKEN=$(extract_json_value "$RESPONSE_BODY" "access_token")
        COMPANY_ID=$(echo "$RESPONSE_BODY" | grep -o "\"company_id\":\"[^\"]*" | cut -d'"' -f4)
        echo -e "Token: ${ACCESS_TOKEN:0:20}..."
        echo -e "Company ID: $COMPANY_ID"
    else
        print_result 1 "Connexion utilisateur" "$RESPONSE_BODY"
        exit 1
    fi
fi

# 3. Tests des Plans
echo -e "\n${YELLOW}3. Tests des Plans${NC}"

# 3.1. Liste des plans
PLANS_LIST_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    "${BASE_URL}/plans")

HTTP_CODE="${PLANS_LIST_RESPONSE: -3}"
RESPONSE_BODY=$(cat response.tmp)

if [ "$HTTP_CODE" = "200" ]; then
    print_result 0 "Liste des plans"
else
    print_result 1 "Liste des plans" "$RESPONSE_BODY"
fi

# 3.2. Détail d'un plan
if [ ! -z "$PLAN_ID" ]; then
    PLAN_DETAIL_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
        -H "$CONTENT_TYPE" \
        -H "$ACCEPT" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        "${BASE_URL}/plans/$PLAN_ID")
    
    HTTP_CODE="${PLAN_DETAIL_RESPONSE: -3}"
    RESPONSE_BODY=$(cat response.tmp)
    
    if [ "$HTTP_CODE" = "200" ]; then
        print_result 0 "Détail du plan"
    else
        print_result 1 "Détail du plan" "$RESPONSE_BODY"
    fi
fi

# 4. Tests de la Company de l'utilisateur
echo -e "\n${YELLOW}4. Tests de la Company (propre entreprise)${NC}"

# 4.1. Affichage de sa propre company
OWN_COMPANY_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    "${BASE_URL}/company")

HTTP_CODE="${OWN_COMPANY_RESPONSE: -3}"
RESPONSE_BODY=$(cat response.tmp)

if [ "$HTTP_CODE" = "200" ]; then
    print_result 0 "Affichage de sa propre company"
else
    print_result 1 "Affichage de sa propre company" "$RESPONSE_BODY"
fi

# 4.2. Mise à jour de sa propre company
UPDATE_OWN_COMPANY_DATA='{
    "name": "Test Company Updated",
    "legal_name": "Test Company SARL Updated",
    "website": "https://updated-test-company.com",
    "address": {
        "line_1": "123 Updated Street",
        "city": "Updated City",
        "postal_code": "75002",
        "country_code": "FR"
    }
}'

UPDATE_OWN_COMPANY_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -X PUT \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -d "$UPDATE_OWN_COMPANY_DATA" \
    "${BASE_URL}/company")

HTTP_CODE="${UPDATE_OWN_COMPANY_RESPONSE: -3}"
RESPONSE_BODY=$(cat response.tmp)

if [ "$HTTP_CODE" = "200" ]; then
    print_result 0 "Mise à jour de sa propre company"
else
    print_result 1 "Mise à jour de sa propre company" "$RESPONSE_BODY"
fi

# 5. Tests des Companies (gestion administrative)
echo -e "\n${YELLOW}5. Tests des Companies (gestion administrative)${NC}"

# 5.1. Liste des companies (devrait échouer si pas d'autorisation admin)
COMPANIES_LIST_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    "${BASE_URL}/companies")

HTTP_CODE="${COMPANIES_LIST_RESPONSE: -3}"
RESPONSE_BODY=$(cat response.tmp)

if [ "$HTTP_CODE" = "200" ]; then
    print_result 0 "Liste des companies (admin)"
elif [ "$HTTP_CODE" = "403" ]; then
    print_result 0 "Liste des companies refusée (normal, pas d'autorisation admin)"
else
    print_result 1 "Liste des companies" "$RESPONSE_BODY"
fi

# 5.2. Création d'une nouvelle company (devrait échouer si pas d'autorisation admin)
CREATE_COMPANY_DATA='{
    "name": "New Test Company",
    "legal_name": "New Test Company SARL",
    "siren": "123456789",
    "siret": "12345678901234",
    "plan_id": "'$PLAN_ID'",
    "address": {
        "line_1": "456 New Street",
        "city": "New City",
        "postal_code": "75003",
        "country_code": "FR"
    }
}'

CREATE_COMPANY_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -X POST \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -d "$CREATE_COMPANY_DATA" \
    "${BASE_URL}/companies")

HTTP_CODE="${CREATE_COMPANY_RESPONSE: -3}"
RESPONSE_BODY=$(cat response.tmp)

if [ "$HTTP_CODE" = "201" ]; then
    print_result 0 "Création d'une nouvelle company (admin)"
    NEW_COMPANY_ID=$(extract_json_id "$RESPONSE_BODY")
    echo -e "Nouvelle Company ID: $NEW_COMPANY_ID"
elif [ "$HTTP_CODE" = "403" ]; then
    print_result 0 "Création de company refusée (normal, pas d'autorisation admin)"
else
    print_result 1 "Création d'une nouvelle company" "$RESPONSE_BODY"
fi

# 6. Tests de validation
echo -e "\n${YELLOW}6. Tests de validation${NC}"

# 6.1. Création de company avec données invalides
INVALID_COMPANY_DATA='{
    "name": "",
    "siren": "123",
    "siret": "invalid",
    "plan_id": "invalid-uuid",
    "website": "not-a-url"
}'

INVALID_COMPANY_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -X POST \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -d "$INVALID_COMPANY_DATA" \
    "${BASE_URL}/companies")

HTTP_CODE="${INVALID_COMPANY_RESPONSE: -3}"
RESPONSE_BODY=$(cat response.tmp)

if [ "$HTTP_CODE" = "422" ]; then
    print_result 0 "Validation des données invalides (erreur 422 attendue)"
elif [ "$HTTP_CODE" = "403" ]; then
    print_result 0 "Validation refusée par autorisation (normal)"
else
    print_result 1 "Test de validation" "$RESPONSE_BODY"
fi

# 6.2. Mise à jour avec données invalides
INVALID_UPDATE_DATA='{
    "website": "not-a-url",
    "siren": "123"
}'

INVALID_UPDATE_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -X PUT \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -d "$INVALID_UPDATE_DATA" \
    "${BASE_URL}/company")

HTTP_CODE="${INVALID_UPDATE_RESPONSE: -3}"
RESPONSE_BODY=$(cat response.tmp)

if [ "$HTTP_CODE" = "422" ]; then
    print_result 0 "Validation de mise à jour invalide (erreur 422 attendue)"
else
    print_result 1 "Test de validation mise à jour" "$RESPONSE_BODY"
fi

# 7. Tests d'autorisation
echo -e "\n${YELLOW}7. Tests d'autorisation${NC}"

# 7.1. Accès sans token
NO_AUTH_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    "${BASE_URL}/company")

HTTP_CODE="${NO_AUTH_RESPONSE: -3}"

if [ "$HTTP_CODE" = "401" ]; then
    print_result 0 "Accès sans authentification refusé (401 attendu)"
else
    print_result 1 "Test d'accès sans authentification" "Code: $HTTP_CODE"
fi

# 7.2. Accès avec token invalide
INVALID_TOKEN_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -H "Authorization: Bearer invalid-token" \
    "${BASE_URL}/company")

HTTP_CODE="${INVALID_TOKEN_RESPONSE: -3}"

if [ "$HTTP_CODE" = "401" ]; then
    print_result 0 "Accès avec token invalide refusé (401 attendu)"
else
    print_result 1 "Test d'accès avec token invalide" "Code: $HTTP_CODE"
fi

# 8. Tests de filtrage et recherche
echo -e "\n${YELLOW}8. Tests de filtrage et recherche${NC}"

# 8.1. Filtrage des plans par statut public
PUBLIC_PLANS_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    "${BASE_URL}/plans?is_public=true")

HTTP_CODE="${PUBLIC_PLANS_RESPONSE: -3}"
RESPONSE_BODY=$(cat response.tmp)

if [ "$HTTP_CODE" = "200" ]; then
    print_result 0 "Filtrage des plans publics"
else
    print_result 1 "Filtrage des plans publics" "$RESPONSE_BODY"
fi

# 8.2. Récupération du plan avec statistiques
if [ ! -z "$PLAN_ID" ]; then
    PLAN_STATS_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
        -H "$CONTENT_TYPE" \
        -H "$ACCEPT" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        "${BASE_URL}/plans/$PLAN_ID?include_stats=1")
    
    HTTP_CODE="${PLAN_STATS_RESPONSE: -3}"
    RESPONSE_BODY=$(cat response.tmp)
    
    if [ "$HTTP_CODE" = "200" ]; then
        print_result 0 "Plan avec statistiques"
    else
        print_result 1 "Plan avec statistiques" "$RESPONSE_BODY"
    fi
fi

# 9. Nettoyage
echo -e "\n${YELLOW}9. Nettoyage${NC}"

# Déconnexion
LOGOUT_RESPONSE=$(curl -s -w "%{http_code}" -o response.tmp \
    -X POST \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    "${BASE_URL}/auth/logout")

HTTP_CODE="${LOGOUT_RESPONSE: -3}"

if [ "$HTTP_CODE" = "200" ]; then
    print_result 0 "Déconnexion"
else
    print_result 1 "Déconnexion" "Code: $HTTP_CODE"
fi

# Nettoyage des fichiers temporaires
rm -f response.tmp

echo -e "\n${BLUE}=== FIN DES TESTS PHASE 3 ===${NC}"
echo -e "${GREEN}Tous les tests de la Phase 3 (Gestion des entreprises) sont terminés !${NC}"
echo -e "${YELLOW}Vérifiez les résultats ci-dessus pour identifier les éventuels problèmes.${NC}\n"
