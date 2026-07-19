<?php

namespace App\Command;

use App\Enum\StatutGame;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cloturer-matchs-expires',
    description: 'Clôture automatiquement les matchs dont la date est passée : annule ceux sans adversaire, termine ceux avec 2 camps confirmés.',
)]
class CloturerMatchsExpiresCommand extends Command
{
    public function __construct(
        private GameRepository         $gameRepository,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $matchs   = $this->gameRepository->trouverMatchsExpires();
        $annules  = 0;
        $termines = 0;

        foreach ($matchs as $match) {
            if ($match->getStatut() === StatutGame::EnAttente) {
                $match->setStatut(StatutGame::Annule);
                $annules++;
            } elseif ($match->getStatut() === StatutGame::Confirme) {
                $match->setStatut(StatutGame::Termine);
                $termines++;
            }
        }

        $this->em->flush();

        $io->success(sprintf(
            '%d match(s) annulé(s), %d match(s) clôturé(s) en terminé.',
            $annules,
            $termines,
        ));

        return Command::SUCCESS;
    }
}
