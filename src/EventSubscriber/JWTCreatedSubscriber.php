<?php

namespace App\EventSubscriber;

use App\Security\VicidialUser;
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

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        $payload = $event->getData();

        if ($user instanceof VicidialUser) {
            $payload['username'] = $user->getUserIdentifier();
            $payload['fullName'] = $user->getFullName();
            $payload['userLevel'] = $user->getUserLevel();
            $payload['roles'] = $user->getRoles();
        }

        $event->setData($payload);
    }
}