<?php declare(strict_types=1);

namespace OrderCleanup\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class OrderCleanupService
{
    public function __construct(
        #[Autowire(service: 'order.repository')]
        private EntityRepository $orderRepository,
        #[Autowire(service: 'document.repository')]
        private EntityRepository $documentRepository,
        #[Autowire(service: 'media.repository')]
        private EntityRepository $mediaRepository,
        #[Autowire(service: 'number_range_state.repository')]
        private EntityRepository $numberRangeStateRepository,
    ) {}

    public function cleanup(Context $context): void
    {
        $this->deleteDocuments($context);
        $this->deleteDocumentMedia($context);

        $this->deleteOrders($context);
        $this->resetNumberRangeStates($context);
    }

    private function deleteDocumentMedia(Context $context): void
    {
        $criteria = new Criteria();
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

        if (!empty($mediaIds)) {
            $this->mediaRepository->delete($mediaIds, $context);
        }
    }

    private function deleteDocuments(Context $context): void
    {
        // Delete child documents first (e.g. credit notes referencing an invoice)
        // to avoid violating the referenced_document_id ON DELETE RESTRICT constraint
        $childCriteria = new Criteria();
        $childCriteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('referencedDocumentId', null),
        ]));

        $childIds = $this->documentRepository->searchIds($childCriteria, $context)->getIds();
        if (!empty($childIds)) {
            $this->documentRepository->delete(
                array_map(fn($id) => ['id' => $id], $childIds),
                $context
            );
        }

        $remainingIds = $this->documentRepository->searchIds(new Criteria(), $context)->getIds();
        if (!empty($remainingIds)) {
            $this->documentRepository->delete(
                array_map(fn($id) => ['id' => $id], $remainingIds),
                $context
            );
        }
    }

    private function deleteOrders(Context $context): void
    {
        $ids = $this->orderRepository->searchIds(new Criteria(), $context)->getIds();

        if (empty($ids)) {
            return;
        }

        $this->orderRepository->delete(
            array_map(fn($id) => ['id' => $id], $ids),
            $context
        );
    }

    private function resetNumberRangeStates(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addAssociation('numberRange');
        $criteria->addFilter(new EqualsAnyFilter('numberRange.type.technicalName', [
            'order',
            'document_invoice',
            'document_storno',
            'document_delivery_note',
            'document_credit_note',
        ]));

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
