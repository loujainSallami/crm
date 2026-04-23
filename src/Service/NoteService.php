<?php

namespace App\Service;

use App\Entity\CRM\Appointment;
use App\Entity\CRM\Note;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class NoteService
{
    public function __construct(
        private readonly NoteRepository $noteRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Crée et persiste une nouvelle note
     */
    public function createNote(string $content, ?string $vicidialUser, Appointment $appointment): array
    {
        $note = new Note();
        $note->setContent($content)
             ->setVicidialUser($vicidialUser)
             ->setAppointment($appointment)
             ->setCreatedAt(new \DateTimeImmutable());

        $this->validate($note);

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        return $this->serialize($note);
    }

    /**
     * Met à jour le contenu d'une note existante
     */
    public function updateNote(Note $note, string $newContent): array
    {
        $note->setContent($newContent)
             ->setUpdatedAt(new \DateTimeImmutable());

        $this->validate($note);
        $this->entityManager->flush();

        return $this->serialize($note);
    }

    /**
     * Supprime définitivement une note
     */
    public function deleteNote(Note $note): void
    {
        $this->entityManager->remove($note);
        $this->entityManager->flush();
    }

    /**
     * Récupère les notes d'un utilisateur Vicidial
     */
    public function getUserNotes(string $vicidialUser, ?int $limit = null): array
    {
        $notes = $this->noteRepository->findByVicidialUser($vicidialUser, $limit);
        return $this->serializeCollection($notes);
    }

    /**
     * Récupère les notes d'un rendez-vous
     */
    public function getAppointmentNotes(Appointment $appointment): array
    {
        $notes = $this->noteRepository->findBy(
            ['appointment' => $appointment],
            ['createdAt' => 'DESC']
        );

        return $this->serializeCollection($notes);
    }

    public function searchNotes(string $query, int $limit = 10): array
    {
        $notes = $this->noteRepository->searchByContent($query, $limit);
        return $this->serializeCollection($notes);
    }

    public function markAsImportant(Note $note): array
    {
        $note->setIsImportant(true)
             ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->serialize($note);
    }

    public function unmarkAsImportant(Note $note): array
    {
        $note->setIsImportant(false)
             ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->serialize($note);
    }

    public function getImportantNotes(string $vicidialUser): array
    {
        $notes = $this->noteRepository->findBy(
            ['vicidialUser' => $vicidialUser, 'isImportant' => true],
            ['createdAt' => 'DESC']
        );

        return $this->serializeCollection($notes);
    }

    public function getNotesStats(?string $vicidialUser = null): array
    {
        return [
            'total' => $this->noteRepository->countUserNotes($vicidialUser),
            'important' => $this->noteRepository->countImportantNotes($vicidialUser),
            'today' => $this->noteRepository->countTodayNotes($vicidialUser),
        ];
    }

    private function validate(Note $note): void
    {
        $errors = $this->validator->validate($note);

        if (count($errors) > 0) {
            throw new BadRequestHttpException((string) $errors);
        }
    }

    private function serialize(Note $note): array
    {
        return json_decode(
            $this->serializer->serialize($note, 'json', ['groups' => 'note:read']),
            true
        );
    }

    private function serializeCollection(array $notes): array
    {
        return json_decode(
            $this->serializer->serialize($notes, 'json', ['groups' => 'note:read']),
            true
        );
    }
}