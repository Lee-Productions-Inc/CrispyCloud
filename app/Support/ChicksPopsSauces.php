<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Official CHICK'S POPS menu sauce allowances; no prices are changed here. */
final class ChicksPopsSauces
{
    public const FLAVORS = [
        'Cheese Chick Sauce',
        'Teriyaki Chick Sauce',
        'Buffalo Chick Sauce',
        'Garlic Mayo Chick Sauce',
    ];

    /**
     * Listed flavored rice meals have one FIXED included flavor.
     * Only printed-menu meals explicitly advertising two flavors allow selection.
     * If your Admin product title differs, add its exact Str::slug() name below.
     */
    public static function policy(string $name): array
    {
        $key = Str::slug($name);
        $fixed = [
            'teriyaki-chick-pops' => 'Teriyaki Chick Sauce',
            'cheese-chick-pops' => 'Cheese Chick Sauce',
            'buffalo-chick-pops' => 'Buffalo Chick Sauce',
            'garlic-mayo-chick-pops' => 'Garlic Mayo Chick Sauce',
        ];
        $twoFlavors = [
            'ala-carte',
            'chick-pops-with-rice-and-fries',
            'hungarian-sausage-fried-pork-liempo-and-chicken-poppers',
        ];

        if (isset($fixed[$key])) {
            return ['kind' => 'fixed', 'fixed' => $fixed[$key], 'limit' => 0, 'options' => self::FLAVORS];
        }
        if (in_array($key, $twoFlavors, true) ||
            in_array($key, ['chick-pops-with-rice-fries'], true) ||
            collect($twoFlavors)->contains(fn ($name) => str_starts_with($key, $name . '-'))) {
            return ['kind' => 'choose', 'fixed' => null, 'limit' => 2, 'options' => self::FLAVORS];
        }
        return ['kind' => 'none', 'fixed' => null, 'limit' => 0, 'options' => self::FLAVORS];
    }

    /** Return canonical display string or null; never trust request values. */
    public static function validateChoices(string $productName, array $choices, bool $sauceRemoved): ?string
    {
        $policy = self::policy($productName);
        if (!array_is_list($choices) || count($choices) > 2 ||
            count($choices) !== count(array_unique($choices, SORT_REGULAR)) ||
            count(array_filter($choices, fn ($s) => is_string($s) && in_array($s, self::FLAVORS, true))) !== count($choices)) {
            self::invalid();
        }
        if ($sauceRemoved) {
            if ($choices !== []) self::invalid();
            return null;
        }
        if ($policy['kind'] === 'fixed') {
            if ($choices !== []) self::invalid();
            return $policy['fixed'];
        }
        if ($policy['kind'] === 'choose') {
            if (count($choices) < 1 || count($choices) > $policy['limit']) self::invalid();
            // Canonical order is the user's selection order, separated for existing order notes.
            return implode(', ', $choices);
        }
        if ($choices !== []) self::invalid();
        return null;
    }

    public static function validateStored(string $productName, mixed $stored, bool $sauceRemoved): ?string
    {
        if ($stored === null || $stored === '') {
            return self::validateChoices($productName, [], $sauceRemoved);
        }
        if (!is_string($stored) || mb_strlen($stored) > 160) self::invalid();
        $policy = self::policy($productName);
        if ($policy['kind'] === 'fixed' && !$sauceRemoved && $stored === $policy['fixed']) {
            return $stored;
        }
        return self::validateChoices($productName, explode(', ', $stored), $sauceRemoved);
    }

    private static function invalid(): never
    {
        throw ValidationException::withMessages([
            'sauce_choices' => 'Please review the included sauce choices for this meal and add it again if needed.',
        ]);
    }
}
