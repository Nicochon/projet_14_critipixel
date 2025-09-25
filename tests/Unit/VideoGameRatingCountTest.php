<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Model\Entity\VideoGame;
use App\Model\Entity\Review;
use App\Model\Entity\User;

class VideoGameRatingCountTest extends TestCase
{
    public function testNumberOfRatingsPerValue(): void
    {
        // Créer un jeu vidéo
        $game = new VideoGame();
        $game->setTitle('Test Game');

        // Créer des utilisateurs
        $users = [
            new User(),
            new User(),
            new User(),
            new User(),
            new User(),
        ];

        // Créer des reviews avec différentes notes
        $ratings = [5, 4, 5, 3, 1]; // 5 deux fois, 4 une fois, 3 une fois, 1 une fois

        foreach ($ratings as $index => $rating) {
            $review = new Review();
            $review->setUser($users[$index]);
            $review->setVideoGame($game);
            $review->setRating($rating);
            $game->getReviews()->add($review);
        }

        // Initialiser le compteur des notes (1 à 5)
        $ratingsCount = array_fill(1, 5, 0);

        // Compter combien de fois chaque note apparaît
        foreach ($game->getReviews() as $review) {
            $ratingsCount[$review->getRating()]++;
        }

        // Vérifications
        $this->assertEquals(1, $ratingsCount[1]); // une note de 1
        $this->assertEquals(0, $ratingsCount[2]); // aucune note de 2
        $this->assertEquals(1, $ratingsCount[3]); // une note de 3
        $this->assertEquals(1, $ratingsCount[4]); // une note de 4
        $this->assertEquals(2, $ratingsCount[5]); // deux notes de 5
    }
}
