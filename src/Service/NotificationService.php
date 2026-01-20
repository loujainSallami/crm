<?php

namespace App\Service;

use App\Entity\CrmUser;
use App\Entity\Notification;
use App\Entity\VicidialUser ;
use App\Entity\Appointment;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private NotificationRepository $notificationRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(NotificationRepository $notificationRepository, EntityManagerInterface $entityManager)
    {
        $this->notificationRepository = $notificationRepository;
        $this->entityManager = $entityManager;
    }

    public function getAllNotifications(CrmUser $user): array
    {
        return $this->notificationRepository->findByUser ($user);
    }

    public function createNotification(CrmUser  $user, string $message, ?int $appointmentId = null): Notification
    {
        $notification = new Notification();
        $notification->setMessage($message)
                     ->setUser ($user)
                     ->setCreatedAt(new \DateTime());

        if ($appointmentId) {
            $appointment = $this->entityManager->getRepository(Appointment::class)->find($appointmentId);
            if ($appointment) {
                $notification->setAppointment($appointment);
            }
        }

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->setIsRead(true);
        $this->entityManager->flush();
    }

    public function deleteNotification(Notification $notification): void
    {
        $this->entityManager->remove($notification);
        $this->entityManager->flush();
    }

    public function getUnreadNotifications(CrmUser  $user): array
    {
        return $this->notificationRepository->findUnreadByUser ($user);
    }
    // src/Service/NotificationService.php

public function getAllNotificationsForAdmin(): array
{
    return $this->notificationRepository->findAll(); // Toutes les notifications
}

}
