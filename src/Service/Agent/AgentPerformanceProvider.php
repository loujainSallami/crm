<?php

namespace App\Service\Agent;

final class AgentPerformanceProvider
{
    public function __construct(
        private string $dataMode,
        private MockAgentPerformanceProvider $mockProvider,
        // plus tard: private RealAgentPerformanceProvider $realProvider
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        if ($this->dataMode === 'mock') {
            return $this->mockProvider->fetch();
        }

        // TODO: branch "real" quand tu auras accès serveur / API
        throw new \RuntimeException("DATA_MODE=real n'est pas encore implémenté.");
    }
}