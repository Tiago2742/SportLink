<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeocodageService
{
    private const OPENCAGE_URL = 'https://api.opencagedata.com/geocode/v1/json';
    private const TIMEOUT_SECONDES = 3;
    private const ADRESSE_MAX_CHARS = 200;

    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(OPENCAGE_API_KEY)%')]
        private string $apiKey,
        #[Autowire(service: 'monolog.logger.security')]
        private LoggerInterface $logger,
    ) {}

    /**
     * Convertit une adresse en coordonnées GPS via OpenCage.
     * Retourne null en cas d'erreur, d'adresse introuvable ou de clé non configurée.
     * Ne lève jamais d'exception — la création de match ne doit jamais échouer à cause du géocodage.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function geocoder(string $adresse): ?array
    {
        if ($this->apiKey === '') {
            return null;
        }

        $adresse = substr(trim($adresse), 0, self::ADRESSE_MAX_CHARS);
        if ($adresse === '') {
            return null;
        }

        try {
            $reponse = $this->client->request('GET', self::OPENCAGE_URL, [
                'query' => [
                    'q'              => $adresse,
                    'key'            => $this->apiKey,
                    'limit'          => 1,
                    'no_annotations' => 1,
                    'language'       => 'fr',
                ],
                'timeout' => self::TIMEOUT_SECONDES,
            ]);

            $donnees = $reponse->toArray();

            if (empty($donnees['results'])) {
                return null;
            }

            $geometry = $donnees['results'][0]['geometry'] ?? null;
            if (!isset($geometry['lat'], $geometry['lng'])) {
                return null;
            }

            return [
                'latitude'  => (float) $geometry['lat'],
                'longitude' => (float) $geometry['lng'],
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('geocoding_failed', [
                'error'          => $e->getMessage(),
                'adresse_length' => strlen($adresse),
            ]);
            return null;
        }
    }
}
