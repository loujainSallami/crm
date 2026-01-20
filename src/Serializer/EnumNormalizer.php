<?php

namespace App\Serializer;

use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EnumNormalizer implements NormalizerInterface
{
    public function normalize($object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if ($object instanceof TaskStatus || $object instanceof TaskPriority) {
            return $object->name; // Utilise le nom de l'enum (toujours en majuscules)
        }

        return null; // Laisse les autres normalizers gérer
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof TaskStatus || $data instanceof TaskPriority;
    }

    // Nouvelle méthode requise pour Symfony 6.4+
    public function getSupportedTypes(?string $format): array
    {
        return [
            TaskStatus::class => true,
            TaskPriority::class => true,
        ];
    }
}
