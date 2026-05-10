<?php

namespace App\Service;

use App\Entity\Tache;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;

class EmailService
{
    public function __construct(private MailerInterface $mailer) {}

    /**
     * Envoie un email de notification quand une tâche est assignée
     */
    public function sendTaskAssignmentEmail(Tache $tache, User $assignee, User $assigner): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@taskflow.com', 'TaskFlow'))
            ->to(new Address($assignee->getEmail(), $assignee->getPseudo()))
            ->subject('✅ Nouvelle tâche assignée : ' . $tache->getTitre())
            ->htmlTemplate('emails/tache_assignee.html.twig')
            ->context([
                'tache' => $tache,
                'projet' => $tache->getProjet(),
                'assignee' => $assignee,
                'assigner' => $assigner,
            ]);

        $this->mailer->send($email);
    }
}
