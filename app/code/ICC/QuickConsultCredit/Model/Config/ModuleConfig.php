<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\Config;

use Magento\Store\Model\ScopeInterface;

/**
 * Reader for the Quick Consult Credit `Stores > Configuration > ICC > Quick Consult Credit`
 * settings (QCC-CONFIG-001..004).
 */
class ModuleConfig
{
    private const XML_PATH_ENABLED = 'quick_consult_credit/general/enabled';
    private const XML_PATH_PRODUCT_SKU = 'quick_consult_credit/general/product_sku';
    private const XML_PATH_QUALIFYING_CONDITION = 'quick_consult_credit/general/qualifying_condition';
    private const XML_PATH_HISTORY_PAGE_SIZE = 'quick_consult_credit/general/history_page_size';

    public const DEFAULT_HISTORY_PAGE_SIZE = 20;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * QCC-CONFIG-001: module enable/disable.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * QCC-CONFIG-002: the Quick Consult Credit product SKU.
     *
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getCreditProductSku($storeId = null): ?string
    {
        $sku = $this->scopeConfig->getValue(self::XML_PATH_PRODUCT_SKU, ScopeInterface::SCOPE_STORE, $storeId);
        return $sku !== null && $sku !== '' ? (string) $sku : null;
    }

    /**
     * QCC-CONFIG-003: qualifying order/payment condition.
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getQualifyingCondition($storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_QUALIFYING_CONDITION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value !== null && $value !== ''
            ? (string) $value
            : \ICC\QuickConsultCredit\Model\Config\Source\QualifyingCondition::INVOICE_GENERATED;
    }

    /**
     * QCC-CONFIG-004: customer transaction history page size.
     *
     * @param int|string|null $storeId
     * @return int
     */
    public function getHistoryPageSize($storeId = null): int
    {
        $value = (int) $this->scopeConfig->getValue(
            self::XML_PATH_HISTORY_PAGE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value > 0 ? $value : self::DEFAULT_HISTORY_PAGE_SIZE;
    }
}
