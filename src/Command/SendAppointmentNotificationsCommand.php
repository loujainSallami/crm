<?php

namespace App\Command;

use App\Entity\Appointment;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;

#[AsCommand(
    name: 'app:send-appointment-notifications',
    description: 'Envoie des notifications pour les rendez-vous à venir.'
)]
class SendAppointmentNotificationsCommand extends Command
{
    private EntityManagerInterface $em;
    private NotificationService $notificationService;

    public function __construct(EntityManagerInterface $em, NotificationService $notificationService)
    {
        parent::__construct();
        $this->em = $em;
        $this->notificationService = $notificationService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTime();
        $soon = (clone $now)->modify('+1 day'); // prochaines 24h

        $appointments = $this->em->getRepository(Appointment::class)
            ->createQueryBuilder('a')
            ->where('a.startTime > :now AND a.startTime <= :soon')  // Correction : exclure les rendez-vous passés ou en cours
            ->setParameter('now', $now)
            ->setParameter('soon', $soon)
            ->getQuery()
            ->getResult();

        foreach ($appointments as $appointment) {
            $message = sprintf(
                "Rendez-vous bientôt : %s à %s",
                $appointment->getDescription(),
                $appointment->getStartTime()->format('d/m/Y H:i')
            );

            $this->notificationService->createNotification(
                $appointment->getUser(),
                $message,
                $appointment->getId()
            );

            $output->writeln("Notification envoyée pour RDV #" . $appointment->getId());
        }

        return Command::SUCCESS;
    }
}
