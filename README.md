# TaskFlow — Mini-projet Symfony (groupes TP1 à TP10)

Application de gestion de projets et de tâches : CRUD Twig, sécurité, API Platform (JSON-LD), pagination, filtres Doctrine, uploads, mails, CLI et tests PHPUnit.

**Alignement barème « Mini-Projet 2 — TaskFlow »** : entités / relations / validations, CRUD (routes du sujet), formulaires (Bootstrap 5 + contraintes fichiers), sécurité (`ROLE_*`, propriété des projets, `#[IsGranted]`), API Platform + groupes, `ProjetStatsCalculator`, session (5 derniers projets, FIFO sans doublons), recherche `ProjetRepository::findByFilters` (+ filtre par étiquette), importmap + SweetAlert2, mail d’assignation + `TaskFlowSubscriber`, `FileUploader`, fixtures + KnpPaginator (6 / page projets, 10 / page tâches, tri), extension Twig `TaskFlowExtension`, commande `app:taskflow:report`, tests PHPUnit.  
*Le sujet mentionne Symfony 7.4 ; ce dépôt cible Symfony 8.0 (API Platform 4, attributs `Symfony\Component\Serializer\Attribute\Groups`).*

[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/9ydLnpF1)

## Prérequis

- PHP ≥ 8.4, Composer, extension PDO SQLite (ou adapter `DATABASE_URL` pour MySQL/PostgreSQL)
- Node non requis pour le cœur de l’app (AssetMapper + importmap)

## Installation

```bash
composer install
cp .env .env.local   # optionnel
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
php -S localhost:8000 -t public
```

Puis ouvrez `http://localhost:8000/projets` et la doc API : `http://localhost:8000/api`.

### Dossiers d’upload

Les fichiers sont stockés sous :

- `public/uploads/projets/` — images de couverture
- `public/uploads/taches/` — pièces jointes

Ils sont créés automatiquement à l’upload ; des `.gitkeep` sont versionnés pour garder l’arborescence.

## Mailtrap (emails d’assignation de tâche)

En développement, les mails partent via Mailtrap (ou restent en `null://null` si non configuré).

1. Créez une boîte dans [Mailtrap](https://mailtrap.io) et copiez le DSN SMTP.
2. Dans `.env.local` :

```dotenv
MAILER_DSN=smtp://USER:PASS@sandbox.smtp.mailtrap.io:2525
```

Les notifications utilisent **TemplatedEmail** (`templates/emails/tache_assignee.html.twig`), expéditeur **`noreply@taskflow.com`**.

## Identifiants de démo (fixtures)

| Rôle            | Email                 | Mot de passe |
|-----------------|------------------------|--------------|
| Administrateur  | admin@taskflow.com     | admin123     |
| Chef de projet  | chef@taskflow.com      | chef123      |
| Utilisateurs    | user1@taskflow.com … user5@taskflow.com | user123 |

Données : **6 étiquettes**, **8 projets**, **40 tâches**, **7 utilisateurs**.

## Tests

`.env.test` définit une base SQLite dédiée (`var/test.db`). Au lancement de PHPUnit, le schéma est migré et les fixtures sont rechargées.

```bash
php bin/phpunit
```

## Schéma des relations (MCD)

```mermaid
erDiagram
    USER ||--o{ PROJET : cree
    USER ||--o{ TACHE : assignee
    PROJET ||--o{ TACHE : contient
    TACHE }o--o{ ETIQUETTE : etiquetee
```

## API Platform

- JSON-LD : `GET /api/projets` (accept `application/ld+json`)
- Swagger / Hydra : `/api`
- Les attributs de sérialisation utilisent `Symfony\Component\Serializer\Attribute\Groups` (obligatoire en Symfony 8 pour que les groupes `projet:read` / `projet:write` s’appliquent).
- **POST** `/api/projets` : authentifiez un utilisateur (ex. chef de projet) puis envoyez `Content-Type: application/ld+json` avec `nom`, `description`, `dateLimite`, `statut`, etc. Le **créateur** n’est pas dans `projet:write` : il est renseigné automatiquement à partir de l’utilisateur connecté (`ProjetCreateurDoctrineListener`).

## Commande rapport

```bash
php bin/console app:taskflow:report
php bin/console app:taskflow:report --overdue
php bin/console app:taskflow:report -p 1
```
