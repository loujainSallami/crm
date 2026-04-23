<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class VicidialAgentApiService
{
    private const DEFAULT_CAMPAIGN = 'CAMP1';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly Connection $vicidialConnection,
        private readonly string $agentApiUrl,
        private readonly string $apiUser,
        private readonly string $apiPass,
        private readonly string $source,
    ) {}

    private function baseQuery(string $function, array $extra = []): array
    {
        return array_merge([
            'source' => $this->source,
            'user' => $this->apiUser,
            'pass' => $this->apiPass,
            'function' => $function,
        ], $extra);
    }

    private function requestVicidial(array $query): string
    {
        try {
            $response = $this->client->request('GET', rtrim($this->agentApiUrl, '/'), [
                'query' => $query,
                'timeout' => 10,
            ]);

            return $response->getContent(false);
        } catch (TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface $e) {
            throw new \RuntimeException('Vicidial Agent API error: ' . $e->getMessage());
        }
    }

    private function response(string $content): array
    {
        return [
            'success' => str_contains($content, 'SUCCESS'),
            'raw' => trim($content),
        ];
    }

    private function randomUniqueId(): string
    {
        return time() . '.' . random_int(10000, 99999);
    }

    private function randomCallerId(): string
    {
        return 'CID' . random_int(10000000, 99999999);
    }

    private function randomChannel(): string
    {
        return 'SIP/' . random_int(1000, 9999) . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
    }

    private function randomExtension(): string
    {
        return (string) random_int(8000, 8999);
    }

    // ======================
    // API VICIDIAL DIRECTE
    // ======================

    public function agentStatus(string $agentUser): array
    {
        return $this->response($this->requestVicidial(
            $this->baseQuery('agent_status', [
                'agent_user' => $agentUser,
            ])
        ));
    }

    public function pause(string $agentUser, string $pauseCode = 'PAUSE'): array
{
    $api = $this->response($this->requestVicidial(
        $this->baseQuery('pause', [
            'agent_user' => $agentUser,
            'value' => $pauseCode,
        ])
    ));

    $this->vicidialConnection->executeStatement(
        "
        UPDATE vicidial_live_agents
        SET status = 'PAUSED',
            comments = :comments,
            last_update_time = NOW()
        WHERE user = :agentUser
        ",
        [
            'comments' => 'PAUSE:' . $pauseCode,
            'agentUser' => $agentUser,
        ]
    );

    return [
        'success' => true,
        'apiStatus' => $api,
        'liveAgent' => $this->getLiveAgent($agentUser),
        'lead' => $this->getLeadByAgent($agentUser),
    ];
}
    public function resume(string $agentUser): array
    {
        $api = $this->response($this->requestVicidial(
            $this->baseQuery('resume', [
                'agent_user' => $agentUser,
            ])
        ));
    
        $liveAgent = $this->getLiveAgent($agentUser);
        $newStatus = ($liveAgent && (int)($liveAgent['lead_id'] ?? 0) > 0) ? 'INCALL' : 'READY';
    
        $this->vicidialConnection->executeStatement(
            "
            UPDATE vicidial_live_agents
            SET status = :status,
                comments = 'RESUME',
                last_update_time = NOW()
            WHERE user = :agentUser
            ",
            [
                'status' => $newStatus,
                'agentUser' => $agentUser,
            ]
        );
    
        return [
            'success' => true,
            'apiStatus' => $api,
            'liveAgent' => $this->getLiveAgent($agentUser),
            'lead' => $this->getLeadByAgent($agentUser),
        ];
    }
    public function externalDial(string $agentUser, string $phoneNumber, string $campaign): array
    {
        return $this->response($this->requestVicidial(
            $this->baseQuery('external_dial', [
                'agent_user' => $agentUser,
                'value' => $phoneNumber,
                'campaign' => $campaign,
            ])
        ));
    }

    public function hangup(string $agentUser): array
    {
        // Appel API si disponible
        $api = $this->response($this->requestVicidial(
            $this->baseQuery('hangup', [
                'agent_user' => $agentUser,
            ])
        ));

        // Mise à jour DB locale pour garantir l'effet UI
        return $this->hangupDb($agentUser, $api);
    }

    public function transfer(string $agentUser, string $extension): array
    {
        return $this->response($this->requestVicidial(
            $this->baseQuery('transfer', [
                'agent_user' => $agentUser,
                'value' => $extension,
            ])
        ));
    }

    public function getNextCall(string $agentUser): array
    {
        // Dans ton environnement simulé, on pilote par la DB
        return $this->nextCallFromDb($agentUser, self::DEFAULT_CAMPAIGN);
    }

    public function setDisposition(string $agentUser, string $status): array
    {
        return $this->response($this->requestVicidial(
            $this->baseQuery('dispo', [
                'agent_user' => $agentUser,
                'value' => $status,
            ])
        ));
    }

    public function logout(string $agentUser): array
    {
        $api = $this->response($this->requestVicidial(
            $this->baseQuery('logout', [
                'agent_user' => $agentUser,
                'value' => 'LOGOUT',
            ])
        ));

        $this->vicidialConnection->executeStatement(
            "
            UPDATE vicidial_live_agents
            SET status = 'LOGOUT',
                lead_id = 0,
                comments = 'LOGOUT',
                uniqueid = NULL,
                last_update_time = NOW()
            WHERE user = :agentUser
            ",
            ['agentUser' => $agentUser]
        );

        $this->vicidialConnection->executeStatement(
            "
            DELETE FROM vicidial_auto_calls
            WHERE agent_grab = :agentUser
            ",
            ['agentUser' => $agentUser]
        );

        return $api;
    }

    // ======================
    // DBAL VICIDIAL
    // ======================

    public function getLiveAgent(string $agentUser): ?array
    {
        $sql = "
            SELECT user, status, campaign_id, lead_id, last_call_time, last_update_time
            FROM vicidial_live_agents
            WHERE user = :agentUser
            LIMIT 1
        ";

        $row = $this->vicidialConnection->fetchAssociative($sql, [
            'agentUser' => $agentUser,
        ]);

        return $row ?: null;
    }

    public function getLeadByAgent(string $agentUser): ?array
    {
        $sql = "
            SELECT
                vl.lead_id,
                vl.first_name,
                vl.last_name,
                vl.phone_number,
                vl.address1,
                vl.city,
                vl.state,
                vl.postal_code,
                vl.comments
            FROM vicidial_live_agents vla
            INNER JOIN vicidial_list vl ON vl.lead_id = vla.lead_id
            WHERE vla.user = :agentUser
              AND vla.lead_id > 0
            LIMIT 1
        ";

        $row = $this->vicidialConnection->fetchAssociative($sql, [
            'agentUser' => $agentUser,
        ]);

        return $row ?: null;
    }

    public function getAgentDashboard(string $agentUser): array
    {
        $apiStatus = $this->agentStatus($agentUser);
        $liveAgent = $this->getLiveAgent($agentUser);
        $lead = $this->getLeadByAgent($agentUser);

        return [
            'success' => $apiStatus['success'] || $liveAgent !== null,
            'agentUser' => $agentUser,
            'apiStatus' => $apiStatus,
            'liveAgent' => $liveAgent,
            'lead' => $lead,
        ];
    }

    public function debugConnections(): array
    {
        $db = $this->vicidialConnection->fetchAssociative('SELECT DATABASE() AS db');

        return [
            'vicidial_db' => $db,
            'agent_api_url' => $this->agentApiUrl,
            'source' => $this->source,
            'api_user' => $this->apiUser,
        ];
    }

    // ======================
    // LOGIQUE DB POUR UI
    // ======================

    public function nextCallFromDb(string $agentUser, string $campaignId = self::DEFAULT_CAMPAIGN): array
    {
        $this->vicidialConnection->beginTransaction();

        try {
            // 1. Vérifier que l'agent existe
            $liveAgent = $this->getLiveAgent($agentUser);
            if (!$liveAgent) {
                throw new \RuntimeException("Agent {$agentUser} introuvable dans vicidial_live_agents");
            }

            // 2. Prendre le prochain lead du hopper
            $lead = $this->vicidialConnection->fetchAssociative(
                "
                SELECT
                    vh.lead_id,
                    vh.list_id,
                    vh.campaign_id,
                    vl.phone_number
                FROM vicidial_hopper vh
                INNER JOIN vicidial_list vl ON vl.lead_id = vh.lead_id
                WHERE vh.campaign_id = :campaignId
                ORDER BY vh.lead_id ASC
                LIMIT 1
                ",
                ['campaignId' => $campaignId]
            );

            if (!$lead) {
                $this->vicidialConnection->rollBack();

                return [
                    'success' => false,
                    'message' => 'Aucun lead disponible dans le hopper',
                ];
            }

            $uniqueid = $this->randomUniqueId();

            // 3. Supprimer l'ancien auto_call de l'agent
            $this->vicidialConnection->executeStatement(
                "
                DELETE FROM vicidial_auto_calls
                WHERE campaign_id = :campaignId
                  AND agent_grab = :agentUser
                ",
                [
                    'campaignId' => $campaignId,
                    'agentUser' => $agentUser,
                ]
            );

            // 4. Mettre à jour l'agent live
            $this->vicidialConnection->executeStatement(
                "
                UPDATE vicidial_live_agents
                SET lead_id = :leadId,
                    campaign_id = :campaignId,
                    status = 'INCALL',
                    uniqueid = :uniqueid,
                    last_call_time = NOW(),
                    last_update_time = NOW(),
                    comments = 'NEXT_CALL'
                WHERE user = :agentUser
                ",
                [
                    'leadId' => (int) $lead['lead_id'],
                    'campaignId' => $campaignId,
                    'uniqueid' => $uniqueid,
                    'agentUser' => $agentUser,
                ]
            );

            // 5. Créer un auto_call cohérent
            $this->vicidialConnection->insert('vicidial_auto_calls', [
                'server_ip' => '127.0.0.1',
                'campaign_id' => $campaignId,
                'status' => 'LIVE',
                'lead_id' => (int) $lead['lead_id'],
                'uniqueid' => $uniqueid,
                'callerid' => $this->randomCallerId(),
                'channel' => $this->randomChannel(),
                'phone_code' => '216',
                'phone_number' => $lead['phone_number'],
                'call_time' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'call_type' => 'OUT',
                'stage' => 'START',
                'alt_dial' => 'NONE',
                'queue_priority' => 1,
                'agent_only' => '',
                'agent_grab' => $agentUser,
                'queue_position' => 1,
                'extension' => $this->randomExtension(),
                'agent_grab_extension' => '',
            ]);

            // 6. Retirer le lead du hopper
            $this->vicidialConnection->executeStatement(
                "
                DELETE FROM vicidial_hopper
                WHERE campaign_id = :campaignId
                  AND lead_id = :leadId
                ",
                [
                    'campaignId' => $campaignId,
                    'leadId' => (int) $lead['lead_id'],
                ]
            );

            $this->vicidialConnection->commit();

            return [
                'success' => true,
                'message' => 'Appel suivant affecté',
                'agentUser' => $agentUser,
                'lead_id' => (int) $lead['lead_id'],
                'liveAgent' => $this->getLiveAgent($agentUser),
                'lead' => $this->getLeadByAgent($agentUser),
            ];
        } catch (\Throwable $e) {
            if ($this->vicidialConnection->isTransactionActive()) {
                $this->vicidialConnection->rollBack();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function hangupDb(string $agentUser, ?array $apiResponse = null): array
    {
        $this->vicidialConnection->beginTransaction();

        try {
            $liveAgent = $this->getLiveAgent($agentUser);
            $previousLeadId = $liveAgent['lead_id'] ?? 0;

            $this->vicidialConnection->executeStatement(
                "
                UPDATE vicidial_live_agents
                SET lead_id = 0,
                    status = 'READY',
                    uniqueid = NULL,
                    comments = 'HANGUP',
                    last_update_time = NOW()
                WHERE user = :agentUser
                ",
                ['agentUser' => $agentUser]
            );

            $this->vicidialConnection->executeStatement(
                "
                DELETE FROM vicidial_auto_calls
                WHERE agent_grab = :agentUser
                ",
                ['agentUser' => $agentUser]
            );

            $this->vicidialConnection->commit();

            return [
                'success' => true,
                'message' => 'Hangup effectué',
                'previousLeadId' => (int) $previousLeadId,
                'apiStatus' => $apiResponse,
                'liveAgent' => $this->getLiveAgent($agentUser),
                'lead' => $this->getLeadByAgent($agentUser),
            ];
        } catch (\Throwable $e) {
            if ($this->vicidialConnection->isTransactionActive()) {
                $this->vicidialConnection->rollBack();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}