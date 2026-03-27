<?php
declare(strict_types=1);

namespace OrderCleanup\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class CleanupService
{
    private const BATCH_SIZE = 500;

    public function __construct(
        #[Autowire(service: 'order.repository')]
        private EntityRepository $orderRepository,
        #[Autowire(service: 'document.repository')]
        private EntityRepository $documentRepository,
        #[Autowire(service: 'media.repository')]
        private EntityRepository $mediaRepository,
        #[Autowire(service: 'number_range_state.repository')]
        private EntityRepository $numberRangeStateRepository,
        #[Autowire(service: 'customer.repository')]
        private EntityRepository $customerRepository,
    ) {
    }

    public function countOrders(Context $context): int
    {
        return $this->orderRepository->searchIds(new Criteria(), $context)->getTotal();
    }

    public function countCustomers(Context $context): int
    {
        return $this->customerRepository->searchIds(new Criteria(), $context)->getTotal();
    }

    /**
     * Deletes one batch of orders and all their associated documents and media.
     * Returns true if there are more orders to process.
     * Resets number range states on the final batch.
     */
    public function cleanupOrders(Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->setLimit(self::BATCH_SIZE);

        $orderIds = $this->orderRepository->searchIds($criteria, $context)->getIds();

        if (empty($orderIds)) {
            return false;
        }

        $this->deleteDocumentsForOrders($orderIds, $context);

        $this->orderRepository->delete(
            array_map(fn($id) => ['id' => $id], $orderIds),
            $context
        );

        $hasMore = count($orderIds) === self::BATCH_SIZE;

        if (!$hasMore) {
            $this->resetNumberRangeStatesByTypes([
                'order',
                'document_invoice',
                'document_storno',
                'document_delivery_note',
                'document_credit_note',
            ], $context);
        }

        return $hasMore;
    }

    /**
     * Deletes one batch of customers.
     * Returns true if there are more customers to process.
     * Resets number range states on the final batch.
     */
    public function cleanupCustomers(Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->setLimit(self::BATCH_SIZE);

        $ids = $this->customerRepository->searchIds($criteria, $context)->getIds();

        if (empty($ids)) {
            return false;
        }

        $this->customerRepository->delete(
            array_map(fn($id) => ['id' => $id], $ids),
            $context
        );

        $hasMore = count($ids) === self::BATCH_SIZE;

        if (!$hasMore) {
            $this->resetNumberRangeStatesByTypes(['customer'], $context);
        }

        return $hasMore;
    }

    private function deleteDocumentsForOrders(array $orderIds, Context $context): void
    {
        // Delete child documents first (e.g. credit notes referencing an invoice)
        // to avoid violating the referenced_document_id ON DELETE RESTRICT constraint
        $childCriteria = new Criteria();
        $childCriteria->addFilter(new EqualsAnyFilter('orderId', $orderIds));
        $childCriteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('referencedDocumentId', null),
        ]));

        $childIds = $this->documentRepository->searchIds($childCriteria, $context)->getIds();
        if (!empty($childIds)) {
            $childMediaIds = $this->getMediaIdsForDocuments($childIds, $context);
            $this->documentRepository->delete(
                array_map(static fn($id) => ['id' => $id], $childIds),
                $context
            );
            if (!empty($childMediaIds)) {
                $this->mediaRepository->delete($childMediaIds, $context);
            }
        }

        $parentCriteria = new Criteria();
        $parentCriteria->addFilter(new EqualsAnyFilter('orderId', $orderIds));

        $parentIds = $this->documentRepository->searchIds($parentCriteria, $context)->getIds();
        if (!empty($parentIds)) {
            $parentMediaIds = $this->getMediaIdsForDocuments($parentIds, $context);
            $this->documentRepository->delete(
                array_map(static fn($id) => ['id' => $id], $parentIds),
                $context
            );
            if (!empty($parentMediaIds)) {
                $this->mediaRepository->delete($parentMediaIds, $context);
            }
        }
    }

    private function getMediaIdsForDocuments(array $documentIds, Context $context): array
    {
        $criteria = new Criteria($documentIds);
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('documentMediaFileId', null),
        ]));

        $documents = $this->documentRepository->search($criteria, $context);

        $mediaIds = [];
        foreach ($documents as $document) {
            if ($document->getDocumentMediaFileId() !== null) {
                $mediaIds[] = ['id' => $document->getDocumentMediaFileId()];
            }
        }

        return $mediaIds;
    }

    private function resetNumberRangeStatesByTypes(array $technicalNames, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addAssociation('numberRange');
        $criteria->addFilter(new EqualsAnyFilter('numberRange.type.technicalName', $technicalNames));

        $states = $this->numberRangeStateRepository->search($criteria, $context);

        $updates = [];
        foreach ($states as $state) {
            $updates[] = [
                'id' => $state->getId(),
                'lastValue' => $state->getNumberRange()->getStart() - 1,
            ];
        }

        if (!empty($updates)) {
            $this->numberRangeStateRepository->update($updates, $context);
        }
    }
}
