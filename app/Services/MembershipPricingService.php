<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Resolves membership prices from config/membership.php.
 *
 * Every membership amount charged by the API comes from here so that the
 * promotional discount and its cut-off date are enforced server side and
 * cannot be influenced by the client.
 */
class MembershipPricingService
{
    public const CONTEXT_NEW = 'new';

    public const CONTEXT_RENEWAL = 'renewal';

    /**
     * Whether the promotional discount is currently running.
     */
    public function promoIsActive(): bool
    {
        if (!config('membership.promo.enabled')) {
            return false;
        }

        $endsAt = $this->promoEndsAt();

        return $endsAt === null || CarbonImmutable::now()->lessThanOrEqualTo($endsAt);
    }

    /**
     * The inclusive moment the promotion stops applying, or null if open ended.
     */
    public function promoEndsAt(): ?CarbonImmutable
    {
        $endsAt = config('membership.promo.ends_at');

        if (empty($endsAt)) {
            return null;
        }

        return CarbonImmutable::parse($endsAt, config('membership.promo.timezone'))
            ->setTimezone(config('app.timezone'));
    }

    /**
     * Discount percentage that applies right now for the given context.
     */
    public function discountPercentage(string $context): float
    {
        if (!$this->promoIsActive()) {
            return 0.0;
        }

        return (float) (config('membership.promo.discounts.' . $this->normaliseContext($context)) ?? 0);
    }

    /**
     * Undiscounted price of a plan in rupees.
     */
    public function basePrice(string $type, string $plan): float
    {
        return (float) $this->planConfig($type, $plan)['price'];
    }

    /**
     * Membership duration of a plan in months.
     */
    public function months(string $type, string $plan): int
    {
        return (int) $this->planConfig($type, $plan)['months'];
    }

    /**
     * Amount actually payable in rupees for a plan in the given context.
     */
    public function priceFor(string $type, string $plan, string $context): float
    {
        return $this->breakdown($type, $plan, $context)['price'];
    }

    /**
     * Amount actually payable in paise - what Razorpay orders are created and
     * verified with.
     */
    public function priceInPaise(string $type, string $plan, string $context): int
    {
        return (int) round($this->priceFor($type, $plan, $context) * 100);
    }

    /**
     * Full pricing breakdown for a single plan.
     *
     * @return array<string,mixed>
     */
    public function breakdown(string $type, string $plan, string $context): array
    {
        $base = $this->basePrice($type, $plan);
        $percentage = $this->discountPercentage($context);
        $price = round($base - (($base * $percentage) / 100), 2);

        return [
            'membership_type'     => $type,
            'membership_plan'     => $plan,
            'months'              => $this->months($type, $plan),
            'currency'            => 'INR',
            'base_price'          => $base,
            'discount_percentage' => $percentage,
            'discount_amount'     => round($base - $price, 2),
            'price'               => $price,
            'price_in_paise'      => (int) round($price * 100),
        ];
    }

    /**
     * Pricing for every plan, for one context.
     *
     * @return array<string,array<string,array<string,mixed>>>
     */
    public function catalogue(string $context): array
    {
        $catalogue = [];

        foreach (array_keys((array) config('membership.plans')) as $type) {
            foreach (array_keys((array) config('membership.plans.' . $type)) as $plan) {
                $catalogue[$type][$plan] = $this->breakdown($type, $plan, $context);
            }
        }

        return $catalogue;
    }

    /**
     * Everything the website needs to render prices for both contexts.
     *
     * @return array<string,mixed>
     */
    public function publicCatalogue(): array
    {
        $endsAt = $this->promoEndsAt();

        return [
            'promo' => [
                'active'   => $this->promoIsActive(),
                'label'    => config('membership.promo.label'),
                'ends_at'  => $endsAt?->toIso8601String(),
                'timezone' => config('membership.promo.timezone'),
                'discounts' => [
                    'new'     => $this->discountPercentage(self::CONTEXT_NEW),
                    'renewal' => $this->discountPercentage(self::CONTEXT_RENEWAL),
                ],
            ],
            'new'     => $this->catalogue(self::CONTEXT_NEW),
            'renewal' => $this->catalogue(self::CONTEXT_RENEWAL),
        ];
    }

    /**
     * @return array{price: float, months: int}
     */
    private function planConfig(string $type, string $plan): array
    {
        $config = config("membership.plans.{$type}.{$plan}");

        if (!is_array($config)) {
            throw new InvalidArgumentException("Unknown membership plan [{$type} / {$plan}].");
        }

        return $config;
    }

    private function normaliseContext(string $context): string
    {
        return in_array($context, [self::CONTEXT_NEW, self::CONTEXT_RENEWAL], true)
            ? $context
            : self::CONTEXT_NEW;
    }
}
