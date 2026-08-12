<?php

namespace App\Service;

class CriteriaManager
{
    public const WEIGHT_LOW = 'low';
    public const WEIGHT_MEDIUM = 'medium';
    public const WEIGHT_HIGH = 'high';

    public static function getWeightValue(string $weight): float
    {
        return match ($weight) {
            self::WEIGHT_LOW => 1.0,
            self::WEIGHT_MEDIUM => 2.0,
            self::WEIGHT_HIGH => 3.0,
            default => 2.0, // default weight is medium
        };
    }

    /**
     * Returns the list of rated criteria.
     * Scale: 1 to 5.
     */
    public static function getRatedCriteria(): array
    {
        return [
            'profitability' => [
                'label' => 'criteria.profitability.label',
                'description' => 'criteria.profitability.description',
            ],
            'feasibility' => [
                'label' => 'criteria.feasibility.label',
                'description' => 'criteria.feasibility.description',
            ],
            'market' => [
                'label' => 'criteria.market.label',
                'description' => 'criteria.market.description',
            ],
            'originality' => [
                'label' => 'criteria.originality.label',
                'description' => 'criteria.originality.description',
            ],
            'scalability' => [
                'label' => 'criteria.scalability.label',
                'description' => 'criteria.scalability.description',
            ],
            'development_time' => [
                'label' => 'criteria.development_time.label',
                'description' => 'criteria.development_time.description',
            ],
            'pleasure' => [
                'label' => 'criteria.pleasure.label',
                'description' => 'criteria.pleasure.description',
            ],
            'launch_cost' => [
                'label' => 'criteria.launch_cost.label',
                'description' => 'criteria.launch_cost.description',
            ],
            'legal_complexity' => [
                'label' => 'criteria.legal_complexity.label',
                'description' => 'criteria.legal_complexity.description',
            ],
        ];
    }
}
