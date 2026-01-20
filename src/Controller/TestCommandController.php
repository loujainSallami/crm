<?php
// src/Controller/TestCommandController.php
namespace App\Controller;

use App\Command\SendAppointmentNotificationsCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestCommandController extends AbstractController
{
    #[Route('/test-notifications', name: 'test_notifications')]
    public function test(SendAppointmentNotificationsCommand $command): Response
    {
        // Appelle la méthode execute de ta commande
        $command->run(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\BufferedOutput());

        return new Response('Commande exécutée !');
    }
}
