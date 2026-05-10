# 📖 PARTIES 6-9 — Guide de Démarrage

Bienvenue ! Ce document vous guide dans l'utilisation des **Parties 6-9** implémentées pour TaskFlow.

---

## 🚀 Démarrage rapide (5 min)

### 1️⃣ Charger les données de test

```bash
php bin/console doctrine:fixtures:load -n
```

✅ Cela charge automatiquement :
- 7 utilisateurs (admin + chef + 5 users)
- 6 étiquettes (Bug, Feature, Urgent, etc.)
- 8 projets réalistes
- 40 tâches réparties entre les projets

### 2️⃣ Tester la commande de rapport

```bash
php bin/console app:taskflow:report
```

✅ Vous verrez un rapport formaté avec :
- Statistiques globales
- Répartition des statuts
- Projets en retard
- Top 5 utilisateurs actifs

### 3️⃣ C'est prêt !

Vous pouvez maintenant :
- Utiliser les **services** dans vos contrôleurs
- Appliquer les **filtres Twig** dans vos templates
- **Paginer** vos listes de résultats
- **Envoyer des emails** lors de l'assignation

---

## 📚 Documentation disponible

| Document | Pour qui ? | Durée |
|----------|-----------|-------|
| **QUICK_START.md** | Développeurs pressés | ⚡ 5 min |
| **INTEGRATION_GUIDE.md** | Développeurs intégrant le code | 💻 15 min |
| **IMPLEMENTATION_6_9.md** | Développeurs comprenants les détails | 📖 30 min |
| **INVENTORY.md** | Références technique | 📋 En permanence |
| **CHECKLIST.md** | Suivi d'implémentation | ✅ En permanence |
| **MAILTRAP_CONFIG.md** | Configuration email | 📧 5 min |
| **FINAL_SUMMARY.md** | Vue d'ensemble complète | 🎯 10 min |

### 👉 Commencez par **QUICK_START.md** !

---

## 🎯 Ce qui a été créé pour vous

### Services (prêts à l'emploi)

#### EmailService — Envoyer des notifications
```php
public function __construct(private EmailService $emailService) {}

public function assignTask() {
    $this->emailService->sendTaskAssignmentEmail($tache, $assignee, $assigner);
}
```

#### FileUploader — Gérer les uploads
```php
public function uploadFile(UploadedFile $file) {
    $fileName = $this->fileUploader->upload($file);
}
```

### Filtres Twig (à utiliser dans les templates)

```twig
{{ tache.dateCreation|time_ago }}        {# "il y a 3 jours" #}
{{ tache.priorite|priority_icon }}       {# 🔴 urgente #}
{{ progress_bar(75) }}                   {# Barre verte colorée #}
```

### Commande Console

```bash
php bin/console app:taskflow:report       # Rapport complet
php bin/console app:taskflow:report --overdue  # Projets en retard
```

### Données de test

- ✅ 7 utilisateurs chargés
- ✅ 6 étiquettes avec couleurs
- ✅ 8 projets réalistes
- ✅ 40 tâches assignées

---

## 📋 Checklist d'utilisation

- [ ] J'ai chargé les fixtures avec `doctrine:fixtures:load`
- [ ] J'ai exécuté la commande `app:taskflow:report`
- [ ] J'ai lu **QUICK_START.md**
- [ ] Je sais comment injecter EmailService
- [ ] Je sais comment utiliser FileUploader
- [ ] J'ai vu les filtres Twig en action
- [ ] Je comprends comment utiliser les données de test

---

## 🔍 Quelques exemples

### Exemple 1 : Envoyer un email lors de l'assignation

**Dans TacheController.php :**
```php
if ($assignee) {
    $this->emailService->sendTaskAssignmentEmail(
        $tache,
        $assignee,
        $this->getUser()
    );
}
```

### Exemple 2 : Uploader un fichier

**Dans le formulaire :**
```php
->add('fichier', FileType::class, [
    'mapped' => false,
    'required' => false,
])
```

**Dans le contrôleur :**
```php
if ($uploadFile) {
    $fileName = $this->fileUploader->upload($uploadFile);
    $tache->setPieceJointeName($fileName);
}
```

### Exemple 3 : Utiliser les filtres Twig

**Dans un template :**
```twig
<td>{{ tache.priorite|priority_icon }}</td>
<td>{{ tache.dateCreation|time_ago }}</td>
<td>{{ progress_bar(projectProgress) }}</td>
```

### Exemple 4 : Paginer une liste

**Dans le contrôleur :**
```php
$pagination = $paginator->paginate(
    $query,
    $request->query->getInt('page', 1),
    6
);
```

**Dans le template :**
```twig
{{ knp_pagination_render(pagination) }}
```

---

## 🐛 Troubleshooting

### Les fixtures ne se chargent pas
```bash
# Assurez-vous que la base de données existe
php bin/console doctrine:database:create

# Puis chargez les fixtures
php bin/console doctrine:fixtures:load -n
```

### EmailService ne fonctionne pas
```bash
# Vérifiez la configuration dans .env.local
MAILER_DSN=smtp://user:pass@smtp.mailtrap.io:2525?encryption=tls

# Testez l'envoi
php bin/console mailer:test your-email@example.com
```

### FileUploader ne trouve pas le répertoire
```bash
# Créez les répertoires
mkdir -p public/uploads/{projets,taches}

# Vérifiez les permissions
chmod -R 755 public/uploads
```

---

## 📧 Utilisateurs de test

Vous pouvez vous connecter avec :

| Email | Mot de passe | Rôle |
|-------|-------------|------|
| admin@taskflow.com | admin123 | Admin |
| chef@taskflow.com | chef123 | Chef Projet |
| user1@taskflow.com | user123 | Utilisateur |

---

## 🎓 Concepts Symfony utilisés

- ✅ Injection de dépendances
- ✅ Services personnalisés
- ✅ Event Subscribers
- ✅ Extensions Twig
- ✅ Commandes Console
- ✅ DataFixtures
- ✅ Mailer avec templates
- ✅ KnpPaginator
- ✅ SymfonyStyle

---

## 📞 Besoin d'aide ?

1. **Pour démarrer** → Voir **QUICK_START.md** ⚡
2. **Pour intégrer** → Voir **INTEGRATION_GUIDE.md** 💻
3. **Pour comprendre** → Voir **IMPLEMENTATION_6_9.md** 📖
4. **Pour références** → Voir **INVENTORY.md** 📋

---

## ✅ Status

**🎉 Toutes les Parties 6-9 sont 100% implémentées et testées.**

Les services fonctionnent, les fixtures se chargent, la commande de rapport génère un rapport complet.

Vous êtes prêt à intégrer dans vos contrôleurs et templates ! 🚀

---

**Dernière mise à jour :** 10 Mai 2026  
**Version :** 1.0  
**Status :** Production Ready ✅
