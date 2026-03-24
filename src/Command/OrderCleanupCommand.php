<?php declare(strict_types=1);

namespace OrderCleanup\Command;

use OrderCleanup\Service\OrderCleanupService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'order-cleanup:clear',
    description: 'Delete all orders, documents and media files, and reset number range counters',
)]
class OrderCleanupCommand extends Command
{
    public function __construct(
        private readonly OrderCleanupService $orderCleanupService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Skip confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->warning('This will permanently delete ALL orders, documents and media files, and reset all order number counters.');

        if (!$input->getOption('no-interaction') && !$io->confirm('Are you sure you want to continue?', false)) {
            $io->comment('Aborted.');

            return Command::SUCCESS;
        }

        $io->text('Running cleanup...');

        $this->orderCleanupService->cleanup(Context::createDefaultContext());

        $io->success('All orders, documents and number range counters have been cleared.');

        return Command::SUCCESS;
    }
}
