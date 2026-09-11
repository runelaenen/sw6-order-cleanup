<?php declare(strict_types=1);

namespace OrderCleanup\Api\Controller;

use OrderCleanup\Service\CleanupService;
use Shopware\Core\PlatformRequest;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class CleanupController extends AbstractController
{
    public function __construct(
        private readonly CleanupService $cleanupService,
    ) {}

    #[Route(
        path: '/api/_action/order-cleanup/count',
        name: 'api.action.order_cleanup.count',
        methods: ['GET']
    )]
    public function count(Context $context): JsonResponse
    {
        return new JsonResponse([
            'orders' => $this->cleanupService->countOrders($context),
            'customers' => $this->cleanupService->countCustomers($context),
        ]);
    }

    #[Route(
        path: '/api/_action/order-cleanup/clear',
        name: 'api.action.order_cleanup.clear',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['order.deleter']],
        methods: ['POST']
    )]
    public function clearOrders(Context $context): JsonResponse
    {
        $hasMore = $this->cleanupService->cleanupOrders($context);

        return new JsonResponse(['hasMore' => $hasMore]);
    }

    #[Route(
        path: '/api/_action/order-cleanup/clear-customers',
        name: 'api.action.order_cleanup.clear_customers',
        methods: ['POST']
    )]
    public function clearCustomers(Context $context): JsonResponse
    {
        $hasMore = $this->cleanupService->cleanupCustomers($context);

        return new JsonResponse(['hasMore' => $hasMore]);
    }

    #[Route(
        path: '/api/_action/order-cleanup/delete-orders',
        name: 'api.action.order_cleanup.delete_orders',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['order.deleter']],
        methods: ['POST']
    )]
    public function deleteOrders(Request $request, Context $context): JsonResponse
    {
        $ids = $request->request->all('ids');

        if (empty($ids)) {
            return new JsonResponse(
                ['error' => 'Parameter "ids" must be a non-empty array of order IDs.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->cleanupService->deleteOrdersByIds($ids, $context);

        return new JsonResponse(['success' => true]);
    }
}
