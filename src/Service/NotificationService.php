<?php

namespace App\Service;

use App\Entity\CRM\CrmUser;
use App\Entity\CRM\Notification;
use App\Entity\CRM\Appointment;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function getAllNotifications(CrmUser $user): array
    {
        return $this->notificationRepository->findBy(
            ['crmUser' => $user],
            ['createdAt' => 'DESC']
        );
    }

    public function createNotification(
        CrmUser $user,
        string $message,
        ?int $appointmentId = null
    ): Notification {
        $notification = new Notification();
        $notification->setMessage($message);
        $notification->setCrmUser($user);
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);

        if ($appointmentId !== null) {
            $appointment = $this->entityManager
                ->getRepository(Appointment::class)
                ->find($appointmentId);

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

    public function getUnreadNotifications(CrmUser $user): array
    {
        return $this->notificationRepository->findBy(
            ['crmUser' => $user, 'isRead' => false],
            ['createdAt' => 'DESC']
        );
    }

    public function getAllNotificationsForAdmin(): array
    {
        return $this->notificationRepository->findBy(
            [],
            ['createdAt' => 'DESC']
        );
    }
}