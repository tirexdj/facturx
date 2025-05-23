# Architecture du projet FacturX

Ce projet suit les principes du Domain-Driven Design (DDD) et implémente une architecture hexagonale.

## Structure des dossiers

- `Domain/` - Cœur métier, indépendant de l'infrastructure
- `Application/` - Cas d'utilisation de l'application
- `Infrastructure/` - Adaptateurs sortants
- `Interface/` - Adaptateurs entrants

## Patterns implémentés

1. Architecture hexagonale - Isolation du domaine métier
2. CQRS - Séparation des opérations de lecture et d'écriture
3. Repository Pattern - Abstraction de l'accès aux données
4. Factory Pattern - Création d'objets complexes
5. Specification Pattern - Encapsulation des règles métier
6. Value Objects - Représentation des concepts sans identité
7. Event Sourcing - Pour certains modules critiques