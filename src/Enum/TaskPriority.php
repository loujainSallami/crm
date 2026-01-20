<?php

namespace App\Enum;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    
    public function getLabel(): string
    {
        return match($this) {
            self::LOW => 'Basse',
            self::MEDIUM => 'Moyenne',
            self::HIGH => 'Haute'
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::LOW => 'success',
            self::MEDIUM => 'warning',
            self::HIGH => 'danger'
        };
    }
    
    public function getHoursLimit(): int
    {
        return match($this) {
            self::LOW => 72,
            self::MEDIUM => 48,
            self::HIGH => 24
        };
    }
    
    public static function choices(): array
    {
        return [
            self::LOW->value => self::LOW->getLabel(),
            self::MEDIUM->value => self::MEDIUM->getLabel(),
            self::HIGH->value => self::HIGH->getLabel(),
        ];
    }
}