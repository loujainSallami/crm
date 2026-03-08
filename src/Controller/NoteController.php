<?php

namespace App\Controller;

use App\Entity\CRM\Appointment;
use App\Entity\CRM\CrmUser;
use App\Entity\CRM\Note;
use App\Service\NoteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notes')]
class NoteController extends AbstractController
{
    public function __construct(
        private readonly NoteService $noteService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * ✅ Créer une nouvelle note
     */
    #[Route('', name: 'note_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            /** @var CrmUser|null $user */
            $user = $this->getUser();

            if (!$user instanceof CrmUser) {
                return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            $appointmentId = $data['appointment_id'] ?? null;
            if (!$appointmentId) {
                return $this->json(['error' => 'appointment_id est requis'], Response::HTTP_BAD_REQUEST);
            }

            $appointment = $this->entityManager->getRepository(Appointment::class)->find($appointmentId);
            if (!$appointment) {
                return $this->json(['error' => 'Rendez-vous introuvable'], Response::HTTP_NOT_FOUND);
            }

            $note = $this->noteService->createNote(
                $data['content'] ?? '',
                $user,
                $appointment
            );

            return $this->json($note, Response::HTTP_CREATED, [], ['groups' => 'note:read']);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * ✅ Mettre à jour une note
     */
    #[Route('/{id}', name: 'note_update', methods: ['PUT'])]
    public function update(Note $note, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $updatedNote = $this->noteService->updateNote($note, $data['content'] ?? '');
            return $this->json($updatedNote, Response::HTTP_OK, [], ['groups' => 'note:read']);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * ✅ Supprimer une note
     */
    #[Route('/{id}', name: 'note_delete', methods: ['DELETE'])]
    public function delete(Note $note): JsonResponse
    {
        try {
            $this->noteService->deleteNote($note);
            return $this->json(['message' => 'Note supprimée avec succès'], Response::HTTP_NO_CONTENT);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * ✅ Récupérer les notes d’un rendez-vous
     */
    #[Route('/appointment/{id}', name: 'notes_by_appointment', methods: ['GET'])]
    public function getByAppointment(Appointment $appointment): JsonResponse
    {
        try {
            $notes = $this->noteService->getAppointmentNotes($appointment);
            return $this->json($notes, Response::HTTP_OK, [], ['groups' => 'note:read']);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}