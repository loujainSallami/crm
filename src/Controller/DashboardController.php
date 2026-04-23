<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    private function getDatamartConnection(ManagerRegistry $doctrine): Connection
    {
        return $doctrine->getConnection('Datamart');
    }

    private function buildFilters(Request $request): array
    {
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $agentUser = $request->query->get('agentUser');
        $campaignId = $request->query->get('campaignId');

        $conditions = [];
        $params = [];

        if ($startDate) {
            $conditions[] = 'd.date >= :startDate';
            $params['startDate'] = $startDate;
        }

        if ($endDate) {
            $conditions[] = 'd.date <= :endDate';
            $params['endDate'] = $endDate;
        }

        if ($agentUser) {
            $conditions[] = 'a.user = :agentUser';
            $params['agentUser'] = $agentUser;
        }

        if ($campaignId) {
            $conditions[] = 'f.campaign_id = :campaignId';
            $params['campaignId'] = $campaignId;
        }

        $where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return [
            'where' => $where,
            'params' => $params,
        ];
    }

    private function buildAgentFilters(Request $request): array
    {
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $agentUser = $request->query->get('agentUser');
        $campaignId = $request->query->get('campaignId');

        $conditions = [];
        $params = [];

        if ($startDate) {
            $conditions[] = 'd.date >= :startDate';
            $params['startDate'] = $startDate;
        }

        if ($endDate) {
            $conditions[] = 'd.date <= :endDate';
            $params['endDate'] = $endDate;
        }

        if ($agentUser) {
            $conditions[] = 'a.user = :agentUser';
            $params['agentUser'] = $agentUser;
        }

        if ($campaignId) {
            $conditions[] = 'faa.campaign_id = :campaignId';
            $params['campaignId'] = $campaignId;
        }

        $where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return [
            'where' => $where,
            'params' => $params,
        ];
    }

    private function buildLiveFilters(Request $request): array
    {
        $agentUser = $request->query->get('agentUser');
        $campaignId = $request->query->get('campaignId');

        $conditions = [];
        $params = [];

        if ($agentUser) {
            $conditions[] = 'a.user = :agentUser';
            $params['agentUser'] = $agentUser;
        }

        if ($campaignId) {
            $conditions[] = 'fls.campaign_id = :campaignId';
            $params['campaignId'] = $campaignId;
        }

        $where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return [
            'where' => $where,
            'params' => $params,
        ];
    }

    #[Route('/api/dashboard/overview-summary', name: 'api_dashboard_overview_summary', methods: ['GET'])]
    public function overviewSummary(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $connection = $this->getDatamartConnection($doctrine);
        $filter = $this->buildFilters($request);

        $row = $connection->fetchAssociative("
            SELECT
                COALESCE(SUM(f.call_count), 0) AS total_calls,
                COALESCE(SUM(CASE WHEN f.call_type = 'OUTBOUND' THEN f.call_count ELSE 0 END), 0) AS total_outbound_calls,
                COALESCE(SUM(CASE WHEN f.call_type = 'INBOUND' THEN f.call_count ELSE 0 END), 0) AS total_inbound_calls,
                COALESCE(COUNT(DISTINCT f.lead_id), 0) AS total_leads,
                COALESCE(COUNT(DISTINCT f.agent_id), 0) AS total_agents_actifs,
                COALESCE(COUNT(DISTINCT f.campaign_id), 0) AS total_campagnes_actives,
                COALESCE(ROUND(AVG(f.total_duration), 2), 0) AS avg_call_duration_sec,
                COALESCE(ROUND(AVG(f.queue_seconds), 2), 0) AS avg_wait_sec,
                COALESCE(
                    ROUND(
                        SUM(CASE WHEN s.status IN ('SALE', 'CALLBK') THEN f.call_count ELSE 0 END)
                        / NULLIF(SUM(f.call_count), 0) * 100, 2
                    ),
                0) AS contact_rate,
                COALESCE(
                    ROUND(
                        SUM(CASE WHEN s.status = 'SALE' THEN f.call_count ELSE 0 END)
                        / NULLIF(SUM(f.call_count), 0) * 100, 2
                    ),
                0) AS conversion_rate,
                COALESCE(
                    ROUND(
                        SUM(CASE WHEN s.status IN ('DROP', 'AB', 'ABANDON') THEN f.call_count ELSE 0 END)
                        / NULLIF(SUM(f.call_count), 0) * 100, 2
                    ),
                0) AS abandon_rate
            FROM fact_calls f
            JOIN dim_date d ON f.date_id = d.date_id
            JOIN dim_agent a ON f.agent_id = a.agent_id
            JOIN dim_campaign c ON f.campaign_id = c.campaign_id
            LEFT JOIN dim_status s ON f.status_id = s.status_id
            {$filter['where']}
        ", $filter['params']);

        return $this->json([
            'total_calls' => (int) ($row['total_calls'] ?? 0),
            'total_outbound_calls' => (int) ($row['total_outbound_calls'] ?? 0),
            'total_inbound_calls' => (int) ($row['total_inbound_calls'] ?? 0),
            'total_leads' => (int) ($row['total_leads'] ?? 0),
            'total_agents_actifs' => (int) ($row['total_agents_actifs'] ?? 0),
            'total_campagnes_actives' => (int) ($row['total_campagnes_actives'] ?? 0),
            'avg_call_duration_sec' => (float) ($row['avg_call_duration_sec'] ?? 0),
            'avg_wait_sec' => (float) ($row['avg_wait_sec'] ?? 0),
            'contact_rate' => (float) ($row['contact_rate'] ?? 0),
            'conversion_rate' => (float) ($row['conversion_rate'] ?? 0),
            'abandon_rate' => (float) ($row['abandon_rate'] ?? 0),
        ]);
    }

    #[Route('/api/dashboard/status', name: 'api_dashboard_status', methods: ['GET'])]
    public function status(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $connection = $this->getDatamartConnection($doctrine);
        $filter = $this->buildFilters($request);

        $data = $connection->fetchAllAssociative("
            SELECT
                s.status,
                SUM(f.call_count) AS total_calls
            FROM fact_calls f
            JOIN dim_status s ON f.status_id = s.status_id
            JOIN dim_date d ON f.date_id = d.date_id
            JOIN dim_agent a ON f.agent_id = a.agent_id
            JOIN dim_campaign c ON f.campaign_id = c.campaign_id
            {$filter['where']}
            GROUP BY s.status
            ORDER BY total_calls DESC
        ", $filter['params']);

        $formatted = array_map(fn(array $row) => [
            'status' => $row['status'],
            'total_calls' => (int) $row['total_calls'],
        ], $data);

        return $this->json($formatted);
    }

    #[Route('/api/dashboard/calls-per-day', name: 'api_dashboard_calls_per_day', methods: ['GET'])]
    public function callsPerDay(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $connection = $this->getDatamartConnection($doctrine);
        $filter = $this->buildFilters($request);

        $data = $connection->fetchAllAssociative("
            SELECT
                d.date,
                SUM(f.call_count) AS total_calls
            FROM fact_calls f
            JOIN dim_date d ON f.date_id = d.date_id
            JOIN dim_agent a ON f.agent_id = a.agent_id
            JOIN dim_campaign c ON f.campaign_id = c.campaign_id
            {$filter['where']}
            GROUP BY d.date
            ORDER BY d.date ASC
        ", $filter['params']);

        $formatted = array_map(fn(array $row) => [
            'date' => $row['date'],
            'total_calls' => (int) $row['total_calls'],
        ], $data);

        return $this->json($formatted);
    }

    #[Route('/api/agent-reports/calls-by-agent', name: 'api_agent_reports_calls_by_agent', methods: ['GET'])]
    public function agentCallsByAgent(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $connection = $this->getDatamartConnection($doctrine);
        $filter = $this->buildFilters($request);

        $data = $connection->fetchAllAssociative("
            SELECT
                a.user,
                COALESCE(SUM(f.call_count), 0) AS total_calls,
                COALESCE(SUM(CASE WHEN f.call_type = 'INBOUND' THEN f.call_count ELSE 0 END), 0) AS inbound_calls,
                COALESCE(SUM(CASE WHEN f.call_type = 'OUTBOUND' THEN f.call_count ELSE 0 END), 0) AS outbound_calls,
                COALESCE(SUM(f.total_duration), 0) AS total_conversation_sec,
                COALESCE(ROUND(AVG(f.total_duration), 2), 0) AS avg_conversation_sec
            FROM fact_calls f
            JOIN dim_agent a ON f.agent_id = a.agent_id
            JOIN dim_date d ON f.date_id = d.date_id
            JOIN dim_campaign c ON f.campaign_id = c.campaign_id
            {$filter['where']}
            GROUP BY a.user
            ORDER BY total_calls DESC
        ", $filter['params']);

        $formatted = array_map(fn(array $row) => [
            'user' => $row['user'],
            'total_calls' => (int) $row['total_calls'],
            'inbound_calls' => (int) $row['inbound_calls'],
            'outbound_calls' => (int) $row['outbound_calls'],
            'total_conversation_sec' => (int) $row['total_conversation_sec'],
            'avg_conversation_sec' => (float) $row['avg_conversation_sec'],
        ], $data);

        return $this->json($formatted);
    }

    #[Route('/api/agent-reports/summary', name: 'api_agent_reports_summary', methods: ['GET'])]
    public function agentReportsSummary(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $connection = $this->getDatamartConnection($doctrine);
        $filter = $this->buildAgentFilters($request);

        $data = $connection->fetchAllAssociative("
            SELECT
                a.user,
                COUNT(*) AS total_events,
                COALESCE(SUM(faa.talk_sec), 0) AS total_talk_sec,
                COALESCE(ROUND(AVG(faa.talk_sec), 2), 0) AS avg_talk_sec,
                COALESCE(SUM(faa.wait_sec), 0) AS total_wait_sec,
                COALESCE(SUM(faa.pause_sec), 0) AS total_pause_sec,
                COALESCE(SUM(faa.dispo_sec), 0) AS total_dispo_sec,
                COALESCE(SUM(faa.dead_sec), 0) AS total_dead_sec
            FROM fact_agent_activity faa
            JOIN dim_agent a ON faa.agent_id = a.agent_id
            JOIN dim_date d ON faa.date_id = d.date_id
            {$filter['where']}
            GROUP BY a.user
            ORDER BY total_talk_sec DESC
        ", $filter['params']);

        $formatted = array_map(fn(array $row) => [
            'user' => $row['user'],
            'total_events' => (int) $row['total_events'],
            'total_talk_sec' => (int) $row['total_talk_sec'],
            'avg_talk_sec' => (float) $row['avg_talk_sec'],
            'total_wait_sec' => (int) $row['total_wait_sec'],
            'total_pause_sec' => (int) $row['total_pause_sec'],
            'total_dispo_sec' => (int) $row['total_dispo_sec'],
            'total_dead_sec' => (int) $row['total_dead_sec'],
        ], $data);

        return $this->json($formatted);
    }

    #[Route('/api/agent-reports/live-status', name: 'api_agent_reports_live_status', methods: ['GET'])]
    public function agentLiveStatus(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $connection = $this->getDatamartConnection($doctrine);
        $filter = $this->buildLiveFilters($request);

        $data = $connection->fetchAllAssociative("
            SELECT
                a.user,
                fls.campaign_id,
                fls.status,
                fls.calls_today,
                fls.last_update
            FROM fact_live_snapshot fls
            JOIN dim_agent a ON fls.agent_id = a.agent_id
            {$filter['where']}
            ORDER BY a.user ASC
        ", $filter['params']);

        $formatted = array_map(fn(array $row) => [
            'user' => $row['user'],
            'campaign_id' => $row['campaign_id'],
            'status' => $row['status'],
            'calls_today' => (int) $row['calls_today'],
            'last_update' => $row['last_update'],
        ], $data);

        return $this->json($formatted);
    }

    #[Route('/api/agent-performance', name: 'api_agent_performance', methods: ['GET'])]
    public function agentPerformance(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $connection = $this->getDatamartConnection($doctrine);

        $callFilter = $this->buildFilters($request);
        $activityFilter = $this->buildAgentFilters($request);

        $calls = $connection->fetchAllAssociative("
            SELECT
                a.user,
                COALESCE(SUM(f.call_count), 0) AS total_calls,
                COALESCE(SUM(CASE WHEN s.status = 'SALE' THEN f.call_count ELSE 0 END), 0) AS sale_calls,
                COALESCE(SUM(CASE WHEN s.status = 'CALLBK' THEN f.call_count ELSE 0 END), 0) AS callbk_calls,
                COALESCE(SUM(CASE WHEN s.status = 'NA' THEN f.call_count ELSE 0 END), 0) AS na_calls,
                COALESCE(SUM(CASE WHEN s.status = 'BUSY' THEN f.call_count ELSE 0 END), 0) AS busy_calls,
                COALESCE(SUM(CASE WHEN s.status IN ('DROP', 'AB', 'ABANDON') THEN f.call_count ELSE 0 END), 0) AS drop_calls,
                COALESCE(ROUND(AVG(f.total_duration), 2), 0) AS avg_handling_time
            FROM fact_calls f
            JOIN dim_agent a ON f.agent_id = a.agent_id
            JOIN dim_status s ON f.status_id = s.status_id
            JOIN dim_date d ON f.date_id = d.date_id
            JOIN dim_campaign c ON f.campaign_id = c.campaign_id
            {$callFilter['where']}
            GROUP BY a.user
        ", $callFilter['params']);

        $activity = $connection->fetchAllAssociative("
            SELECT
                a.user,
                COALESCE(SUM(faa.talk_sec), 0) AS total_talk_sec,
                COALESCE(SUM(faa.wait_sec), 0) AS total_wait_sec,
                COALESCE(SUM(faa.pause_sec), 0) AS total_pause_sec,
                COALESCE(SUM(faa.dispo_sec), 0) AS total_dispo_sec,
                COALESCE(SUM(faa.dead_sec), 0) AS total_dead_sec
            FROM fact_agent_activity faa
            JOIN dim_agent a ON faa.agent_id = a.agent_id
            JOIN dim_date d ON faa.date_id = d.date_id
            {$activityFilter['where']}
            GROUP BY a.user
        ", $activityFilter['params']);

        $activityByUser = [];
        foreach ($activity as $row) {
            $activityByUser[$row['user']] = $row;
        }

        $formatted = [];

        foreach ($calls as $row) {
            $user = $row['user'];

            $act = $activityByUser[$user] ?? [
                'total_talk_sec' => 0,
                'total_wait_sec' => 0,
                'total_pause_sec' => 0,
                'total_dispo_sec' => 0,
                'total_dead_sec' => 0,
            ];

            $productiveSec = (int) $act['total_talk_sec'] + (int) $act['total_wait_sec'] + (int) $act['total_dispo_sec'];
            $loggedSec = $productiveSec + (int) $act['total_pause_sec'] + (int) $act['total_dead_sec'];

            $totalCalls = (int) $row['total_calls'];

            $callsPerHour = $productiveSec > 0
                ? round($totalCalls / ($productiveSec / 3600), 2)
                : 0;

            $occupancyRate = $loggedSec > 0
                ? round(($productiveSec / $loggedSec) * 100, 2)
                : 0;

            $pauseRatio = $loggedSec > 0
                ? round(((int) $act['total_pause_sec'] / $loggedSec) * 100, 2)
                : 0;

            $formatted[] = [
                'user' => $user,
                'total_calls' => $totalCalls,
                'conversion_rate' => $totalCalls > 0 ? round(((int) $row['sale_calls'] / $totalCalls) * 100, 2) : 0,
                'calls_per_hour' => $callsPerHour,
                'occupancy_rate' => $occupancyRate,
                'pause_ratio' => $pauseRatio,
                'average_handling_time' => (float) $row['avg_handling_time'],
                'sale_rate' => $totalCalls > 0 ? round(((int) $row['sale_calls'] / $totalCalls) * 100, 2) : 0,
                'callbk_rate' => $totalCalls > 0 ? round(((int) $row['callbk_calls'] / $totalCalls) * 100, 2) : 0,
                'na_rate' => $totalCalls > 0 ? round(((int) $row['na_calls'] / $totalCalls) * 100, 2) : 0,
                'busy_rate' => $totalCalls > 0 ? round(((int) $row['busy_calls'] / $totalCalls) * 100, 2) : 0,
                'drop_rate' => $totalCalls > 0 ? round(((int) $row['drop_calls'] / $totalCalls) * 100, 2) : 0,
            ];
        }

        usort($formatted, fn($a, $b) => $b['total_calls'] <=> $a['total_calls']);

        return $this->json($formatted);
    }

    #[Route('/api/live/summary', name: 'api_live_summary', methods: ['GET'])]
    public function liveSummary(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $connection = $this->getDatamartConnection($doctrine);
        $filter = $this->buildLiveFilters($request);

        $row = $connection->fetchAssociative("
            SELECT
                COALESCE(SUM(CASE WHEN fls.status = 'READY' THEN 1 ELSE 0 END), 0) AS agents_ready,
                COALESCE(SUM(CASE WHEN fls.status = 'INCALL' THEN 1 ELSE 0 END), 0) AS agents_incall,
                COALESCE(SUM(CASE WHEN fls.status = 'PAUSED' THEN 1 ELSE 0 END), 0) AS agents_paused,
                COALESCE(SUM(CASE WHEN fls.status = 'QUEUE' THEN 1 ELSE 0 END), 0) AS agents_queue,
                COALESCE(SUM(CASE WHEN fls.status = 'INCALL' THEN 1 ELSE 0 END), 0) AS calls_in_progress,
                COALESCE(SUM(fls.calls_today), 0) AS calls_today
            FROM fact_live_snapshot fls
            JOIN dim_agent a ON fls.agent_id = a.agent_id
            {$filter['where']}
        ", $filter['params']);

        $metricsWhere = '';
        $metricsParams = [];
        $campaignId = $request->query->get('campaignId');

        if ($campaignId) {
            $metricsWhere = 'WHERE flm.campaign_id = :campaignId';
            $metricsParams['campaignId'] = $campaignId;
        }

        $metrics = $connection->fetchAssociative("
            SELECT
                COALESCE(SUM(flm.leads_in_hopper), 0) AS leads_in_hopper,
                COALESCE(SUM(flm.auto_calls_count), 0) AS auto_calls_count,
                COALESCE(ROUND(AVG(flm.avg_queue_position), 2), 0) AS avg_queue_position
            FROM fact_live_metrics flm
            {$metricsWhere}
        ", $metricsParams);

        return $this->json([
            'agents_ready' => (int) ($row['agents_ready'] ?? 0),
            'agents_incall' => (int) ($row['agents_incall'] ?? 0),
            'agents_paused' => (int) ($row['agents_paused'] ?? 0),
            'agents_queue' => (int) ($row['agents_queue'] ?? 0),
            'calls_in_progress' => (int) ($row['calls_in_progress'] ?? 0),
            'calls_today' => (int) ($row['calls_today'] ?? 0),
            'leads_in_hopper' => (int) ($metrics['leads_in_hopper'] ?? 0),
            'auto_calls_count' => (int) ($metrics['auto_calls_count'] ?? 0),
            'avg_queue_position' => (float) ($metrics['avg_queue_position'] ?? 0),
        ]);
    }

    #[Route('/api/recordings/summary', name: 'api_recordings_summary', methods: ['GET'])]
    public function recordingsSummary(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $connection = $this->getDatamartConnection($doctrine);

        $agentUser = $request->query->get('agentUser');
        $conditions = [];
        $params = [];

        if ($agentUser) {
            $conditions[] = 'a.user = :agentUser';
            $params['agentUser'] = $agentUser;
        }

        $where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $summary = $connection->fetchAssociative("
            SELECT
                COUNT(*) AS total_recordings,
                COALESCE(ROUND(AVG(fr.duration_sec), 2), 0) AS avg_duration_sec,
                COALESCE(SUM(fr.duration_sec), 0) AS total_duration_sec
            FROM fact_recordings fr
            LEFT JOIN dim_agent a ON fr.agent_id = a.agent_id
            {$where}
        ", $params);

        $byAgent = $connection->fetchAllAssociative("
            SELECT
                a.user,
                COUNT(*) AS recordings_count
            FROM fact_recordings fr
            JOIN dim_agent a ON fr.agent_id = a.agent_id
            {$where}
            GROUP BY a.user
            ORDER BY recordings_count DESC
        ", $params);

        return $this->json([
            'total_recordings' => (int) ($summary['total_recordings'] ?? 0),
            'avg_duration_sec' => (float) ($summary['avg_duration_sec'] ?? 0),
            'total_duration_sec' => (int) ($summary['total_duration_sec'] ?? 0),
            'by_agent' => array_map(fn(array $row) => [
                'user' => $row['user'],
                'recordings_count' => (int) $row['recordings_count'],
            ], $byAgent),
        ]);
    }

    #[Route('/api/campaign-analytics', name: 'api_campaign_analytics', methods: ['GET'])]
    public function campaignAnalytics(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $connection = $this->getDatamartConnection($doctrine);
        $filter = $this->buildFilters($request);

        $data = $connection->fetchAllAssociative("
            SELECT
                c.campaign_id,
                c.campaign_name,
                COALESCE(COUNT(DISTINCT f.lead_id), 0) AS leads_count,
                COALESCE(SUM(f.call_count), 0) AS total_calls,
                COALESCE(SUM(CASE WHEN f.call_type = 'OUTBOUND' THEN f.call_count ELSE 0 END), 0) AS outbound_calls,
                COALESCE(SUM(CASE WHEN f.call_type = 'INBOUND' THEN f.call_count ELSE 0 END), 0) AS inbound_calls,
                COALESCE(ROUND(AVG(f.total_duration), 2), 0) AS avg_call_duration_sec,
                COALESCE(SUM(CASE WHEN s.status = 'SALE' THEN f.call_count ELSE 0 END), 0) AS sale_calls,
                COALESCE(SUM(CASE WHEN s.status = 'CALLBK' THEN f.call_count ELSE 0 END), 0) AS callbk_calls,
                COALESCE(SUM(CASE WHEN s.status IN ('SALE', 'CALLBK') THEN f.call_count ELSE 0 END), 0) AS reachable_calls,
                COALESCE(SUM(CASE WHEN s.status IN ('DROP', 'AB', 'ABANDON') THEN f.call_count ELSE 0 END), 0) AS abandoned_calls
            FROM fact_calls f
            JOIN dim_campaign c ON f.campaign_id = c.campaign_id
            JOIN dim_date d ON f.date_id = d.date_id
            JOIN dim_agent a ON f.agent_id = a.agent_id
            LEFT JOIN dim_status s ON f.status_id = s.status_id
            {$filter['where']}
            GROUP BY c.campaign_id, c.campaign_name
            ORDER BY total_calls DESC
        ", $filter['params']);

        $formatted = array_map(function (array $row) {
            $totalCalls = (int) $row['total_calls'];

            return [
                'campaign_id' => $row['campaign_id'],
                'campaign_name' => $row['campaign_name'],
                'leads_count' => (int) $row['leads_count'],
                'total_calls' => $totalCalls,
                'outbound_calls' => (int) $row['outbound_calls'],
                'inbound_calls' => (int) $row['inbound_calls'],
                'avg_call_duration_sec' => (float) $row['avg_call_duration_sec'],
                'conversion_rate' => $totalCalls > 0 ? round(((int) $row['sale_calls'] / $totalCalls) * 100, 2) : 0,
                'reachability_rate' => $totalCalls > 0 ? round(((int) $row['reachable_calls'] / $totalCalls) * 100, 2) : 0,
                'abandon_rate' => $totalCalls > 0 ? round(((int) $row['abandoned_calls'] / $totalCalls) * 100, 2) : 0,
                'sale_rate' => $totalCalls > 0 ? round(((int) $row['sale_calls'] / $totalCalls) * 100, 2) : 0,
                'callbk_rate' => $totalCalls > 0 ? round(((int) $row['callbk_calls'] / $totalCalls) * 100, 2) : 0,
            ];
        }, $data);

        return $this->json($formatted);
    }
}