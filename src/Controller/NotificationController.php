<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\VicidialUser;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService,
        private SerializerInterface $serializer
    ) {}

    /**
     * Récupère toutes les notifications (pour tous les utilisateurs)
     */
    #[Route('', name: 'notifications_list', methods: ['GET'])]
    public function getAllNotifications(): JsonResponse
    {
        $notifications = $this->notificationService->getAllNotificationsForAdmin(); // Renvoie toutes les notifications

        $data = array_map(fn(Notification $n) => [
            'id' => $n->getId(),
            'message' => $n->getMessage(),
            'created_at' => $n->getCreatedAt()->format('Y-m-d H:i:s'),
            'is_read' => $n->isRead(),
            'user' => [
                'id' => $n->getUser()->getId(),
                'username' => $n->getUser()->getUser(),
            ],
        ], $notifications);

        return $this->json($data);
    }

    /**
     * Crée une nouvelle notification pour un utilisateur
     */
    #[Route('/create', name: 'notification_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var VicidialUser $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        try {
            $notification = $this->notificationService->createNotification(
                $user,
                $data['message'],
                $data['appointment_id'] ?? null
            );

            return new JsonResponse(
                $this->serializer->serialize($notification, 'json', ['groups' => 'notification:read']),
                Response::HTTP_CREATED,
                [],
                true
            );
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Marque une notification comme lue
     */
    #[Route('/{id}/read', name: 'notification_mark_as_read', methods: ['PUT'])]
    public function markAsRead(Notification $notification): JsonResponse
    {
        $this->notificationService->markAsRead($notification);

        return new JsonResponse(
            $this->serializer->serialize($notification, 'json', ['groups' => 'notification:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Supprime une notification
     */
    #[Route('/{id}', name: 'notification_delete', methods: ['DELETE'])]
    public function delete(Notification $notification): JsonResponse
    {
        $this->notificationService->deleteNotification($notification);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Récupère les notifications non lues d'un utilisateur
     */
    #[Route('/unread', name: 'notifications_unread', methods: ['GET'])]
    public function getUnreadNotifications(): JsonResponse
    {
        /** @var VicidialUser $user */
        $user = $this->getUser();
        $notifications = $this->notificationService->getUnreadNotifications($user);

        return new JsonResponse(
            $this->serializer->serialize($notifications, 'json', ['groups' => 'notification:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }
}
