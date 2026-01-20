<?php
// src/Doctrine/Types/VicidialEnumType.php
namespace App\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

class VicidialEnumType extends Type
{
    public const TYPE_NAME = 'vicidial_enum'; // Nom du type
    private const ALLOWED_VALUES = ['Y', 'N']; // Valeurs autorisées

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return sprintf("ENUM('%s')", implode("','", self::ALLOWED_VALUES));
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        if (!in_array($value, self::ALLOWED_VALUES, true) && $value !== null) {
            throw new InvalidArgumentException(sprintf(
                "Invalid enum value '%s'. Allowed values are %s",
                $value,
                implode(', ', self::ALLOWED_VALUES)
            ));
        }
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (!in_array($value, self::ALLOWED_VALUES, true) && $value !== null) {
            throw new InvalidArgumentException(sprintf(
                "Invalid enum value '%s'. Allowed values are %s",
                $value,
                implode(', ', self::ALLOWED_VALUES)
            ));
        }
        return $value;
    }

    public function getName(): string
    {
        return self::TYPE_NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true; // Indique que le type nécessite un commentaire SQL
    }
}
