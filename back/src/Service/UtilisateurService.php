<?php

namespace App\Service;

use App\Entity\EquipeJoueur;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurNiveau;
use App\Enum\TypeUtilisateur;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UtilisateurService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private EquipeRepository $equipeRepo,
        private RequestStack $requestStack,
        #[Autowire(service: 'monolog.logger.security')]
        private LoggerInterface $logger,
    ) {}

    /**
     * Anonymise le compte : efface les données personnelles et invalide les accès.
     * L'entité est conservée pour préserver l'intégrité référentielle (matchs, messages).
     *
     * @throws \InvalidArgumentException si le club gère encore des équipes
     */
    public function anonymiser(Utilisateur $user): void
    {
        // Requête directe (pas la collection inverse, qui peut être stale en mémoire)
        if ($user->getType() === TypeUtilisateur::Club && $this->equipeRepo->count(['club' => $user]) > 0) {
            throw new \InvalidArgumentException(
                'Supprimez ou transférez vos équipes avant de clôturer le compte.'
            );
        }

        // EquipeJoueur : requête directe (collection inverse potentiellement stale)
        $ejs = $this->em->getRepository(EquipeJoueur::class)->findBy(['utilisateur' => $user]);
        foreach ($ejs as $ej) {
            $this->em->remove($ej);
        }

        // UtilisateurNiveau : idem, requête directe
        $uns = $this->em->getRepository(UtilisateurNiveau::class)->findBy(['utilisateur' => $user]);
        foreach ($uns as $un) {
            $this->em->remove($un);
        }

        // Effacement des données personnelles (RGPD)
        $id = $user->getId();
        $user->setEmail("deleted_{$id}@anonyme.local");
        $user->setNom('Utilisateur supprimé');
        $user->setPrenom(null);
        $user->setLocalisation(null);
        $user->setLogo(null);

        // Invalidation des accès : mot de passe aléatoire + roles vides
        $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(32))));
        $user->setRoles([]);

        $this->em->flush();

        $this->logger->info('account_anonymized', [
            'user_id' => $id,
            'ip'      => $this->requestStack->getCurrentRequest()?->getClientIp(),
        ]);
    }
}
