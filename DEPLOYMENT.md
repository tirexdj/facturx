# Guide de Déploiement FacturX

Ce guide décrit le processus complet de déploiement de FacturX depuis le développement jusqu'à la production.

## 📋 Prérequis

### Environnement de développement
- Docker & Docker Compose
- Git
- Make (optionnel mais recommandé)
- Node.js 20+ (pour le développement local)
- PHP 8.4+ (pour le développement local)

### Environnement de production
- Kubernetes cluster
- Helm 3+
- Cert-manager (pour les certificats SSL)
- Ingress controller (NGINX recommandé)
- PostgreSQL 16+
- Redis 7+
- S3-compatible storage

## 🚀 Déploiement rapide

### 1. Développement local

```bash
# Clone et installation
git clone https://github.com/votre-username/facturx.git
cd facturx
make install

# Démarrage de l'environnement
make dev
```

L'application sera disponible sur http://localhost:8000

### 2. Tests et validation

```bash
# Tests complets
make test

# Qualité de code
make lint
make analyze

# Tests de sécurité
make security-check
```

### 3. Déploiement en staging

```bash
# Construction et push des images
make prod-build

# Déploiement staging
kubectl apply -f k8s/staging.yaml
```

### 4. Déploiement en production

```bash
# Via Helm (recommandé)
helm upgrade --install facturx ./helm \
  --namespace facturx \
  --create-namespace \
  --values helm/values-production.yaml

# Ou via Kubernetes natif
kubectl apply -f k8s/production.yaml
```

## ⚙️ Configuration détaillée

### Variables d'environnement

#### Base de données
```bash
DB_CONNECTION=pgsql
DB_HOST=postgres-service
DB_PORT=5432
DB_DATABASE=facturx_production
DB_USERNAME=facturx
DB_PASSWORD=${DB_PASSWORD}
```

#### Cache et sessions
```bash
REDIS_HOST=redis-service
REDIS_PORT=6379
REDIS_PASSWORD=${REDIS_PASSWORD}
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

#### Facturation électronique
```bash
PPF_API_URL=https://api.ppf.gouv.fr
PPF_API_KEY=${PPF_API_KEY}
PPF_ENVIRONMENT=production
PDP_TEST_MODE=false
```

#### Stockage S3
```bash
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID}
AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY}
AWS_DEFAULT_REGION=eu-west-3
AWS_BUCKET=${AWS_BUCKET}
```

### Secrets Kubernetes

Créez le secret avec les données sensibles :

```bash
kubectl create secret generic facturx-secrets \
  --namespace=facturx \
  --from-literal=APP_KEY="base64:YOUR_APP_KEY" \
  --from-literal=DB_PASSWORD="your_db_password" \
  --from-literal=REDIS_PASSWORD="your_redis_password" \
  --from-literal=PPF_API_KEY="your_ppf_api_key" \
  --from-literal=AWS_ACCESS_KEY_ID="your_aws_key" \
  --from-literal=AWS_SECRET_ACCESS_KEY="your_aws_secret" \
  --from-literal=STRIPE_SECRET_KEY="your_stripe_key"
```

## 🔄 CI/CD avec GitHub Actions

### Workflow principal

Le fichier `.github/workflows/laravel.yml` gère :
- Tests automatiques (PHP 8.4, PostgreSQL, Redis)
- Analyse statique (PHPStan)
- Vérification du style (PHP CS Fixer)
- Tests de sécurité
- Construction des images Docker
- Déploiement automatique

### Variables d'environnement GitHub

Configurez ces secrets dans GitHub :

```bash
# Base
DB_PASSWORD
REDIS_PASSWORD

# AWS
AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY
AWS_BUCKET

# Facturation électronique
PPF_API_KEY

# Paiements
STRIPE_SECRET_KEY
STRIPE_WEBHOOK_SECRET

# Monitoring
SENTRY_DSN

# Notifications
SLACK_WEBHOOK_URL
DISCORD_WEBHOOK
```

### Déclenchement des déploiements

```bash
# Release automatique
git tag v1.0.0
git push origin v1.0.0

# Déploiement manuel
gh workflow run release.yml -f version=v1.0.0
```

## 🛠️ Commandes utiles

### Développement

```bash
# Démarrage rapide
make dev

# Tests
make test
make test-coverage

# Qualité de code
make lint-fix
make analyze

# Base de données
make fresh
make db-backup
```

### Production

```bash
# Monitoring
kubectl logs -f deployment/facturx-app -n facturx
kubectl get pods -n facturx

# Scaling
kubectl scale deployment facturx-app --replicas=5 -n facturx

