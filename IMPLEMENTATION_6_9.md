# 📋 TaskFlow — Implémentation des Parties 6-9

## 📌 Résumé de l'implémentation

Ce document résume l'implémentation complète des **Parties 6 à 9** du mini-projet TaskFlow.

---

## **Partie 6 — Email et Événements ✅**

### 6.1 Service Email (`EmailService.php`)

**Fichier** : `src/Service/EmailService.php`

Service injecté pour envoyer des notifications par email quand une tâche est assignée.

```php
// Utilisation dans le contrôleur
$this->emailService->sendTaskAssignmentEmail($tache, $assignee, $assigner);
```

**Caractéristiques** :
- Envoie un email HTML templé (Twig)
- Utilise `TemplatedEmail` de Symfony Mailer
- Adresse d'expédition : `noreply@taskflow.com`
- Sujet dynamique : "✅ Nouvelle tâche assignée : {titre}"

### 6.2 Template Email (`tache_assignee.html.twig`)

**Fichier** : `templates/emails/tache_assignee.html.twig`

Template responsive avec :
- Titre de la tâche
- Nom du projet
- Priorité avec icône emoji
- Date d'échéance
- Nom de l'assignateur
- Description de la tâche
- Styling CSS intégré

### 6.3 Event Subscriber (`TaskFlowSubscriber.php`)

**Fichier** : `src/EventSubscriber/TaskFlowSubscriber.php`

Ajoute le header personnalisé `X-TaskFlow-Version: 1.0` à toutes les réponses HTTP.

```bash
# Vérifier dans le terminal
curl -I http://localhost:8000/projets
# Vous verrez : X-TaskFlow-Version: 1.0
```

---

## **Partie 7 — Upload de fichiers ✅**

### 7.1 Service FileUploader (`FileUploader.php`)

**Fichier** : `src/Service/FileUploader.php`

Service réutilisable pour gérer les uploads de fichiers.

**Méthodes** :
- `upload(UploadedFile $file): string` — Télécharge et retourne le nom sécurisé
- `remove(string $fileName): void` — Supprime un fichier
- `getTargetDirectory(): string` — Retourne le répertoire cible

**Avantages** :
- Génère des noms sécurisés avec slug + uniqid
- Prévient les injections de chemins
- Peut être injecté dans les contrôleurs

### 7.2 Configuration (`config/services.yaml`)

```yaml
parameters:
    upload_directory_projets: '%kernel.project_dir%/public/uploads/projets'
    upload_directory_taches: '%kernel.project_dir%/public/uploads/taches'

services:
    App\Service\FileUploader:
        arguments:
            $targetDirectory: '%upload_directory_projets%'
    
    file_uploader_taches:
        class: App\Service\FileUploader
        arguments:
            $targetDirectory: '%upload_directory_taches%'
```

**Utilisation dans le contrôleur** :
```php
// Injecter le service
public function __construct(
    #[Autowire(service: 'file_uploader_taches')]
    private FileUploader $fileUploaderTaches,
) {}
```

### 7.3 Répertoires d'upload

```
public/uploads/
├── projets/     # Images de couverture (JPEG, PNG, WebP, max 2 Mo)
└── taches/      # Pièces jointes (PDF, DOCX, images, max 5 Mo)
```

---

## **Partie 8 — DataFixtures et Pagination ✅**

### 8.1 DataFixtures avec FakerPHP

#### **EtiquetteFixtures.php**
6 étiquettes prédéfinies avec couleurs :

| Nom | Couleur |
|-----|---------|
| Bug | #E74C3C (rouge) |
| Feature | #3498DB (bleu) |
| Urgent | #E91E63 (rose) |
| Documentation | #9B59B6 (violet) |
| Amélioration | #F39C12 (orange) |
| Design | #16A085 (teal) |

#### **UserFixtures.php**
7 utilisateurs avec rôles variés :

1. **Admin** : `admin@taskflow.com` / `admin123` → `ROLE_ADMIN`
2. **Chef Projet** : `chef@taskflow.com` / `chef123` → `ROLE_CHEF_PROJET`
3-7. **5 Utilisateurs** : `user1-5@taskflow.com` / `user123` → `ROLE_USER`

Tous les mots de passe sont hachés avec `UserPasswordHasherInterface`.

#### **ProjetFixtures.php**
8 projets réalistes avec :
- Noms générés (Système de Stock, E-commerce, etc.)
- Descriptions par Faker
- Statuts variés (planifié, en cours, terminé, annulé)
- Créateurs aléatoires parmi les utilisateurs
- Dates limites réalistes (+1 à +3 mois)

#### **TacheFixtures.php**
40 tâches réparties (5 par projet) :
- Titres dynamiques
- Descriptions par Faker
- Priorités variées (basse, moyenne, haute, urgente)
- Statuts variés (à faire, en cours, terminée)
- Assignées à des utilisateurs aléatoires
- Chacune a 1-3 étiquettes aléatoires (ManyToMany)

