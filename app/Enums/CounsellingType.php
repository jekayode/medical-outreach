<?php

namespace App\Enums;

enum CounsellingType: string
{
    case Wellness = 'wellness';
    case Prayer = 'prayer';
    case Missions = 'missions';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * @param  list<string>  $values
     * @return list<CounsellingType>
     */
    public static function tryFromMany(array $values): array
    {
        $byValue = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            $type = self::tryFrom($value);

            if ($type !== null) {
                $byValue[$type->value] = $type;
            }
        }

        return array_values($byValue);
    }
}
