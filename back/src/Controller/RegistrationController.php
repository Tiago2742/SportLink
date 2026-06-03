<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\UtilisateurNiveau;
use App\Enum\TypeUtilisateur;
use App\Repository\NiveauRepository;
use App\Repository\SportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function inscrire(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        SportRepository $sportRepo,
        NiveauRepository $niveauRepo,
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true);

        $champsRequis = ['email', 'password', 'nom', 'type'];
        foreach ($champsRequis as $champ) {
            if (empty($donnees[$champ])) {
                return $this->json(['erreur' => "Le champ \"$champ\" est requis."], 400);
            }
        }

        try {
            $typeEnum = TypeUtilisateur::from($donnees['type']);
        } catch (\ValueError) {
            return $this->json(['erreur' => 'Type invalide. Valeurs acceptées : club, joueur.'], 400);
        }

        if ($typeEnum === TypeUtilisateur::Joueur && empty($donnees['prenom'])) {
            return $this->json(['erreur' => 'Le champ "prenom" est requis pour un compte joueur.'], 400);
        }

        if ($em->getRepository(Utilisateur::class)->findOneBy(['email' => $donnees['email']])) {
            return $this->json(['erreur' => 'Cette adresse email est déjà utilisée.'], 409);
        }

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail($donnees['email']);
        $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $donnees['password']));
        $utilisateur->setNom($donnees['nom']);
        $utilisateur->setPrenom(
            $typeEnum === TypeUtilisateur::Club
                ? (isset($donnees['prenom']) && $donnees['prenom'] !== '' ? $donnees['prenom'] : null)
                : $donnees['prenom'],
        );
        $utilisateur->setType($typeEnum);
        $utilisateur->setLocalisation($donnees['localisation'] ?? null);
        $utilisateur->setDateInscription(new \DateTime());

        // Sports optionnels : [{sportId: N, niveauId: M}, ...]
        foreach ($donnees['sports'] ?? [] as $entree) {
            if (empty($entree['sportId']) || empty($entree['niveauId'])) {
                continue;
            }

            $sport = $sportRepo->find((int) $entree['sportId']);
            if (!$sport) {
                continue;
            }

            $niveau = $niveauRepo->find((int) $entree['niveauId']);
            // R4 : le niveau doit appartenir au sport
            if (!$niveau || $niveau->getSport() !== $sport) {
                continue;
            }

            $un = new UtilisateurNiveau();
            $un->setSport($sport);
            $un->setNiveau($niveau);
            $utilisateur->addNiveau($un);
        }

        $erreurs = $validator->validate($utilisateur);
        if (count($erreurs) > 0) {
            $messages = [];
            foreach ($erreurs as $erreur) {
                $messages[$erreur->getPropertyPath()] = $erreur->getMessage();
            }
            return $this->json(['erreurs' => $messages], 422);
        }

        $em->persist($utilisateur);
        $em->flush();

        return $this->json($utilisateur, 201, [], [
            'groups' => ['utilisateur:read', 'utilisateur:detail', 'utilisateur_niveau:read', 'sport:read', 'niveau:read'],
        ]);
    }
}
