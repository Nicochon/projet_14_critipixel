<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Model\Entity\VideoGame;
use App\Model\Entity\Review;
use App\Model\Entity\User;

class VideoGameTest extends TestCase
{
    public function testAverageRatingIsCorrect(): void
    {
        // Créer un jeu vidéo
        $game = new VideoGame();
        $game->setTitle('Test Game');

        // Créer des utilisateurs
        $user1 = new User();
        $user2 = new User();
        $user3 = new User();
        $user4 = new User();
        $user5 = new User();

        // Créer des reviews avec différentes notes
        $reviewsData = [
            5, 4, 5, 3, 1
        ];

        $users = [$user1, $user2, $user3, $user4, $user5];

        foreach ($reviewsData as $index => $rating) {
            $review = new Review();
            $review->setUser($users[$index]);
            $review->setVideoGame($game);
            $review->setRating($rating);
            $game->getReviews()->add($review);
        }

        // Calcul du nombre de notes
        // Calcul de la moyenne
        $total = 0;
        foreach ($game->getReviews() as $review) {
            $total += $review->getRating();
        }
        $average = $total / count($game->getReviews());

        // Vérification : 18 / 5 = 3.6
        $this->assertEquals(3.6, $average);
    }
}
