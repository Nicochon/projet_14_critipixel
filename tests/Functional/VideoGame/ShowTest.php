<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ShowTest extends FunctionalTestCase
{
    public function testShouldShowVideoGame(): void
    {
        // on envoit une requete get
        $this->get('/jeu-video-0');
        // vérifier que la réponse est ok
        self::assertResponseIsSuccessful(); // assertion #1
        // on recherche un h1 qui contient le nom jeux video 0 si il n'existe pas c'est mort
        self::assertSelectorTextContains('h1', 'Jeu vidéo 0'); // assertion #2
    }

//    public function testShouldNotPostReviewWithoutRating(): void
//    {
//        $this->login();
//
//        $crawler = $this->client->request('GET', '/jeu-video-49');
//        self::assertResponseIsSuccessful();
//
//        // On soumet le formulaire avec un commentaire mais sans rating
//        $form = $crawler->selectButton('Poster')->form([
//            'review[comment]' => 'Mon commentaire',
//        ]);
//
//        $this->client->submit($form);
//        self::assertResponseStatusCodeSame(Response::HTTP_OK);
//        // Vérifie que le formulaire est toujours affiché (car la soumission a échoué)
//        self::assertSelectorExists('form[name="review"]');
//
//        // Vérifie la présence du message d'erreur sur le rating
//        self::assertSelectorTextContains('.form-error-message', 'Cette valeur ne doit pas être vide');
//    }

    //vérifier le cas d'un utilisateur qui post un avis
    public function testShouldPostReview(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        // Supprimer l’utilisateur si existant
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['username' => 'testuser']);
        if ($existingUser) {
            $existingReviews = $entityManager->getRepository(Review::class)->findBy(['user' => $existingUser]);
            foreach ($existingReviews as $review) {
                $entityManager->remove($review);
            }
            $entityManager->flush();

            // Supprimer l’utilisateur
            $entityManager->remove($existingUser);
            $entityManager->flush();
        }

        // 1. Créer un utilisateur
        $user = new User();
        $user->setUsername('testuser');
        $user->setPlainPassword('testpass');
        $user->setEmail('testuser@example.com');

        $entityManager->persist($user);
        $entityManager->flush();

        // 2. Connexion
        $this->login($user->getEmail());

        // 3. Charger la page du jeu
        $crawler = $this->client->request('GET', '/jeu-video-49');
        self::assertResponseIsSuccessful();
        file_put_contents('/var/www/html/showtest.html', $this->client->getResponse()->getContent());

        // 4. Récupérer et remplir le formulaire
        $form = $crawler->filter('form[name="review"]')->form();
        $form->setValues([
            'review[rating]' => 4,
            'review[comment]' => 'Mon commentaire',
        ]);

        // 5. Soumettre le formulaire
        $crawler = $this->client->submit($form);

        // 6. Vérifier redirection
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $crawler = $this->client->followRedirect();

        // 7. Vérifier que l’avis apparaît dans la page
        self::assertSelectorTextContains('div.list-group-item:last-child h3', 'testuser');
        self::assertSelectorTextContains('div.list-group-item:last-child p', 'Mon commentaire');
        self::assertSelectorTextContains('div.list-group-item:last-child span.value', '4');

        // 8. Vérifier en base
        $review = self::getContainer()
            ->get('doctrine')
            ->getRepository(\App\Model\Entity\Review::class)
            ->findOneBy(['comment' => 'Mon commentaire', 'rating' => 4]);
        self::assertNotNull($review);

        $videoGame = self::getContainer()
            ->get('doctrine')
            ->getRepository(\App\Model\Entity\VideoGame::class)
            ->findOneBy(['slug' => 'jeu-video-49']);

        self::assertSame('testuser', $review->getUser()->getUsername());
        self::assertSame($videoGame->getId(), $review->getVideoGame()->getId());

        // 9. Vérifier que le formulaire n’existe plus après soumission
        self::assertSelectorNotExists('form[name="review"]');
    }
}
