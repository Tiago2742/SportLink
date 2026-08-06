<?php

namespace App\Command;

use App\Entity\Niveau;
use App\Entity\Sport;
use App\Enum\TypeSport;
use App\Reference\SportNiveaux;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reference:load',
    description: 'Charge les sports et leurs niveaux depuis SportNiveaux::CATALOGUE (idempotent).',
)]
class LoadReferenceDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $repo = $this->em->getRepository(Sport::class);

        $crees   = 0;
        $ignores = 0;

        foreach (SportNiveaux::CATALOGUE as $nom => $cfg) {
            // Idempotence : si le sport existe déjà, on ne le recrée pas
            if ($repo->findOneBy(['nom' => $nom]) !== null) {
                $ignores++;
                continue;
            }

            $sport = new Sport();
            $sport->setNom($nom);
            $sport->setType(TypeSport::from($cfg['type']));

            foreach ($cfg['niveaux'] as $ordre => $libelle) {
                $niveau = new Niveau();
                $niveau->setLibelle($libelle);
                $niveau->setOrdre($ordre + 1);
                $sport->addNiveau($niveau);
                $this->em->persist($niveau);
            }

            $this->em->persist($sport);
            $crees++;
        }

        $this->em->flush();

        if ($crees > 0) {
            $io->success(sprintf('%d sport(s) créé(s) avec leurs niveaux.', $crees));
        } else {
            $io->info(sprintf('Référentiel déjà à jour — %d sport(s) ignoré(s).', $ignores));
        }

        return Command::SUCCESS;
    }
}
