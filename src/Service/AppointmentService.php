<?php

namespace App\Service;

use App\Entity\CRM\Appointment;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AppointmentService
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Crée un nouveau rendez-vous avec validation et vérification des conflits
     */
    public function createAppointment(
        string $vicidialUser,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?string $description = null,
        ?int $leadId = null,
        ?string $campaignId = null
    ): Appointment {
        $appointment = new Appointment();
        $appointment->setVicidialUser($vicidialUser);
        $appointment->setStartTime($start);
        $appointment->setEndTime($end);
        $appointment->setDescription($description ?? '');
        $appointment->setVicidialLeadId($leadId);
        $appointment->setVicidialCampaignId($campaignId);

        $this->validateAppointment($appointment);
        $this->checkForScheduleConflicts($appointment);

        $this->entityManager->persist($appointment);
        $this->entityManager->flush();

        return $appointment;
    }

    /**
     * Met à jour un rendez-vous existant
     */
    public function updateAppointment(
        Appointment $appointment,
        string $vicidialUser,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?string $description = null,
        ?int $leadId = null,
        ?string $campaignId = null
    ): Appointment {
        $appointment->setVicidialUser($vicidialUser);
        $appointment->setStartTime($startTime);
        $appointment->setEndTime($endTime);
        $appointment->setDescription($description ?? '');
        $appointment->setVicidialLeadId($leadId);
        $appointment->setVicidialCampaignId($campaignId);

        $this->validateAppointment($appointment);
        $this->checkForScheduleConflicts($appointment, true);

        $this->entityManager->flush();

        return $appointment;
    }

    /**
     * Supprime un rendez-vous
     */
    public function deleteAppointment(Appointment $appointment): void
    {
        $this->entityManager->remove($appointment);
        $this->entityManager->flush();
    }

    /**
     * Valide les contraintes de l'entité + logique métier
     */
    private function validateAppointment(Appointment $appointment): void
    {
        if ($appointment->getStartTime() === null || $appointment->getEndTime() === null) {
            throw new BadRequestHttpException('Les dates de début et de fin sont obligatoires');
        }

        if ($appointment->getStartTime() >= $appointment->getEndTime()) {
            throw new BadRequestHttpException('La date de début doit être avant la date de fin');
        }

        if (!$appointment->getVicidialUser()) {
            throw new BadRequestHttpException('vicidialUser est obligatoire');
        }

        $errors = $this->validator->validate($appointment);

        if (count($errors) > 0) {
            throw new BadRequestHttpException((string) $errors);
        }
    }

    /**
     * Vérifie les conflits de planning pour le même agent Vicidial
     */
    private function checkForScheduleConflicts(Appointment $appointment, bool $isUpdate = false): void
    {
        $conflicts = $this->appointmentRepository->findConflictingAppointmentsByUser(
            $appointment->getVicidialUser(),
            $appointment->getStartTime(),
            $appointment->getEndTime(),
            $isUpdate ? $appointment : null
        );

        if (!empty($conflicts)) {
            throw new BadRequestHttpException('Le créneau horaire est déjà réservé pour cet utilisateur');
        }
    }

    /**
     * Récupère les rendez-vous à venir pour un utilisateur Vicidial
     */
    public function getUpcomingAppointments(string $vicidialUser): array
    {
        return $this->appointmentRepository->findUpcomingByVicidialUser($vicidialUser);
    }

    /**
     * Récupère un rendez-vous avec toutes ses relations
     */
    public function getAppointmentDetails(int $id): ?Appointment
    {
        return $this->appointmentRepository->findWithDetails($id);
    }

    /**
     * Récupère tous les rendez-vous
     */
    public function getAllAppointments(): array
    {
        return $this->appointmentRepository->findAllAppointments();
    }
}