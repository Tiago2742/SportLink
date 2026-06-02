<?php

namespace App\Tests\Unit\Service;

use App\Entity\Disputer;
use App\Entity\Equipe;
use App\Entity\Game;
use App\Entity\Message;
use App\Entity\Participation;
use App\Entity\Resultat;
use App\Entity\Utilisateur;
use App\Repository\EquipeRepository;
use App\Repository\ParticipationRepository;
use App\Service\MatchService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MatchServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ParticipationRepository&MockObject $participationRepository;
    private EquipeRepository&MockObject $equipeRepository;
    private MatchService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->participationRepository = $this->createMock(ParticipationRepository::class);
        $this->equipeRepository = $this->createMock(EquipeRepository::class);
        $this->service = new MatchService($this->em, $this->participationRepository, $this->equipeRepository);
    }

    // --- creer ---

    public function testCreerMatchAvecChampsObligatoires(): void
    {
        $createur = new Utilisateur();
        $donnees = [
            'sport'      => 'football',
            'dateMatch'  => '2026-09-01 18:00',
            'lieu'       => 'Terrain municipal',
            'niveauRequis' => 'D1',
        ];

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Game::class));
        $this->em->expects($this->once())->method('flush');

        $match = $this->service->creer($donnees, $createur);

        $this->assertEquals('football', $match->getSport());
        $this->assertEquals('Terrain municipal', $match->getLieu());
        $this->assertEquals('D1', $match->getNiveauRequis());
        $this->assertEquals('ouvert', $match->getStatut());
        $this->assertSame($createur, $match->getCreateur());
    }

    public function testCreerMatchStatutParDefautOuvert(): void
    {
        $this->em->method('persist');
        $this->em->method('flush');

        $match = $this->service->creer(['sport' => 'tennis', 'dateMatch' => '2026-10-01 10:00'], new Utilisateur());

        $this->assertEquals('ouvert', $match->getStatut());
    }

    // --- modifier ---

    public function testModifierMatchChampsSport(): void
    {
        $match = new Game();
        $match->setSport('football');

        $this->em->expects($this->once())->method('flush');

        $this->service->modifier($match, ['sport' => 'rugby']);

        $this->assertEquals('rugby', $match->getSport());
    }

    public function testModifierMatchSansChamp_NeModifiePasLeChamp(): void
    {
        $match = new Game();
        $match->setSport('football');
        $match->setLieu('Terrain A');

        $this->em->method('flush');

        $this->service->modifier($match, ['lieu' => 'Terrain B']);

        $this->assertEquals('football', $match->getSport());
        $this->assertEquals('Terrain B', $match->getLieu());
    }

    // --- participer ---

    public function testParticiperDejaInscrit_LeveException(): void
    {
        $match = new Game();
        $utilisateur = new Utilisateur();

        $this->participationRepository
            ->method('findOneBy')
            ->willReturn(new Participation());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('déjà');

        $this->service->participer($match, $utilisateur);
    }

    public function testParticiperSucces_StatutInvite(): void
    {
        $match = new Game();
        $utilisateur = new Utilisateur();

        $this->participationRepository->method('findOneBy')->willReturn(null);
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Participation::class));
        $this->em->expects($this->once())->method('flush');

        $participation = $this->service->participer($match, $utilisateur);

        $this->assertEquals('invité', $participation->getStatut());
        $this->assertSame($match, $participation->getGame());
        $this->assertSame($utilisateur, $participation->getUtilisateur());
    }

    // --- modifierStatut ---

    public function testModifierStatutValide(): void
    {
        $participation = new Participation();
        $participation->setStatut('invité');

        $this->em->expects($this->once())->method('flush');

        $this->service->modifierStatut($participation, 'confirmé');

        $this->assertEquals('confirmé', $participation->getStatut());
    }

    public function testModifierStatutInvalide_LeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Statut invalide');

        $this->service->modifierStatut(new Participation(), 'inexistant');
    }

    public function testTousLesStatutsValidesAcceptes(): void
    {
        $this->em->method('flush');

        foreach (['invité', 'confirmé', 'refusé'] as $statut) {
            $participation = new Participation();
            $this->service->modifierStatut($participation, $statut);
            $this->assertEquals($statut, $participation->getStatut());
        }
    }

    // --- inscrireEquipe ---

    public function testInscrireEquipeIntrouvable_LeveException(): void
    {
        $match = $this->creerMatchAvecEquipes([]);
        $this->equipeRepository->method('find')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Équipe introuvable.');

        $this->service->inscrireEquipe($match, 99, null);
    }

    public function testInscrireEquipeDejaPresente_LeveException(): void
    {
        $equipe = new Equipe();

        $disputer = new Disputer();
        $disputer->setEquipe($equipe);

        $match = $this->creerMatchAvecEquipes([$disputer]);
        $this->equipeRepository->method('find')->willReturn($equipe);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('déjà inscrite');

        $this->service->inscrireEquipe($match, 1, null);
    }

    public function testInscrireEquipe_DeuxEquipesMaximum_LeveException(): void
    {
        $match = $this->creerMatchAvecEquipes([new Disputer(), new Disputer()]);
        $this->equipeRepository->method('find')->willReturn(new Equipe());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('deux équipes');

        $this->service->inscrireEquipe($match, 1, null);
    }

    public function testInscrireEquipeSucces(): void
    {
        $equipe = new Equipe();
        $match = $this->creerMatchAvecEquipes([]);

        $this->equipeRepository->method('find')->willReturn($equipe);
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Disputer::class));
        $this->em->expects($this->once())->method('flush');

        $disputer = $this->service->inscrireEquipe($match, 1, 'equipe_1');

        $this->assertSame($equipe, $disputer->getEquipe());
        $this->assertEquals('equipe_1', $disputer->getRole());
    }

    // --- envoyerMessage ---

    public function testEnvoyerMessageSucces(): void
    {
        $match = new Game();
        $expediteur = new Utilisateur();

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Message::class));
        $this->em->expects($this->once())->method('flush');

        $message = $this->service->envoyerMessage($match, $expediteur, 'Bonjour !');

        $this->assertEquals('Bonjour !', $message->getContenu());
        $this->assertSame($match, $message->getGame());
        $this->assertSame($expediteur, $message->getExpediteur());
        $this->assertNotNull($message->getDateEnvoi());
    }

    // --- estParticipant ---

    public function testEstParticipant_Createur(): void
    {
        $createur = new Utilisateur();
        $match = new Game();
        $match->setCreateur($createur);

        $this->assertTrue($this->service->estParticipant($match, $createur));
    }

    public function testEstParticipant_UtilisateurConfirme(): void
    {
        $utilisateur = new Utilisateur();
        $match = new Game();
        $match->setCreateur(new Utilisateur());

        $participation = new Participation();
        $participation->setStatut('confirmé');

        $this->participationRepository
            ->method('findOneBy')
            ->with(['game' => $match, 'utilisateur' => $utilisateur, 'statut' => 'confirmé'])
            ->willReturn($participation);

        $this->assertTrue($this->service->estParticipant($match, $utilisateur));
    }

    public function testEstParticipant_UtilisateurInvite_RetourneFaux(): void
    {
        $utilisateur = new Utilisateur();
        $match = new Game();
        $match->setCreateur(new Utilisateur());

        $this->participationRepository->method('findOneBy')->willReturn(null);

        $this->assertFalse($this->service->estParticipant($match, $utilisateur));
    }

    // --- saisirResultat ---

    public function testSaisirResultatSucces(): void
    {
        $match = $this->creerMatchSansResultat();

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Resultat::class));
        $this->em->expects($this->once())->method('flush');

        $resultat = $this->service->saisirResultat($match, 3, 1);

        $this->assertEquals(3, $resultat->getScoreEquipe1());
        $this->assertEquals(1, $resultat->getScoreEquipe2());
    }

    public function testSaisirResultat_DejaExistant_LeveException(): void
    {
        $match = $this->createPartialMock(Game::class, ['getResultat']);
        $match->method('getResultat')->willReturn(new Resultat());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('déjà un résultat');

        $this->service->saisirResultat($match, 2, 0);
    }

    public function testModifierResultatSucces(): void
    {
        $resultat = new Resultat();
        $resultat->setScoreEquipe1(1);
        $resultat->setScoreEquipe2(0);

        $match = $this->createPartialMock(Game::class, ['getResultat']);
        $match->method('getResultat')->willReturn($resultat);

        $this->em->expects($this->once())->method('flush');

        $resultatModifie = $this->service->modifierResultat($match, 2, 2);

        $this->assertEquals(2, $resultatModifie->getScoreEquipe1());
        $this->assertEquals(2, $resultatModifie->getScoreEquipe2());
    }

    public function testModifierResultat_Inexistant_LeveException(): void
    {
        $match = $this->creerMatchSansResultat();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pas encore de résultat');

        $this->service->modifierResultat($match, 1, 1);
    }

    // --- Helpers ---

    private function creerMatchAvecEquipes(array $equipes): Game
    {
        $collection = new ArrayCollection($equipes);
        $match = $this->createPartialMock(Game::class, ['getEquipesDisputant']);
        $match->method('getEquipesDisputant')->willReturn($collection);

        return $match;
    }

    private function creerMatchSansResultat(): Game
    {
        $match = $this->createPartialMock(Game::class, ['getResultat']);
        $match->method('getResultat')->willReturn(null);

        return $match;
    }
}
