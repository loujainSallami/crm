<?php
namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTCreatedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            JWTCreatedEvent::class => 'onJWTCreated',
        ];
    }

    /*public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        $payload = $event->getData();
        dump($user, $payload); // <-- pour vérifier

        if ($user instanceof \App\Entity\VicidialUser) {
            $payload['id'] = $user->getId();      
            $payload['username'] = $user->getUser();
        }
    
        $event->setData($payload);
    }
    */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        $payload = $event->getData();
    
        // Ajouter le username
$payload['username'] = method_exists($user, 'getUser') ? $user->getUser() : $user->getUserIdentifier();
    
        $event->setData($payload);
    }
    
}