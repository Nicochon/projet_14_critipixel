<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Tag;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class FilterTest extends FunctionalTestCase
{
    private EntityManagerInterface $manager;
    private VideoGame $testGame;
    private Tag $testTag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->client->getContainer()->get('doctrine')->getManager();
        //
        //        // 1. Créer et persister l'entité Tag
        //        $this->testTag = (new Tag())
        //            ->setName('toto');
        //        $this->manager->persist($this->testTag);
        //
        //        // Un premier flush() est nécessaire pour que l'ID et le code du Tag soient générés
        //        $this->manager->flush();
        //
        //        // 2. Créer l'entité VideoGame
        //        $this->testGame = (new VideoGame())
        //            ->setTitle('Test Game with toto')
        //            ->setDescription('Test Game with toto')
        //            ->setRating(4)
        //            ->setReleaseDate(new \DateTimeImmutable());
        //
        //        // 3. Associer le Tag avec le VideoGame
        //        $this->testGame->addTag($this->testTag);
        //
        //        // 4. Persister l'entité VideoGame
        //        $this->manager->persist($this->testGame);
        //
        //        // Un second flush() pour persister l'entité VideoGame et la relation dans la table de jointure
        //        $this->manager->flush();
    }

    public function testFilterBySingleTag(): void
    {
        $crawler = $this->client->request('GET', '/'); // ou l’URL de ta page
        $html = $this->client->getResponse()->getContent();
        file_put_contents('/var/www/html/debug.html', $html);
        $form = $crawler->filter('form[name="filter"]')->form();

        // Correction : Utilisez la méthode setValues() sur l'objet form
        $form->setValues(['filter[tags]' => [1]]);

        $crawler = $this->client->submit($form);

        self::assertResponseIsSuccessful();

        $nodes = $crawler->filter('article.game-card');
        self::assertGreaterThan(0, $nodes->count(), 'Il doit y avoir au moins un jeu affiché');

        foreach ($nodes as $node) {
            $html = $node->textContent;
            self::assertStringContainsStringIgnoringCase(
                'rpg',
                $html,
                'Le jeu affiché doit être associé au tag RPG'
            );
        }
    }

    public function testFilterByMultipleTags(): void
    {
        // 1. Charger la page avec plusieurs tags
        $crawler = $this->client->request('GET', '/?filter[tags][]=1&filter[tags][]=6');
        self::assertResponseIsSuccessful();

        // 2. Récupérer tous les jeux affichés
        $nodes = $crawler->filter('article.game-card');
        file_put_contents('/var/www/html/node.html', $nodes->outerHtml()); // debug HTML complet
        self::assertGreaterThan(0, $nodes->count(), 'Il doit y avoir au moins un jeu affiché');

        // 3. Vérifier que chaque jeu contient au moins un des tags attendus
        $expectedTagNames = ['rpg', 'action'];

        foreach ($nodes as $node) {
            $tags = [];
            foreach ($node->getElementsByTagName('span') as $span) {
                if ('tag' === $span->getAttribute('class')) {
                    $tags[] = strtolower($span->textContent);
                }
            }

            // Vérifier qu'au moins un des tags attendus est présent
            $found = count(array_intersect($tags, $expectedTagNames)) > 0;

            self::assertTrue(
                $found,
                sprintf(
                    'Le jeu affiché doit contenir au moins un des tags attendus (%s). Tags trouvés : %s',
                    implode(', ', $expectedTagNames),
                    implode(', ', $tags)
                )
            );
        }
    }

    public function testFilterByNonExistentTag(): void
    {
        //        https://www.critipixel.fr/?page=1&limit=10&sorting=ReleaseDate&direction=Descending&filter[search]=&filter[tags][]=999
        $crawler = $this->client->request('GET', '/?filter[tags][]=999');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('article.game-card')->count());
    }
}
