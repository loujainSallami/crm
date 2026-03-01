<?php

namespace App\Service;

use App\Entity\Vicidial\CrmLead;
use Doctrine\Persistence\ManagerRegistry;

class CrmLeadService
{
    private $em;

    public function __construct(private readonly ManagerRegistry $doctrine)
    {
        // ✅ IMPORTANT : on force l'EntityManager vicidial
        $this->em = $this->doctrine->getManager('vicidial');
    }

    public function getAllLeads(): array
    {
        $leads = $this->em->getRepository(CrmLead::class)->findAll();

        return array_map(fn(CrmLead $l) => [
            'id' => $l->getId(),
            'firstName' => $l->getFirstName(),
            'lastName' => $l->getLastName(),
            'phoneNumber' => $l->getPhoneNumber(),
            'email' => $l->getEmail(),
            'campaign' => $l->getCampaign()?->getId(),
        ], $leads);
    }

    public function getLeadById(int $id): ?array
    {
        $lead = $this->em->getRepository(CrmLead::class)->find($id);
        if (!$lead) return null;

        return [
            'id' => $lead->getId(),
            'firstName' => $lead->getFirstName(),
            'lastName' => $lead->getLastName(),
            'phoneNumber' => $lead->getPhoneNumber(),
            'email' => $lead->getEmail(),
            'campaign' => $lead->getCampaign()?->getId(),
        ];
    }

    public function createLead(array $data): array
    {
        $lead = new CrmLead();
        $lead->setFirstName($data['first_name'] ?? null);
        $lead->setLastName($data['last_name'] ?? null);
        $lead->setPhoneNumber($data['phone_number'] ?? null);
        $lead->setEmail($data['email'] ?? null);

        $this->em->persist($lead);
        $this->em->flush();

        return [
            'id' => $lead->getId(),
            'firstName' => $lead->getFirstName(),
            'lastName' => $lead->getLastName(),
            'phoneNumber' => $lead->getPhoneNumber(),
            'email' => $lead->getEmail(),
        ];
    }

    public function updateLead(int $id, array $data): ?array
    {
        $lead = $this->em->getRepository(CrmLead::class)->find($id);
        if (!$lead) return null;

        $lead->setFirstName($data['first_name'] ?? $lead->getFirstName());
        $lead->setLastName($data['last_name'] ?? $lead->getLastName());
        $lead->setPhoneNumber($data['phone_number'] ?? $lead->getPhoneNumber());
        $lead->setEmail($data['email'] ?? $lead->getEmail());

        $this->em->flush();

        return [
            'id' => $lead->getId(),
            'firstName' => $lead->getFirstName(),
            'lastName' => $lead->getLastName(),
            'phoneNumber' => $lead->getPhoneNumber(),
            'email' => $lead->getEmail(),
        ];
    }

    public function deleteLead(int $id): bool
    {
        $lead = $this->em->getRepository(CrmLead::class)->find($id);
        if (!$lead) return false;

        $this->em->remove($lead);
        $this->em->flush();

        return true;
    }
}