# Maintenance
kubectl exec -it deployment/facturx-app -n facturx -- php artisan migrate
kubectl exec -it deployment/facturx-app -n facturx -- php artisan cache:clear
```

### Helm

```bash
# Installation
helm install facturx ./helm -n facturx --create-namespace

# Mise à jour
helm upgrade facturx ./helm -n facturx

# Rollback
helm rollback facturx 1 -n facturx

# Status
helm status facturx -n facturx
```

## 📊 Monitoring et observabilité

### Métriques surveillées

- **Performance** : Temps de réponse, throughput
- **Erreurs** : Taux d'erreur 5xx, exceptions PHP
- **Base de données** : Temps de requête, connexions
- **Queue** : Jobs en attente, jobs échoués
- **Infrastructure** : CPU, mémoire, stockage

### Alertes configurées

- Application indisponible (> 1 minute)
- Taux d'erreur élevé (> 5%)
- Temps de réponse dégradé (> 2 secondes)
- Base de données surchargée
- Stockage presque plein (> 85%)

### Logs

```bash
# Application
kubectl logs -f deployment/facturx-app -n facturx

# Workers
kubectl logs -f deployment/facturx-queue-worker -n facturx

# Nginx
kubectl logs -f deployment/facturx-nginx -n facturx

# Scheduler
kubectl logs -f cronjob/facturx-scheduler -n facturx
```

## 🔐 Sécurité

### Mesures implémentées

- **Authentification** : MFA, sessions sécurisées
- **Chiffrement** : TLS 1.3, données sensibles chiffrées
- **Isolation** : NetworkPolicy, Security Context
- **Audit** : Logs d'accès, piste d'audit complète
- **Compliance** : RGPD, archivage légal

### Vérifications régulières

```bash
# Audit de sécurité
make security-check

# Scan des vulnerabilités
trivy image ghcr.io/your-username/facturx:latest

# Vérification des certificats
kubectl get certificate -n facturx
```

## 🔄 Mise à jour et maintenance

### Processus de mise à jour

1. **Tests locaux**
   ```bash
   make test
   make security-check
   ```

2. **Déploiement staging**
   ```bash
   helm upgrade facturx-staging ./helm -n facturx-staging
   ```

3. **Tests en staging**
   ```bash
   curl -f https://staging.facturx.com/health
   ```

4. **Déploiement production**
   ```bash
   helm upgrade facturx ./helm -n facturx
   ```

### Maintenance programmée

```bash
# Sauvegarde avant maintenance
kubectl exec -it postgres-pod -n facturx -- pg_dump facturx_production > backup.sql

# Mise en mode maintenance
kubectl scale deployment facturx-app --replicas=0 -n facturx

# Maintenance (migrations, etc.)
kubectl exec -it deployment/facturx-app -n facturx -- php artisan migrate

# Retour en service
kubectl scale deployment facturx-app --replicas=3 -n facturx
```

## 🆘 Résolution de problèmes

### Problèmes courants

#### Application inaccessible
```bash
# Vérifier les pods
kubectl get pods -n facturx

# Vérifier les services
kubectl get svc -n facturx

# Vérifier l'ingress
kubectl get ingress -n facturx
```

#### Base de données inaccessible
```bash
# Vérifier PostgreSQL
kubectl logs -f deployment/postgresql -n facturx

# Tester la connexion
kubectl exec -it deployment/facturx-app -n facturx -- php artisan tinker
# >>> DB::connection()->getPdo()
```

#### Queue bloquée
```bash
# Redémarrer les workers
kubectl rollout restart deployment/facturx-queue-worker -n facturx

# Vérifier les jobs
kubectl exec -it deployment/facturx-app -n facturx -- php artisan queue:monitor
```

### Contacts support

- **Technique** : dev@facturx.com
- **Opérations** : ops@facturx.com
- **Sécurité** : security@facturx.com
- **Urgence** : +33 (0)1 XX XX XX XX

## 📚 Ressources supplémentaires

### Documentation
- [Architecture technique](docs/architecture.md)
- [API Documentation](https://docs.facturx.com/api)
- [Guide utilisateur](https://docs.facturx.com/user-guide)

### Liens utiles
- [Réglementation facturation électronique](https://www.impots.gouv.fr)
- [Documentation PPF](https://www.impots.gouv.fr/facturation-electronique)
- [GitHub Repository](https://github.com/your-username/facturx)

### Formation
- [Webinaires de formation](https://facturx.com/training)
- [Certification administrateur](https://facturx.com/certification)

---

**FacturX Deployment Guide v1.0**  
Dernière mise à jour : 2025-01-01  
Contact : dev@facturx.com