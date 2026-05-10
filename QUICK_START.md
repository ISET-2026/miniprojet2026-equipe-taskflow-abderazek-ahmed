# 🚀 Démarrage rapide — Parties 6-9 TaskFlow

## ⚡ Installation rapide (5 minutes)

### 1. Installer les dépendances
```bash
cd /home/denzi/projetsym/miniprojet2026-equipe-taskflow-abderazek-ahmed
composer require --dev doctrine/doctrine-fixtures-bundle fakerphp/faker knplabs/knp-paginator-bundle -n
```

### 2. Charger les fixtures
```bash
php bin/console doctrine:fixtures:load -n
```

### 3. Vérifier la commande de rapport
```bash
php bin/console app:taskflow:report
```

✅ Vous devriez voir un rapport formaté avec statistiques et top 5 utilisateurs.

---

## 📦 Ce qui a été créé

| Fichier | Fonction |
|---------|----------|
| `src/Service/EmailService.php` | Envoie emails de notification |
| `src/Service/FileUploader.php` | Gère upload sécurisé de fichiers |
| `src/EventSubscriber/TaskFlowSubscriber.php` | Ajoute header personnalisé |
| `src/Twig/TaskFlowExtension.php` | Filtres et fonctions Twig |
| `src/Command/TaskFlowReportCommand.php` | Rapport console formaté |
| `src/DataFixtures/*.php` | 4 fichiers de fixtures |
| `templates/emails/tache_assignee.html.twig` | Template email |
| `MAILTRAP_CONFIG.md` | Config email |
| `IMPLEMENTATION_6_9.md` | Documentation complète |
| `CHECKLIST.md` | Checklist d'implémentation |

---

## 🎯 Utilisation immédiate

### A. Envoyer un email (EmailService)
```php
// Dans votre contrôleur
public function __construct(private EmailService $emailService) {}

public function create() {
    // ... créer une tâche ...
    $this->emailService->sendTaskAssignmentEmail($tache, $assignee, $assigner);
}
```

### B. Uploader un fichier (FileUploader)
```php
// Injecter le service
#[Autowire(service: 'file_uploader_taches')]
private FileUploader $fileUploader

// Uploader
if ($file) {
    $fileName = $this->fileUploader->upload($file);
    $tache->setPieceJointeName($fileName);
}

// Supprimer
$this->fileUploader->remove($fileName);
```

### C. Utiliser les filtres Twig
```twig
{# Date relative #}
{{ tache.dateCreation|time_ago }}

{# Icône priorité #}
{{ tache.priorite|priority_icon }}

{# Barre de progression #}
{{ progress_bar(65) }}
```

### D. Paginer les résultats
```php
// Dans le contrôleur
$pagination = $paginator->paginate(
    $query,
    $request->query->getInt('page', 1),
    6 // 6 items par page
);

// Dans le template
<div class="pagination">
  {{ knp_pagination_render(pagination) }}
</div>
```

### E. Exécuter le rapport
```bash
php bin/console app:taskflow:report
php bin/console app:taskflow:report --overdue
```

---

## 📊 Données de test disponibles

| Email | Mot de passe | Rôle |
|-------|-------------|------|
| admin@taskflow.com | admin123 | Admin |
| chef@taskflow.com | chef123 | Chef Projet |
| user1@taskflow.com | user123 | Utilisateur |
| user2@taskflow.com | user123 | Utilisateur |
| user3@taskflow.com | user123 | Utilisateur |
| user4@taskflow.com | user123 | Utilisateur |
| user5@taskflow.com | user123 | Utilisateur |

---

## 🔌 Configuration Mailtrap (optionnel)

Fichier `.env.local`:
```env
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525?encryption=tls
```

Voir `MAILTRAP_CONFIG.md` pour plus de détails.

---

## 📚 Documentation complète

- 📖 `IMPLEMENTATION_6_9.md` — Documentation détaillée de chaque partie
- ✅ `CHECKLIST.md` — Checklist complète d'implémentation
- 📧 `MAILTRAP_CONFIG.md` — Configuration email

---

## ⚙️ Fichiers de configuration modifiés

### `config/services.yaml`
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

---

## 🧪 Tester rapidement

```bash
# 1. Voir la commande en action
php bin/console app:taskflow:report

# 2. Voir les projets en retard
php bin/console app:taskflow:report --overdue

# 3. Vérifier les fixtures
php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM projet"

# 4. Tester l'extension Twig
php bin/console twig:lint templates/
```

---

## 🎉 Status final

✅ **PARTIES 6-9 COMPLÈTEMENT IMPLÉMENTÉES**

Tous les fichiers sont créés, configurés et testés.
Les fixtures se chargent avec succès.
La commande de rapport fonctionne parfaitement.

Prêt pour le développement des contrôleurs et templates ! 🚀
