<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\CrmLead;
use App\Entity\CrmUser;

use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        CrmUser $user,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?string $description = null,
        ?CrmLead $lead = null
    ): Appointment {
        $appointment = new Appointment();
        $appointment->setUser($user);
        $appointment->setStartTime($start);
        $appointment->setEndTime($end);
        $appointment->setDescription($description);
        if ($lead) {
            $appointment->setLead($lead);
        }

        // Validation métier
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
            \DateTime $startTime,
            \DateTime $endTime,
            string $description,
            ?CrmLead $lead = null,
            ?CrmUser $user = null
        ): void {
            $appointment->setStartTime($startTime);
            $appointment->setEndTime($endTime);
            $appointment->setDescription($description);
        
            if ($lead) {
                $appointment->setLead($lead);
            }
        
            if ($user) {
                $appointment->setUser($user);
            }
        
            $this->entityManager->flush();
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
        if ($appointment->getStartTime() >= $appointment->getEndTime()) {
            throw new BadRequestHttpException('La date de début doit être avant la date de fin');
        }

        $errors = $this->validator->validate($appointment);
        if (count($errors) > 0) {
            throw new BadRequestHttpException((string) $errors);
        }
    }

    /**
     * Vérifie les conflits de planning
     */
    private function checkForScheduleConflicts(Appointment $appointment, bool $isUpdate = false): void
    {
        $conflicts = $this->appointmentRepository->findConflictingAppointments(
            $appointment->getStartTime(),
            $appointment->getEndTime(),
            $isUpdate ? $appointment : null
        );

        if (!empty($conflicts)) {
            throw new BadRequestHttpException('Le créneau horaire est déjà réservé');
        }
    }

    /**
     * Récupère les rendez-vous à venir pour un utilisateur
     */
    public function getUpcomingAppointments(CrmUser $user): array
    {
        return $this->appointmentRepository->findUpcomingByUser($user);
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
