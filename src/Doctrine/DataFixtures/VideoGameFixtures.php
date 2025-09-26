<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();

        $tagNames = ['rpg', 'multijoueur', 'fps', 'tps', 'gestion', 'action'];
        $tags = array_map(fn (string $name) => (new Tag())->setName($name), $tagNames);
        array_walk($tags, [$manager, 'persist']);
        $manager->flush();

        $videoGames = [];
        for ($i = 0; $i < 50; ++$i) {
            $videoGames[] = (new VideoGame())
                ->setTitle(sprintf('Jeu vidéo %d', $i))
                ->setDescription($this->faker->paragraphs(10, true))
                ->setReleaseDate(new \DateTimeImmutable())
                ->setTest($this->faker->paragraphs(6, true))
                ->setRating(($i % 5) + 1)
                ->setImageName(sprintf('video_game_%d.png', $i))
                ->setImageSize(2_098_872);
        }

        // Associer aléatoirement des tags aux jeux vidéo
        foreach ($videoGames as $game) {
            shuffle($tags);
            foreach (array_slice($tags, 0, 2) as $tag) {
                $game->getTags()->add($tag);
            }
        }

        foreach ($videoGames as $videoGame) {
            $manager->persist($videoGame);
        }

        $manager->flush();

        // Ajouter des reviews aux jeux vidéo
        foreach ($videoGames as $game) {
            foreach ($users as $user) {
                $review = (new Review())
                    ->setUser($user)
                    ->setVideoGame($game)
                    ->setRating(rand(1, 5))
                    ->setComment($this->faker->sentence());

                $game->getReviews()->add($review);
                $manager->persist($review);

                $this->calculateAverageRating->calculateAverage($game);
                $this->countRatingsPerValue->countRatingsPerValue($game);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
