<?php

namespace App\Service\Users\Phone;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse; 
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;



class PhoneService
{
    private Connection $connection;
    
    private HttpClientInterface $client;
    private string $apiUrl;
    private string $apiUser;
    private string $apiPass;
    private string $source;
    

    public function __construct(
        Connection $connection,
        HttpClientInterface $client,
        string $apiUrl,
        string $apiUser,
        string $apiPass,
        string $source
    ) {
        $this->client = $client;
        $this->apiUrl = $apiUrl;
        $this->apiUser = $apiUser;
        $this->apiPass = $apiPass;
        $this->source = $source;
        $this->connection = $connection;

    }


/**
 * Met à jour un téléphone directement dans la base de données.
 */
public function updatePhone(array $data): JsonResponse
{
    try {
        // Valider les champs requis
        $this->validateListData($data, [
            'phone_extension', 'server_ip', 'protocol', 'phone_login', 
            'phone_pass', 'dialplan_number', 'voicemail_id', 
            'phone_full_name', 'local_gmt', 'outbound_cid'
        ]);

        // Préparer les paramètres pour l'API
        $params = [
            'function' => 'update_phone',
            'user' => $this->apiUser,
            'pass' => $this->apiPass,
            'source' => $this->source,
            'extension' => $data['phone_extension'],
            'phone_login' => $data['phone_login'],
            'phone_pass' => $data['phone_pass'],
            'dialplan_number' => $data['dialplan_number'],
            'voicemail_id' => $data['voicemail_id'],
            'server_ip' => $data['server_ip'],
            'protocol' => $data['protocol'],
            'registration_password' => $data['phone_pass'], // Utilisé comme `registration_password`
            'phone_full_name' => $data['phone_full_name'],
            'local_gmt' => $data['local_gmt'],
            'outbound_cid' => $data['outbound_cid'],
        ];

        // Appel de l'API avec HttpClient
        $response = $this->client->request('GET', $this->apiUrl, ['query' => $params]);

        // Vérifier la réponse
        $statusCode = $response->getStatusCode();
        $responseData = $response->getContent();

        if ($statusCode === 200 && str_contains($responseData, 'SUCCESS')) {
            return new JsonResponse(['success' => true, 'message' => 'Phone updated successfully'], JsonResponse::HTTP_OK);
        }

        // Gérer les erreurs renvoyées par l'API
        return new JsonResponse(['error' => 'API error', 'message' => $responseData], JsonResponse::HTTP_BAD_REQUEST);

    } catch (\InvalidArgumentException $e) {
        return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
    } catch (\Exception $e) {
        return new JsonResponse(['error' => 'Failed to update phone', 'message' => $e->getMessage()], 500);
    }
}



    /**
     * Récupère tous les téléphones depuis la base de données.
     */
    public function getPhones(): JsonResponse
    {
        try {
            $phones = $this->connection->fetchAllAssociative("
                SELECT 
                    extension AS phone_extension, 
                    server_ip, 
                    protocol, 
                    login, 
                    pass AS phone_pass, 
                     
                    dialplan_number, 
                    status, 
                    fullname
                FROM phones
            ");

            return new JsonResponse($phones, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch phones', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Ajoute un téléphone directement dans la base de données.
     */
    public function addPhone(array $data): JsonResponse
{
    try {
        // Valider les champs requis
        $this->validateListData($data, [
            'phone_extension', 'server_ip', 'protocol', 'phone_login', 
            'phone_pass', 'dialplan_number', 'voicemail_id', 
            'phone_full_name', 'local_gmt', 'outbound_cid'
        ]);

        // Préparer les paramètres pour l'API
        $params = [
            'function' => 'add_phone',
            'user' => $this->apiUser,
            'pass' => $this->apiPass,
            'source' => $this->source,
            'extension' => $data['phone_extension'],
            'phone_login' => $data['phone_login'],
            'phone_pass' => $data['phone_pass'],
            'dialplan_number' => $data['dialplan_number'],
            'voicemail_id' => $data['voicemail_id'],
            'server_ip' => $data['server_ip'],
            'protocol' => $data['protocol'],
            'registration_password' => $data['registration_password'] ?? 'default_password',
            'phone_full_name' => $data['phone_full_name'],
            'local_gmt' => $data['local_gmt'],
            'outbound_cid' => $data['outbound_cid'],
        ];

        // Appel de l'API avec HttpClient
        $response = $this->client->request('GET', $this->apiUrl, ['query' => $params]);

        // Vérifier la réponse
        $statusCode = $response->getStatusCode();
        $responseData = $response->getContent();

        if ($statusCode === 200 && str_contains($responseData, 'SUCCESS')) {
            return new JsonResponse(['success' => true, 'message' => 'Phone added successfully'], JsonResponse::HTTP_OK);
        }

        // Gérer les erreurs renvoyées par l'API
        return new JsonResponse(['error' => 'API error', 'message' => $responseData], JsonResponse::HTTP_BAD_REQUEST);

    } catch (\InvalidArgumentException $e) {
        return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
    } catch (\Exception $e) {
        return new JsonResponse(['error' => 'Failed to add phone', 'message' => $e->getMessage()], 500);
    }
}




 /**
 * Supprime un téléphone de la base de données par extension et server_ip.
 */
public function deletePhonePhysically(string $extension, string $serverIp): JsonResponse
{
    try {
        $this->connection->executeQuery("DELETE FROM phones WHERE extension = ? AND server_ip = ?", [$extension, $serverIp]);

        return new JsonResponse(['success' => true, 'message' => 'Phone physically deleted'], JsonResponse::HTTP_OK);
    } catch (\Exception $e) {
        return new JsonResponse(['error' => 'Failed to delete phone', 'message' => $e->getMessage()], 500);
    }
}


    /**
     * Valide les champs requis.
     */
    private function validateListData(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Le champ '{$field}' est obligatoire.");
            }
        }
    }
}

    


    
