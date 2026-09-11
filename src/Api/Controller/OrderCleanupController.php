<?php declare(strict_types=1);

namespace OrderCleanup\Api\Controller;

use OrderCleanup\Service\OrderCleanupService;
use Shopware\Core\PlatformRequest;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class OrderCleanupController extends AbstractController
{
    public function __construct(
        private readonly OrderCleanupService $orderCleanupService,
    ) {}

    #[Route(
        path: '/api/_action/order-cleanup/clear',
        name: 'api.action.order_cleanup.clear',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['order.deleter']],
        methods: ['POST']
    )]
    public function clear(Context $context): JsonResponse
    {
        $this->orderCleanupService->cleanup($context);

        return new JsonResponse(['success' => true]);
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

        $this->orderCleanupService->deleteOrdersByIds($ids, $context);

        return new JsonResponse(['success' => true]);
    }
}
