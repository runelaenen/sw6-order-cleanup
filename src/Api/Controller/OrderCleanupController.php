<?php declare(strict_types=1);

namespace OrderCleanup\Api\Controller;

use OrderCleanup\Service\OrderCleanupService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class OrderCleanupController extends AbstractController
{
    public function __construct(
        private readonly OrderCleanupService $orderCleanupService,
    ) {}

    #[Route(
        path: '/api/_action/order-cleanup/clear',
        name: 'api.action.order_cleanup.clear',
        methods: ['POST']
    )]
    public function clear(Context $context): JsonResponse
    {
        $this->orderCleanupService->cleanup($context);

        return new JsonResponse(['success' => true]);
    }
}
