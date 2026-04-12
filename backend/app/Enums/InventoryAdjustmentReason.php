<?php

namespace App\Enums;

/**
 * Allowed reasons for manual inventory adjustments (product detail + API).
 * Stored as the case value in {@see InventoryAdjustment::$reason}.
 */
enum InventoryAdjustmentReason: string
{
    case ReceivedShipment = 'received_shipment';
    case CustomerReturn = 'customer_return';
    case DamagedOrDefective = 'damaged_or_defective';
    case ExpiredOrUnsellable = 'expired_or_unsellable';
    case TheftOrLoss = 'theft_or_loss';
    case CycleCountAudit = 'cycle_count_audit';
    case DataCorrection = 'data_correction';
    case DemoOrInternalUse = 'demo_or_internal_use';

    public function label(): string
    {
        return match ($this) {
            self::ReceivedShipment => __('Received shipment or restock'),
            self::CustomerReturn => __('Customer return'),
            self::DamagedOrDefective => __('Damaged or defective'),
            self::ExpiredOrUnsellable => __('Expired or unsellable'),
            self::TheftOrLoss => __('Theft or loss'),
            self::CycleCountAudit => __('Cycle count / audit'),
            self::DataCorrection => __('Data entry correction'),
            self::DemoOrInternalUse => __('Demo or internal use'),
        };
    }

    /**
     * Human-readable label for a stored reason value (legacy free-text rows pass through).
     */
    public static function labelOrRaw(?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '—';
        }

        $case = self::tryFrom($stored);

        return $case !== null ? $case->label() : $stored;
    }
}
