<?php declare(strict_types=1);

namespace OrderCleanup\Command;

use OrderCleanup\Service\CleanupService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'order-cleanup:clear-customers',
    description: 'Delete all customers and reset the customer number range counter',
)]
class CustomerCleanupCommand extends Command
{
    public function __construct(
        private readonly CleanupService $cleanupService,
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

        $io->warning('This will permanently delete ALL customers and reset the customer number range counter.');

        if (!$input->getOption('no-interaction') && !$io->confirm('Are you sure you want to continue?', false)) {
            $io->comment('Aborted.');

            return Command::SUCCESS;
        }

        $context = Context::createDefaultContext();
        $batch = 0;

        do {
            $hasMore = $this->cleanupService->cleanupCustomers($context);
            $io->text(sprintf('Processed batch %d...', ++$batch));
        } while ($hasMore);

        $io->success(sprintf('All customers and the customer number range counter have been cleared in %d batch(es).', $batch));

        return Command::SUCCESS;
    }
}