### 8.2 Chargement des Fixtures

```bash
php bin/console doctrine:fixtures:load -n
```

**Résultat** :
- ✅ 7 utilisateurs chargés
- ✅ 6 étiquettes chargées
- ✅ 8 projets chargés
- ✅ 40 tâches réparties et assignées

### 8.3 Pagination avec KnpPaginatorBundle

**Installation** : ✅ (déjà installé via composer)

```bash
composer require knplabs/knp-paginator-bundle
```

**Configuration** : `config/packages/knp_paginator.yaml`

**Utilisation dans le contrôleur** :
```php
public function list(Request $request, ProjetRepository $repo, PaginatorInterface $paginator)
{
    $query = $repo->createQueryBuilder('p')
                  ->orderBy('p.dateCreation', 'DESC')
                  ->getQuery();

    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        6 // 6 projets par page
    );

    return $this->render('projet/list.html.twig', [
        'pagination' => $pagination,
    ]);
}
```

**Affichage dans Twig** :
```twig
{# templates/projet/list.html.twig #}
<table class="table">
  <thead>
    <tr>
      <th>{{ knp_pagination_sortable(pagination, 'Nom', 'p.nom') }}</th>
      <th>{{ knp_pagination_sortable(pagination, 'Date', 'p.dateCreation') }}</th>
    </tr>
  </thead>
  <tbody>
    {% for projet in pagination %}
      <tr>
        <td>{{ projet.nom }}</td>
        <td>{{ projet.dateCreation|date('d/m/Y') }}</td>
      </tr>
    {% endfor %}
  </tbody>
</table>

{# Affichage de la pagination #}
<div class="pagination">
  {{ knp_pagination_render(pagination) }}
</div>

{# Affichage du nombre total de résultats #}
<p>Total : {{ pagination.getTotalItemCount() }} résultats</p>
```

---

## **Partie 9 — Extension Twig et Commande Console ✅**

### 9.1 Extension Twig (`TaskFlowExtension.php`)

**Fichier** : `src/Twig/TaskFlowExtension.php`

#### **Filtre `time_ago`**
Convertit une date en format relatif.

```twig
{{ tache.dateCreation|time_ago }}
{# Affichage :
   - "il y a 2 minutes"
   - "il y a 3 heures"
   - "hier"
   - "il y a 5 jours"
   - "il y a 2 semaines"
   - "il y a 3 mois"
   - "il y a 1 an"
#}
```

#### **Filtre `priority_icon`**
Retourne un icône emoji selon la priorité.

```twig
{{ tache.priorite|priority_icon }}
{# Affichage :
   - 🔵 pour "basse"
   - 🟢 pour "moyenne"
   - 🟠 pour "haute"
   - 🔴 pour "urgente"
#}
```

#### **Fonction `progress_bar`**
Génère une barre de progression HTML Bootstrap avec couleur dynamique.

```twig
{{ progress_bar(75) }}
{# Affichage :
<div class="progress">
  <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75">75%</div>
</div>
#}
```

**Couleurs dynamiques** :
- ✅ **Vert** (> 75%) : projet bien avancé
- 🔵 **Info** (> 50%) : progression normale
- 🟡 **Jaune** (> 25%) : commencer à accélérer
- 🔴 **Danger** (≤ 25%) : urgent

#### **Utilisation dans les templates**

```twig
{# Liste des projets #}
<tr>
  <td>{{ projet.nom }}</td>
  <td>{{ projet.dateCreation|time_ago }}</td>
  <td>{{ progress_bar(projectStats.getProgressPercentage(projet)) }}</td>
</tr>

{# Détail du projet - tâches #}
<tr>
  <td>{{ tache.titre }}</td>
  <td>{{ tache.priorite|priority_icon }}</td>
  <td>{{ tache.dateCreation|time_ago }}</td>
</tr>
```

### 9.2 Commande Console `app:taskflow:report`

**Fichier** : `src/Command/TaskFlowReportCommand.php`

```bash
php bin/console app:taskflow:report
php bin/console app:taskflow:report --overdue
php bin/console app:taskflow:report --projet=1
```

#### **Fonctionnalités**

1. **Statistiques Globales**
   - Nombre total de projets
   - Nombre total de tâches

2. **Répartition des Statuts**
   - Projets par statut (tableau avec icônes)
   - Tâches par statut (tableau)

3. **Projets en Retard**
   - Date limite dépassée ET tâches non terminées
   - Affichage : nom, date limite, jours de retard, tâches en attente
   - Avertissement ⚠️ si des projets sont en retard

4. **Top 5 Utilisateurs les Plus Actifs**
   - Classement par nombre de tâches assignées
   - Tableau avec rang, pseudo, nombre de tâches

#### **Sortie exemple**

