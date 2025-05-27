#!/bin/bash

# Script de vérification post-installation FacturX
# Vérifie que tous les composants sont correctement configurés

set -e

echo "🔍 Vérification post-installation FacturX"
echo "========================================"

# Variables
NAMESPACE=${1:-facturx}
ENVIRONMENT=${2:-production}

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonctions utilitaires
check_success() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
    else
        echo -e "${RED}❌ $1${NC}"
        exit 1
    fi
}

check_warning() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
    else
        echo -e "${YELLOW}⚠️  $1${NC}"
    fi
}

echo -e "${BLUE}🔧 Vérification de l'environnement Kubernetes${NC}"

# Vérifier que kubectl est installé et configuré
kubectl version --client > /dev/null 2>&1
check_success "kubectl est installé et configuré"

# Vérifier l'accès au cluster
kubectl cluster-info > /dev/null 2>&1
check_success "Accès au cluster Kubernetes"

# Vérifier que le namespace existe
kubectl get namespace $NAMESPACE > /dev/null 2>&1
check_success "Namespace '$NAMESPACE' existe"

echo ""
echo -e "${BLUE}🐳 Vérification des déploiements${NC}"

# Vérifier que tous les déploiements sont prêts
DEPLOYMENTS=("facturx-app" "facturx-nginx" "facturx-queue-worker")

for deployment in "${DEPLOYMENTS[@]}"; do
    kubectl get deployment $deployment -n $NAMESPACE > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        READY=$(kubectl get deployment $deployment -n $NAMESPACE -o jsonpath='{.status.readyReplicas}')
        DESIRED=$(kubectl get deployment $deployment -n $NAMESPACE -o jsonpath='{.spec.replicas}')
        
        if [ "$READY" = "$DESIRED" ]; then
            echo -e "${GREEN}✅ Déploiement $deployment ($READY/$DESIRED pods prêts)${NC}"
        else
            echo -e "${RED}❌ Déploiement $deployment ($READY/$DESIRED pods prêts)${NC}"
        fi
    else
        echo -e "${RED}❌ Déploiement $deployment non trouvé${NC}"
    fi
done

echo ""
echo -e "${BLUE}🔌 Vérification des services${NC}"

# Vérifier les services
SERVICES=("facturx-app-service" "facturx-nginx-service")

for service in "${SERVICES[@]}"; do
    kubectl get service $service -n $NAMESPACE > /dev/null 2>&1
    check_success "Service $service existe"
done

echo ""
echo -e "${BLUE}🌐 Vérification de l'Ingress${NC}"

# Vérifier l'ingress
kubectl get ingress facturx-ingress -n $NAMESPACE > /dev/null 2>&1
check_success "Ingress facturx-ingress existe"

# Vérifier que l'adresse IP est assignée
INGRESS_IP=$(kubectl get ingress facturx-ingress -n $NAMESPACE -o jsonpath='{.status.loadBalancer.ingress[0].ip}')
if [ -n "$INGRESS_IP" ]; then
    echo -e "${GREEN}✅ Ingress a une adresse IP: $INGRESS_IP${NC}"
else
    echo -e "${YELLOW}⚠️  Ingress n'a pas encore d'adresse IP assignée${NC}"
fi

echo ""
echo -e "${BLUE}🔐 Vérification des secrets${NC}"

# Vérifier les secrets
kubectl get secret facturx-secrets -n $NAMESPACE > /dev/null 2>&1
check_success "Secret facturx-secrets existe"

# Vérifier les certificats TLS
kubectl get secret facturx-tls -n $NAMESPACE > /dev/null 2>&1
check_warning "Certificat TLS facturx-tls existe"

echo ""
echo -e "${BLUE}📦 Vérification des ConfigMaps${NC}"

# Vérifier les ConfigMaps
kubectl get configmap facturx-config -n $NAMESPACE > /dev/null 2>&1
check_success "ConfigMap facturx-config existe"

kubectl get configmap nginx-config -n $NAMESPACE > /dev/null 2>&1
check_success "ConfigMap nginx-config existe"

echo ""
echo -e "${BLUE}💾 Vérification du stockage${NC}"

# Vérifier les PVC
kubectl get pvc facturx-storage-pvc -n $NAMESPACE > /dev/null 2>&1
check_success "PVC facturx-storage-pvc existe"

# Vérifier le statut du PVC
PVC_STATUS=$(kubectl get pvc facturx-storage-pvc -n $NAMESPACE -o jsonpath='{.status.phase}')
if [ "$PVC_STATUS" = "Bound" ]; then
    echo -e "${GREEN}✅ PVC est lié (Bound)${NC}"
else
    echo -e "${RED}❌ PVC n'est pas lié (statut: $PVC_STATUS)${NC}"
fi

echo ""
echo -e "${BLUE}🗄️  Vérification des bases de données${NC}"

# Tester la connexion à PostgreSQL
echo "Test de connexion à PostgreSQL..."
kubectl exec -it deployment/facturx-app -n $NAMESPACE -- php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" > /dev/null 2>&1
check_success "Connexion PostgreSQL"

