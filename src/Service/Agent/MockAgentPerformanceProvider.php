<?php

namespace App\Service\Agent;

final class MockAgentPerformanceProvider
{
    public function __construct(
        private string $csvPath
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $fullPath = $this->csvPath;

        if (!is_file($fullPath)) {
            throw new \RuntimeException("Mock CSV introuvable: {$fullPath}");
        }

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Impossible d'ouvrir le CSV: {$fullPath}");
        }

        try {
            $header = null;

            // 1) Avancer jusqu'à la ligne d'en-tête réelle
            while (($row = fgetcsv($handle)) !== false) {
                if (!$row || count($row) < 2) {
                    continue;
                }

                // Nettoyage des guillemets/espaces
                $normalized = array_map(
                    fn($v) => trim((string)$v, " \t\n\r\0\x0B\""),
                    $row
                );

                // On détecte l'en-tête réel Vicidial
                if (in_array('USER NAME', $normalized, true) && in_array('ID', $normalized, true)) {
                    $header = $normalized;
                    break;
                }
            }

            if ($header === null) {
                throw new \RuntimeException("Header CSV introuvable (USER NAME / ID).");
            }

            // 2) Lire les lignes data
            $data = [];
            while (($row = fgetcsv($handle)) !== false) {
                if (!$row || count($row) < 2) {
                    continue;
                }

                $normalizedRow = array_map(
                    fn($v) => trim((string)$v, " \t\n\r\0\x0B\""),
                    $row
                );

                // Ignore lignes TOTALS s'il y en a
                $idxUserName = array_search('USER NAME', $header, true);
                if ($idxUserName !== false && isset($normalizedRow[$idxUserName]) && $normalizedRow[$idxUserName] === 'TOTALS') {
                    continue;
                }

                // Associer header -> valeurs
                $assoc = [];
                foreach ($header as $i => $col) {
                    $assoc[$col] = $normalizedRow[$i] ?? null;
                }

                // Validation minimale
                if (empty($assoc['ID'])) {
                    continue;
                }

                $data[] = $assoc;
            }

            return $data;
        } finally {
            fclose($handle);
        }
    }
}