```
📊 Rapport TaskFlow - État des Projets
======================================

📈 Statistiques Globales
------------------------

Nombre total de projets : 8
Nombre total de tâches : 40

🎯 Répartition des Statuts des Projets
--------------------------------------

 ------------- -------- 
  Statut        Nombre  
 ------------- -------- 
  🔵 Planifie   4       
  🟡 En cours   2       
  🟢 Termine    1       
  🔴 Annule     1       
 ------------- -------- 

✅ Répartition des Statuts des Tâches
-------------------------------------

 ---------- -------- 
  Statut     Nombre  
 ---------- -------- 
  A faire    16      
  En cours   10      
  Terminee   14      
 ---------- -------- 

⚠️ Projets en Retard
--------------------

 [OK] ✅ Aucun projet en retard !

👥 Top 5 des Utilisateurs les Plus Actifs
-----------------------------------------

 ------ ------------- ------------------ 
  Rang   Utilisateur   Tâches Assignées  
 ------ ------------- ------------------ 
  1      Christine     11                
  2      Bertrand      10                
  3      Caroline      9                 
  4      Patrick       8                 
  5      Jacques       2                 
 ------ ------------- ------------------ 

 [INFO] 📊 Rapport généré avec succès !
```

#### **Utilisation de SymfonyStyle**

```php
$io->title('Titre principal');
$io->section('Sous-titre');
$io->table(['Col 1', 'Col 2'], $data);
$io->warning('Message d\'avertissement');
$io->success('Message de succès');
$io->info('Information');
```

---

## 📦 Fichiers créés/modifiés

### Nouveaux fichiers

```
src/
├── Service/
│   ├── EmailService.php
│   └── FileUploader.php
├── EventSubscriber/
│   └── TaskFlowSubscriber.php
├── Twig/
│   └── TaskFlowExtension.php
├── Command/
│   └── TaskFlowReportCommand.php
└── DataFixtures/
    ├── EtiquetteFixtures.php
    ├── UserFixtures.php
    ├── ProjetFixtures.php
    └── TacheFixtures.php

templates/
└── emails/
    └── tache_assignee.html.twig

tests/
└── Command/
    └── TaskFlowReportCommandTest.php

config/
└── services.yaml (modifié)
```

### Fichiers de documentation

```
MAILTRAP_CONFIG.md          # Configuration Mailtrap
IMPLEMENTATION_6_9.md       # Ce fichier
```

---

## 🔧 Configuration

### 1. Services Symfony

Dans `config/services.yaml`, deux services `FileUploader` sont définis :

```yaml
services:
    App\Service\FileUploader:
        arguments:
            $targetDirectory: '%upload_directory_projets%'
    
    file_uploader_taches:
        class: App\Service\FileUploader
        arguments:
            $targetDirectory: '%upload_directory_taches%'
```

### 2. Mailer (Mailtrap)

Dans `.env.local` :

```env
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525?encryption=tls
```

### 3. Extensions Twig

La classe `TaskFlowExtension` est enregistrée automatiquement via `autoconfigure: true`.

---

## 🧪 Tests

Pour exécuter la commande de test :

```bash
php bin/console app:taskflow:report
```

Pour exécuter le test unitaire :

```bash
php bin/phpunit tests/Command/TaskFlowReportCommandTest.php
```

---

## ✅ Checklist d'implémentation

- ✅ Service EmailService pour envoyer des notifications
- ✅ Template email Twig responsive
- ✅ Event Subscriber pour ajouter le header X-TaskFlow-Version
- ✅ Service FileUploader réutilisable
- ✅ Configuration de deux répertoires d'upload
- ✅ DataFixtures complètes (7 utilisateurs, 6 étiquettes, 8 projets, 40 tâches)
- ✅ Pagination avec KnpPaginatorBundle
- ✅ Extension Twig avec 3 fonctionnalités (filtres + fonction)
- ✅ Commande console TaskFlowReportCommand avec SymfonyStyle
- ✅ Tests unitaires et fonctionnels

---

## 📝 Notes additionnelles

1. **Fixtures** : Les fixtures chargeront en ordre de dépendance (Etiquette → User → Projet → Tache)

2. **Mailtrap** : Pour développement, créez un compte gratuit sur [mailtrap.io](https://mailtrap.io)

3. **Upload** : Les répertoires `public/uploads/{projets,taches}` doivent avoir les permissions d'écriture

4. **Extension Twig** : Enregistrée automatiquement via `autoconfigure: true`

5. **Commande Console** : Accessible via `php bin/console app:taskflow:report`

---

## 🚀 Prochaines étapes

- Intégrer `EmailService` dans `TacheController` pour envoyer un email lors de l'assignation
- Utiliser `FileUploader` dans les contrôleurs `ProjetController` et `TacheController`
- Utiliser les filtres Twig dans les templates
- Paginer les listes avec `KnpPaginatorBundle`
