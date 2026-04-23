<?php

namespace App\Controller;

use App\Entity\CRM\Notification;
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
        private readonly NotificationService $notificationService,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'notifications_list', methods: ['GET'])]
    public function getAllNotifications(): JsonResponse
    {
        $notifications = $this->notificationService->getAllNotificationsForAdmin();

        $data = array_map(static function (Notification $n): array {
            return [
                'id' => $n->getId(),
                'message' => $n->getMessage(),
                'created_at' => $n->getCreatedAt()?->format('Y-m-d H:i:s'),
                'is_read' => $n->getIsRead(),
                'vicidialUser' => $n->getVicidialUser(),
                'appointment_id' => $n->getAppointment()?->getId(),
            ];
        }, $notifications);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/create', name: 'notification_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['message'])) {
            return $this->json(
                ['error' => 'Le champ message est obligatoire'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $vicidialUser = $data['vicidialUser'] ?? null;

        try {
            $notification = $this->notificationService->createNotification(
                $vicidialUser,
                (string) $data['message'],
                isset($data['appointment_id']) && $data['appointment_id'] !== ''
                    ? (int) $data['appointment_id']
                    : null
            );

            return new JsonResponse(
                $this->serializer->serialize(
                    $notification,
                    'json',
                    ['groups' => ['notification:read']]
                ),
                Response::HTTP_CREATED,
                [],
                true
            );
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/read', name: 'notification_mark_as_read', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function markAsRead(Notification $notification): JsonResponse
    {
        $this->notificationService->markAsRead($notification);

        return new JsonResponse(
            $this->serializer->serialize(
                $notification,
                'json',
                ['groups' => ['notification:read']]
            ),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'notification_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Notification $notification): JsonResponse
    {
        $this->notificationService->deleteNotification($notification);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/unread/{vicidialUser}', name: 'notifications_unread', methods: ['GET'])]
    public function getUnreadNotifications(string $vicidialUser): JsonResponse
    {
        $notifications = $this->notificationService->getUnreadNotifications($vicidialUser);

        return new JsonResponse(
            $this->serializer->serialize(
                $notifications,
                'json',
                ['groups' => ['notification:read']]
            ),
            Response::HTTP_OK,
            [],
            true
        );
    }
}