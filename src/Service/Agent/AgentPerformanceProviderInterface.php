<?php

namespace App\Service\Agent;

interface AgentPerformanceProviderInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAgentPerformance(): array;
}