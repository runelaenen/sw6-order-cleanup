<?php declare(strict_types=1);

namespace OrderCleanup\Command;

use OrderCleanup\Service\CleanupService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'order-cleanup:delete',
    description: 'Delete one or more orders by ID, including their documents and media files',
)]
class OrderDeleteCommand extends Command
{
    public function __construct(
        private readonly CleanupService $cleanupService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('ids', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'One or more order IDs to delete')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Skip confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ids = $input->getArgument('ids');

        $count = \count($ids);
        $io->warning(\sprintf(
            'This will permanently delete %d order(s) and all associated documents and media files.',
            $count,
        ));

        if (!$input->getOption('no-interaction') && !$io->confirm('Are you sure you want to continue?', false)) {
            $io->comment('Aborted.');

            return Command::SUCCESS;
        }

        $this->cleanupService->deleteOrdersByIds($ids, Context::createDefaultContext());

        $io->success(\sprintf('%d order(s) and their documents have been deleted.', $count));

        return Command::SUCCESS;
    }
}
