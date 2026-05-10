# 🔗 Guide d'Intégration — Utiliser les Services et Filtres

Ce guide montre comment intégrer les services et filtres créés dans votre application.

---

## 📧 Envoyer un Email de Notification

### Dans ProjetController ou TacheController

```php
<?php

namespace App\Controller;

use App\Entity\Tache;
use App\Form\TacheType;
use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class TacheController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmailService $emailService,  // 👈 Injecter le service
    ) {}

    #[Route('/projets/{id}/taches/nouvelle', name: 'tache_create', methods: ['GET', 'POST'])]
    public function create(Request $request, $projet): Response
    {
        $tache = new Tache();
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Assigner à un utilisateur
            $assignee = $form->get('assignee')->getData();
            $tache->setAssigneA($assignee);

            $this->em->persist($tache);
            $this->em->flush();

            // 📧 Envoyer l'email de notification
            if ($assignee) {
                $this->emailService->sendTaskAssignmentEmail(
                    $tache,
                    $assignee,
                    $this->getUser()  // L'assignateur
                );
                $this->addFlash('success', '✅ Email envoyé à ' . $assignee->getPseudo());
            }

            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        return $this->render('tache/create.html.twig', [
            'form' => $form,
            'projet' => $projet,
        ]);
    }
}
```

---

## 📁 Uploader des Fichiers

### Configuration du formulaire (TacheType)

```php
<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class TacheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ...autres champs...
            ->add('pieceJointe', FileType::class, [
                'label' => 'Pièce jointe (PDF, DOCX, images)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Fichier invalide (PDF, DOCX ou images)',
                    ]),
                ],
            ]);
    }
}
```

### Dans le contrôleur - Uploader

```php
<?php

namespace App\Controller;

use App\Service\FileUploader;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TacheController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'file_uploader_taches')]
        private FileUploader $fileUploader,  // 👈 Injecter pour les tâches
    ) {}

    public function create(Request $request, $projet): Response
    {
        $tache = new Tache();
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 📁 Uploader la pièce jointe
            $pieceJointeFile = $form->get('pieceJointe')->getData();
            if ($pieceJointeFile) {
                $fileName = $this->fileUploader->upload($pieceJointeFile);
                $tache->setPieceJointeName($fileName);
            }

            $this->em->persist($tache);
            $this->em->flush();

            $this->addFlash('success', '✅ Tâche créée !');
            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        return $this->render('tache/create.html.twig', ['form' => $form]);
    }

    public function edit(Request $request, Tache $tache): Response
    {
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pieceJointeFile = $form->get('pieceJointe')->getData();
            if ($pieceJointeFile) {
                // Supprimer l'ancienne pièce jointe
                if ($tache->getPieceJointeName()) {
                    $this->fileUploader->remove($tache->getPieceJointeName());
                }

                // Uploader la nouvelle
                $fileName = $this->fileUploader->upload($pieceJointeFile);
                $tache->setPieceJointeName($fileName);
            }

            $this->em->flush();
            return $this->redirectToRoute('projet_show', ['id' => $tache->getProjet()->getId()]);
        }

        return $this->render('tache/edit.html.twig', ['form' => $form, 'tache' => $tache]);
    }

    public function delete(Request $request, Tache $tache): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tache->getId(), $request->get('_token'))) {
            // Supprimer le fichier du serveur
            if ($tache->getPieceJointeName()) {
                $this->fileUploader->remove($tache->getPieceJointeName());
            }

            $projetId = $tache->getProjet()->getId();
            $this->em->remove($tache);
            $this->em->flush();

            return $this->redirectToRoute('projet_show', ['id' => $projetId]);
        }
    }
}
```

---

## 📊 Utiliser les Filtres et Fonctions Twig

### 1. Dans un template - Liste de tâches

```twig
{# templates/tache/list.html.twig #}

{% extends "base.html.twig" %}

{% block body %}
<div class="container mt-5">
    <h1>📋 Tâches du projet {{ projet.nom }}</h1>

    <table class="table table-hover">
        <thead class="table-dark">
            <tr>
                <th>Titre</th>
                <th>Priorité</th>
                <th>Créée</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {% for tache in taches %}
            <tr>
                <td>
                    <strong>{{ tache.titre }}</strong>
                    {% if tache.description %}
                        <br><small class="text-muted">{{ tache.description|slice(0, 50) }}...</small>
                    {% endif %}
                </td>
                <td style="font-size: 1.5em;">
                    {# 🎯 Filtre priority_icon #}
                    {{ tache.priorite|priority_icon }}
                </td>
                <td>
                    {# 📅 Filtre time_ago #}
                    <small>{{ tache.dateCreation|time_ago }}</small>
                </td>
                <td>
                    <span class="badge {% if tache.statut == 'terminee' %}bg-success{% elseif tache.statut == 'en_cours' %}bg-warning{% else %}bg-secondary{% endif %}">
                        {{ tache.statut|replace('_', ' ')|capitalize }}
                    </span>
                </td>
                <td>
                    <a href="{{ path('tache_edit', {'id': tache.id}) }}" class="btn btn-sm btn-primary">
                        ✏️ Modifier
                    </a>
                </td>
            </tr>
            {% endfor %}
        </tbody>
    </table>
</div>
{% endblock %}
```

