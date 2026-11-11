<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

/** Validate promotion definitions; application/redeeming is deliberately disabled. */
final class PromoCodeData
{
    private static function cents(string $value, bool $allowZero = true): int
    {
        if (!preg_match('/^[0-9]{1,8}(?:[.,][0-9]{1,2})?$/D', $value)) {
            throw new \DomainException('COM_MINIMARKET_PROMOCODES_INVALID');
        }
        $parts = explode('.', str_replace(',', '.', $value), 2);
        $cents = (int)$parts[0] * 100 + (int)str_pad($parts[1] ?? '', 2, '0');
        if (!$allowZero && $cents === 0) {
            throw new \DomainException('COM_MINIMARKET_PROMOCODES_INVALID');
        }
        return $cents;
    }

    private static function date(string $value): ?string
    {
        if (trim($value) === '') return null;
        $value = trim($value);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d\TH:i') !== $value) {
            throw new \DomainException('COM_MINIMARKET_PROMOCODES_INVALID');
        }
        return $date->format('Y-m-d H:i:s');
    }

    private static function limit(string $raw): ?int
    {
        if ($raw === '') return null;
        if (!preg_match('/^[1-9][0-9]{0,8}$/D', $raw)) {
            throw new \DomainException('COM_MINIMARKET_PROMOCODES_INVALID');
        }
        return (int)$raw;
    }

    public static function normalize(array $input): array
    {
        $code = strtoupper(trim((string)($input['code'] ?? '')));
        $kind = (string)($input['discount_type'] ?? 'fixed');
        $scope = (string)($input['applies_to'] ?? 'all');
        if (!preg_match('/^[A-Z0-9-]{3,64}$/D', $code)
            || !in_array($kind, ['fixed', 'percent'], true)
            || !in_array($scope, ['all', 'category', 'product'], true)) {
            throw new \DomainException('COM_MINIMARKET_PROMOCODES_INVALID');
        }
        $amount = $kind === 'fixed' ? self::cents(trim((string)($input['amount'] ?? '')), false) : 0;
        $percent = null;
        if ($kind === 'percent') {
            $raw = trim(str_replace(',', '.', (string)($input['percent_value'] ?? '')));
            if (!preg_match('/^(?:[1-9][0-9]?|100)(?:\.[0-9]{1,2})?$/D', $raw)
                || (float)$raw > 100.0) {
                throw new \DomainException('COM_MINIMARKET_PROMOCODES_INVALID');
            }
            $percent = (float)$raw;
        }
        $start = self::date((string)($input['starts_at'] ?? ''));
        $end = self::date((string)($input['ends_at'] ?? ''));
        if ($start !== null && $end !== null && $end <= $start) {
            throw new \DomainException('COM_MINIMARKET_PROMOCODES_INVALID');
        }
        $minimum = self::cents(trim((string)($input['min_cart'] ?? '0')));
        $totalLimit = self::limit(trim((string)($input['usage_limit'] ?? '')));
        $customerLimit = self::limit(trim((string)($input['per_customer_limit'] ?? '')));
        $rawIds = trim((string)($input['target_ids'] ?? ''));
        $ids = [];
        if ($scope !== 'all') {
            $parts = array_filter(array_map('trim', explode(',', $rawIds)), static fn($v) => $v !== '');
            if (!$parts || count($parts) > 100) {
                throw new \DomainException('COM_MINIMARKET_PROMOCODES_INVALID');
            }
            foreach ($parts as $part) {
                if (!preg_match('/^[1-9][0-9]{0,9}$/D', $part)) {
                    throw new \DomainException('COM_MINIMARKET_PROMOCODES_INVALID');
                }
                $ids[] = (int)$part;
            }
            $ids = array_values(array_unique($ids));
        }
        return [
            'code' => $code,
            'amount_cents' => $amount,
            'discount_type' => $kind,
            'percent_value' => $percent,
            'starts_at' => $start,
            'ends_at' => $end,
            'min_cart_cents' => $minimum,
            'usage_limit' => $totalLimit,
            'per_customer_limit' => $customerLimit,
            'applies_to' => $scope,
            'target_ids_json' => json_encode($ids, JSON_THROW_ON_ERROR),
            'combinable' => !empty($input['combinable']) ? 1 : 0,
        ];
    }
}
