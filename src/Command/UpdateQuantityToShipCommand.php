<?php

namespace App\Command;

use App\Entity\ClientOrderRow;
use App\Service\ClientOrderRowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-quantity-to-ship',
    description: 'Aggiorna il campo quantity_to_ship per tutte le righe ordine cliente',
)]
class UpdateQuantityToShipCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientOrderRowService $clientOrderRowService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rows = $this->entityManager->getRepository(ClientOrderRow::class)->findAll();

        $io->progressStart(count($rows));

        foreach ($rows as $row) {
            $this->clientOrderRowService->updateQuantityToShip($row);
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success('Tutte le righe ordine sono state aggiornate.');

        return Command::SUCCESS;
    }
}