### 2. Dans un template - Détail du projet avec progression

```twig
{# templates/projet/show.html.twig #}

{% extends "base.html.twig" %}

{% block body %}
<div class="container mt-5">
    <h1>📂 {{ projet.nom }}</h1>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">📊 Progression du projet</h5>

            {# 📈 Fonction progress_bar #}
            {{ progress_bar(projectStats.getProgressPercentage(projet)) }}

            <small class="text-muted">
                {{ projectStats.getTaskCountByStatus(projet).terminee }} / 
                {{ projet.taches|length }} tâches terminées
            </small>
        </div>
    </div>

    <h3>📋 Tâches</h3>
    <table class="table">
        <tbody>
            {% for tache in projet.taches %}
            <tr>
                <td width="50%">{{ tache.titre }}</td>
                <td width="20%">
                    {# Filtre priority_icon #}
                    <span style="font-size: 1.3em;">{{ tache.priorite|priority_icon }}</span>
                </td>
                <td width="30%">
                    {# Filtre time_ago #}
                    <small class="text-muted">{{ tache.dateCreation|time_ago }}</small>
                </td>
            </tr>
            {% endfor %}
        </tbody>
    </table>
</div>
{% endblock %}
```

### 3. Filtres combinés

```twig
{# Afficher la date sous deux formes #}
<p>
    Créée le <strong>{{ tache.dateCreation|date('d/m/Y') }}</strong>
    (<span class="text-muted">{{ tache.dateCreation|time_ago }}</span>)
</p>

{# Afficher priorité avec icône et badge #}
<p>
    {{ tache.priorite|priority_icon }}
    <span class="badge bg-secondary">{{ tache.priorite|capitalize }}</span>
</p>

{# Barre de progression avec couleur #}
<div class="card">
    <div class="card-body">
        <h5>Progression</h5>
        {{ progress_bar(75) }}
    </div>
</div>
```

---

## 📄 Télécharger les pièces jointes

### Dans le template

```twig
{% if tache.pieceJointeName %}
    <a href="{{ path('tache_download', {'id': tache.id}) }}" class="btn btn-sm btn-success">
        📥 Télécharger la pièce jointe
    </a>
{% endif %}
```

### Dans le contrôleur

```php
#[Route('/taches/{id}/download', name: 'tache_download')]
public function download(Tache $tache): Response
{
    $fileName = $tache->getPieceJointeName();
    if (!$fileName) {
        throw $this->createNotFoundException('Pièce jointe non trouvée');
    }

    $filePath = $this->fileUploader->getTargetDirectory() . '/' . $fileName;

    return $this->file($filePath);
}
```

---

## 📊 Paginer les résultats

### Dans le contrôleur

```php
<?php

use Knp\Component\Pager\PaginatorInterface;

class ProjetController extends AbstractController
{
    public function __construct(
        private PaginatorInterface $paginator,
    ) {}

    #[Route('/projets', name: 'projet_list')]
    public function list(Request $request, ProjetRepository $repo): Response
    {
        $query = $repo->createQueryBuilder('p')
                     ->orderBy('p.dateCreation', 'DESC')
                     ->getQuery();

        // Paginer : 6 projets par page
        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('projet/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
```

### Dans le template

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

{# Afficher les boutons de pagination #}
<div class="pagination">
    {{ knp_pagination_render(pagination) }}
</div>

{# Afficher le nombre total #}
<p class="mt-3">
    <strong>Total :</strong> {{ pagination.getTotalItemCount() }} projets
</p>
```

---

## 🎯 Utiliser la Commande de Rapport

### Exécution basique

```bash
php bin/console app:taskflow:report
```

### Avec option --overdue (projets en retard)

```bash
php bin/console app:taskflow:report --overdue
```

### Avec option --projet (projet spécifique)

```bash
php bin/console app:taskflow:report --projet=1
```

---

## ✅ Checklist d'intégration

- [ ] EmailService injecté dans TacheController
- [ ] EmailService appelé lors de l'assignation d'une tâche
- [ ] FileUploader injecté dans ProjetController et TacheController
- [ ] Gestion des uploads dans les actions create/edit
- [ ] Gestion de la suppression des fichiers
- [ ] Filtres Twig utilisés dans au moins 3 templates
- [ ] PaginatorInterface injecté dans les contrôleurs
- [ ] Pagination implémentée sur liste des projets
- [ ] Pagination implémentée sur liste des tâches
- [ ] Commande de rapport testée avec succès
- [ ] Fixtures chargées avec `php bin/console doctrine:fixtures:load -n`

---

## 🚀 Vous êtes prêt !

Tous les services sont prêts à être utilisés. Consultez :
- `QUICK_START.md` pour démarrer rapidement
- `IMPLEMENTATION_6_9.md` pour la documentation complète
- `INVENTORY.md` pour l'inventaire des services
