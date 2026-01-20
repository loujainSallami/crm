<?php

namespace App\Enum;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminée',
            self::CANCELLED => 'Annulée'
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::IN_PROGRESS => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger'
        };
    }
    
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }
    
    public static function choices(): array
    {
        return [
            self::PENDING->value => self::PENDING->getLabel(),
            self::IN_PROGRESS->value => self::IN_PROGRESS->getLabel(),
            self::COMPLETED->value => self::COMPLETED->getLabel(),
            self::CANCELLED->value => self::CANCELLED->getLabel(),
        ];
    }
}