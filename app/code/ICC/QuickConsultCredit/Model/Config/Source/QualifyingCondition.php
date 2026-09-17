<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Admin configuration options for the qualifying order/payment condition (QCC-CONFIG-003).
 */
class QualifyingCondition implements OptionSourceInterface
{
    public const INVOICE_GENERATED = 'invoice_generated';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::INVOICE_GENERATED,
                'label' => __('Invoice generated / payment captured'),
            ],
        ];
    }
}
