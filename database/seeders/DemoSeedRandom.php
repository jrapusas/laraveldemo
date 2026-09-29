<?php

namespace Database\Seeders;

/** Random picks for demo seeders — no Faker (production deploy uses --no-dev). */
final class DemoSeedRandom
{
    /**
     * @param  non-empty-array<int, mixed>  $items
     */
    public static function pick(array $items): mixed
    {
        return $items[array_rand($items)];
    }

    /**
     * @param  non-empty-list<string>  $choices
     */
    public static function maybePick(float $probability, array $choices): ?string
    {
        if (random_int(1, 100) > (int) round($probability * 100)) {
            return null;
        }

        return self::pick($choices);
    }
}
