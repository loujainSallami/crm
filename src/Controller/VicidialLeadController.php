<?php

namespace App\Controller;

use App\Repository\VicidiailLeadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\VicidialLead;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
#[Route('/api/vicidial-leads')]
class VicidialLeadController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private VicidiailLeadRepository $leadRepository;

    public function __construct(EntityManagerInterface $entityManager, VicidiailLeadRepository $leadRepository)
    {
        $this->entityManager = $entityManager;
        $this->leadRepository = $leadRepository;
    }

    #[Route('/getAllLeads', name: 'get_all_leads', methods: ['GET'])]
    public function getAllLeads(): JsonResponse
    {
        $leads = $this->leadRepository->findAll();

        if (empty($leads)) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Aucun lead trouvé.'
            ], Response::HTTP_NOT_FOUND); // ✅ utilisation correcte de Response
        }

        // Nettoyage des données pour éviter les références circulaires
        $data = array_map(function (VicidialLead $lead) {
            return [
                'id'          => $lead->getId(),
                'firstName'   => $lead->getFirstName(),
                'lastName'    => $lead->getLastName(),
                'phoneNumber' => $lead->getPhoneNumber(),
            ];
        }, $leads);

        return new JsonResponse($data, Response::HTTP_OK);
    }



    #[Route('', name: 'vicidial_lead_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $lead = new VicidialLead();
        // Assurez-vous de valider et de mapper les données ici
        $lead->setFirstName($data['first_name']);
        $lead->setLastName($data['last_name']);
        $lead->setPhoneNumber($data['phone_number']);
        $lead->setEmail($data['email']);
        // Ajoutez d'autres propriétés si nécessaire

        $this->vicidialLeadService->createLead($lead);

        return new JsonResponse(
            $this->serializer->serialize($lead, 'json', ['groups' => 'vicidial_lead:read']),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'vicidial_lead_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $lead = $this->vicidialLeadService->getLeadById($id); // Vous devez ajouter cette méthode dans le service

        return new JsonResponse(
            $this->serializer->serialize($lead, 'json', ['groups' => 'vicidial_lead:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'vicidial_lead_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $lead = $this->vicidialLeadService->getLeadById($id); // Vous devez ajouter cette méthode dans le service
        $data = json_decode($request->getContent(), true);

        // Mettez à jour les propriétés du lead
        $lead->setFirstName($data['first_name']);
        $lead->setLastName($data['last_name']);
        $lead->setPhoneNumber($data['phone_number']);
        $lead->setEmail($data['email']);
        // Ajoutez d'autres propriétés si nécessaire

        $this->vicidialLeadService->updateLead($lead);

        return new JsonResponse(
            $this->serializer->serialize($lead, 'json', ['groups' => 'vicidial_lead:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'vicidial_lead_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $lead = $this->vicidialLeadService->getLeadById($id); // Vous devez ajouter cette méthode dans le service
        $this->vicidialLeadService->deleteLead($lead);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    
}
