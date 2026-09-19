<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\Service;

use ICC\QuickConsultCredit\Api\CreditLedgerInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionSearchResultsInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionSearchResultsInterfaceFactory;
use ICC\QuickConsultCredit\Model\CreditTransaction;
use ICC\QuickConsultCredit\Model\CreditTransactionFactory;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction as CreditTransactionResource;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @inheritDoc
 *
 * Every query is force-scoped to the supplied $customerId, regardless of any filter the
 * caller's SearchCriteria supplies, so another customer's entries can never be returned
 * (QCC-SEC-004).
 */
class CreditLedger implements CreditLedgerInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CreditTransactionSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var CreditTransactionFactory
     */
    private $creditTransactionFactory;

    /**
     * @var CreditTransactionResource
     */
    private $transactionResource;

    /**
     * @param CollectionFactory $collectionFactory
     * @param CreditTransactionSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CreditTransactionFactory $creditTransactionFactory
     * @param CreditTransactionResource $transactionResource
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        CreditTransactionSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        CreditTransactionFactory $creditTransactionFactory,
        CreditTransactionResource $transactionResource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->creditTransactionFactory = $creditTransactionFactory;
        $this->transactionResource = $transactionResource;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        int $customerId,
        SearchCriteriaInterface $searchCriteria
    ): CreditTransactionSearchResultsInterface {
        $collection = $this->collectionFactory->create();
        // Forced, non-overridable customer scope (QCC-SEC-004) — applied before any
        // caller-supplied SearchCriteria filter.
        $collection->addFieldToFilter('customer_id', $customerId);

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var CreditTransactionSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $customerId, int $transactionId): CreditTransactionInterface
    {
        /** @var CreditTransaction $model */
        $model = $this->creditTransactionFactory->create();
        $this->transactionResource->load($model, $transactionId);

        if (!$model->getId() || $model->getCustomerId() !== $customerId) {
            throw new NoSuchEntityException(__('No such transaction.'));
        }

        return $model;
    }
}
