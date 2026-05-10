<?php

namespace App\EventListener;

use App\Entity\Projet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Aligne l’API avec la sérialisation (créateur non présent dans projet:write) :
 * lors d’un POST, le créateur est déduit de l’utilisateur authentifié.
 */
#[AsDoctrineListener(event: Events::prePersist)]
final class ProjetCreateurDoctrineListener
{
    public function __construct(private readonly Security $security)
    {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Projet) {
            return;
        }

        if (null !== $entity->getCreateur()) {
            return;
        }

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $entity->setCreateur($user);
        }
    }
}
