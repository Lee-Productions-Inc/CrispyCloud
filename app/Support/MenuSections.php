<?php

namespace App\Support;

/** One classification rule shared by online ordering and the counter POS. */
final class MenuSections
{
    public static function isBeverageCategory(?string $name): bool
    {
        return (bool) preg_match(
            '/(?:^|[^a-z])(?:drinks?|beverages?|refreshments?|juices?|milkshakes?|sodas?|coffees?|teas?|water)(?:$|[^a-z])/i',
            $name ?? ''
        );
    }

    /** Legacy drink add-ons must not appear among food extras. No records are deleted. */
    public static function isDrinkAddOnName(?string $name): bool
    {
        return (bool) preg_match(
            '/\b(?:coke|coca[ -]?cola|cola|pepsi|sprite|fanta|mountain[ -]?dew|mtn[ -]?dew|7[ -]?up|royal|soda|soft[ -]?drinks?|beverages?|juices?|iced[ -]?tea|milk[ -]?tea|milkshakes?|coffee|latte|water|lemonade|root[ -]?beer|energy[ -]?drink)\b/i',
            $name ?? ''
        );
    }
}
