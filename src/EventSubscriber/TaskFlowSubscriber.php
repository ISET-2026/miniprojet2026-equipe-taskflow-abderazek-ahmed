<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TaskFlowSubscriber implements EventSubscriberInterface
{
    /**
     * Retourne les événements auxquels cet abonné écoute
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * Ajoute un header personnalisé X-TaskFlow-Version à chaque réponse
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('X-TaskFlow-Version', '1.0');
    }
}