# Tester la connexion à Redis
echo "Test de connexion à Redis..."
kubectl exec -it deployment/facturx-app -n $NAMESPACE -- php artisan tinker --execute="Cache::store('redis')->put('test', 'ok'); echo Cache::store('redis')->get('test');" > /dev/null 2>&1
check_success "Connexion Redis"

echo ""
echo -e "${BLUE}🚀 Vérification de l'application${NC}"

# Vérifier que l'application répond
APP_POD=$(kubectl get pods -n $NAMESPACE -l app=facturx-app -o jsonpath='{.items[0].metadata.name}')
if [ -n "$APP_POD" ]; then
    echo "Test du health check de l'application..."
    kubectl exec $APP_POD -n $NAMESPACE -- curl -f http://localhost/health > /dev/null 2>&1
    check_success "Health check de l'application"
    
    echo "Vérification des migrations..."
    kubectl exec $APP_POD -n $NAMESPACE -- php artisan migrate:status > /dev/null 2>&1
    check_success "État des migrations"
    
    echo "Vérification de la configuration..."
    kubectl exec $APP_POD -n $NAMESPACE -- php artisan config:show > /dev/null 2>&1
    check_success "Configuration de l'application"
else
    echo -e "${RED}❌ Aucun pod d'application trouvé${NC}"
fi

echo ""
echo -e "${BLUE}⚙️  Vérification des workers${NC}"

# Vérifier les workers de queue
WORKER_POD=$(kubectl get pods -n $NAMESPACE -l app=facturx-queue-worker -o jsonpath='{.items[0].metadata.name}')
if [ -n "$WORKER_POD" ]; then
    echo "Vérification du statut des workers..."
    kubectl exec $WORKER_POD -n $NAMESPACE -- php artisan queue:monitor > /dev/null 2>&1
    check_warning "Workers de queue actifs"
else
    echo -e "${YELLOW}⚠️  Aucun worker de queue trouvé${NC}"
fi

echo ""
echo -e "${BLUE}📈 Vérification du scaling${NC}"

# Vérifier l'HPA
kubectl get hpa facturx-app-hpa -n $NAMESPACE > /dev/null 2>&1
check_warning "HPA (Horizontal Pod Autoscaler) configuré"

if [ $? -eq 0 ]; then
    HPA_STATUS=$(kubectl get hpa facturx-app-hpa -n $NAMESPACE -o jsonpath='{.status.conditions[0].type}')
    if [ "$HPA_STATUS" = "ScalingActive" ]; then
        echo -e "${GREEN}✅ HPA est actif${NC}"
    else
        echo -e "${YELLOW}⚠️  HPA n'est pas encore actif${NC}"
    fi
fi

echo ""
echo -e "${BLUE}🔒 Vérification de la sécurité${NC}"

# Vérifier les NetworkPolicies
kubectl get networkpolicy facturx-network-policy -n $NAMESPACE > /dev/null 2>&1
check_warning "NetworkPolicy configurée"

# Vérifier les SecurityContexts
POD_SECURITY=$(kubectl get pods -n $NAMESPACE -l app=facturx-app -o jsonpath='{.items[0].spec.securityContext.runAsNonRoot}')
if [ "$POD_SECURITY" = "true" ]; then
    echo -e "${GREEN}✅ SecurityContext configuré (runAsNonRoot)${NC}"
else
    echo -e "${YELLOW}⚠️  SecurityContext pourrait être amélioré${NC}"
fi

echo ""
echo -e "${BLUE}🌍 Test d'accès externe${NC}"

# Obtenir l'URL d'accès
INGRESS_HOST=$(kubectl get ingress facturx-ingress -n $NAMESPACE -o jsonpath='{.spec.rules[0].host}')
if [ -n "$INGRESS_HOST" ]; then
    echo "Test d'accès externe sur https://$INGRESS_HOST..."
    curl -f -s -k "https://$INGRESS_HOST/health" > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Application accessible via https://$INGRESS_HOST${NC}"
    else
        echo -e "${YELLOW}⚠️  Application non accessible via HTTPS (normal si certificats en cours)${NC}"
        
        # Test en HTTP
        curl -f -s "http://$INGRESS_HOST/health" > /dev/null 2>&1
        check_warning "Application accessible via HTTP"
    fi
else
    echo -e "${YELLOW}⚠️  Pas de host configuré dans l'Ingress${NC}"
fi

echo ""
echo -e "${BLUE}📋 Résumé de l'installation${NC}"
echo "================================="
echo -e "Namespace: ${YELLOW}$NAMESPACE${NC}"
echo -e "Environnement: ${YELLOW}$ENVIRONMENT${NC}"

if [ -n "$INGRESS_HOST" ]; then
    echo -e "URL d'accès: ${YELLOW}https://$INGRESS_HOST${NC}"
fi

echo ""
echo -e "${GREEN}🎉 Vérification terminée !${NC}"
echo ""
echo "Prochaines étapes :"
echo "1. Vérifier que les certificats SSL sont bien configurés"
echo "2. Tester les fonctionnalités principales de l'application"
echo "3. Configurer la surveillance et les alertes"
echo "4. Planifier les sauvegardes régulières"
echo ""
echo "Pour plus d'informations, consultez DEPLOYMENT.md"
