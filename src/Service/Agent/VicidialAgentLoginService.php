<?php

namespace App\Service\Agent;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VicidialAgentLoginService
{
    public function __construct(
        private Connection $vicidialConnection,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $vicidialApiUrl,
        private string $vicidialApiUser,
        private string $vicidialApiPass
    ) {
    }

    public function loginAgent(
        string $agentUser,
        string $phoneLogin,
        string $phonePass,
        string $campaign
    ): array {
        try {
            $sql = "
                SELECT user, pass, full_name
                FROM vicidial_users
                WHERE user = :user
                LIMIT 1
            ";

            $agent = $this->vicidialConnection->fetchAssociative($sql, [
                'user' => $agentUser,
            ]);

            if (!$agent) {
                return [
                    'success' => false,
                    'message' => 'Agent introuvable dans vicidial_users',
                ];
            }

            $response = $this->httpClient->request('GET', $this->vicidialApiUrl, [
                'query' => [
                    'source'      => 'crm',
                    'function'    => 'agent_login',
                    'user'        => $this->vicidialApiUser,
                    'pass'        => $this->vicidialApiPass,
                    'agent_user'  => $agent['user'],
                    'agent_pass'  => $agent['pass'],
                    'phone_login' => $phoneLogin,
                    'phone_pass'  => $phonePass,
                    'campaign'    => $campaign,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $content = trim($response->getContent(false));

            $this->logger->info('Réponse login agent VICIdial', [
                'status_code' => $statusCode,
                'agent_user' => $agent['user'],
                'phone_login' => $phoneLogin,
                'campaign' => $campaign,
                'vicidial_response' => $content,
            ]);

            $success = $statusCode === 200 && $content !== '';

            return [
                'success' => $success,
                'message' => $success
                    ? 'Login agent VICIdial réussi'
                    : 'Login agent VICIdial échoué ou fonction absente',
                'agent_user' => $agent['user'],
                'full_name' => $agent['full_name'] ?? null,
                'vicidial_response' => $content,
                'status_code' => $statusCode,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Erreur loginAgent VICIdial', [
                'message' => $e->getMessage(),
                'agent_user' => $agentUser,
                'phone_login' => $phoneLogin,
                'campaign' => $campaign,
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du login agent VICIdial',
                'error' => $e->getMessage(),
            ];
        }
    }
}