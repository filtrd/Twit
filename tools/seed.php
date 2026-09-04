<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("This seeder can only be run from the command line.\n");
}

require_once __DIR__ . '/../inc/database.php';

$pdo = db();

if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    exit("Database is not empty. Delete data/microblog.sqlite and run the seeder again for a fresh dataset.\n");
}

if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
    exit("This seeder requires the PHP GD extension with WebP support.\n");
}

$users = [
    ['admin', 'pass'],
    ['Enigma', 'sunnydays'],
    ['NebulaNinja', 'mountains'],
    ['paperbound', 'booklover'],
    ['Pulse', 'weekendcook'],
    ['Hornpub', 'gardenlife'],
    ['LagSpawn', 'vinylfan'],
];

$topics = [
    'breakfast' => [
        'posts' => [
            'Made scrambled eggs on toast this morning and somehow managed to get the eggs exactly right. Soft in the middle, crisp toast, plenty of pepper, and coffee strong enough to wake up the neighbours.',
            'I have decided that a slow breakfast is one of the nicest small luxuries. No phone for twenty minutes, warm toast, fruit, and a second cup of coffee while the kitchen gradually fills with sunlight.',
            'Tried making pancakes without measuring anything today. The first two were questionable, but by the third I had the heat right and ended up with a surprisingly good stack. Maple syrup definitely did most of the work.',
        ],
        'comments' => ['The second cup of coffee is an essential part of the plan.', 'Getting the first pancake right is basically impossible.', 'Now I want pancakes for breakfast.', 'That sounds like a genuinely peaceful start to the day.'],
        'replies' => ['Exactly. The first one is just research.', 'The coffee was absolutely doing some heavy lifting.', 'Honestly, the slow part was my favourite.'],
    ],
    'walking' => [
        'posts' => [
            'Went out for a walk just before the rain and caught that strange bit of winter light where everything looks brighter than it should. Came home five minutes before the rain started, which feels like a small victory.',
            'Found a new route through the park today. It adds about fifteen minutes to the usual walk, but there are fewer cars, more trees, and a bench overlooking the river that I had somehow never noticed before.',
            'A short walk turned into nearly two hours because I kept taking the next path just to see where it went. I should probably have checked the weather first, but the clouds stayed away and it was worth it.',
        ],
        'comments' => ['Those accidental long walks are usually the best ones.', 'A quiet route makes such a difference.', 'That sounds like a good place to discover by accident.', 'Did you get caught by the rain in the end?'],
        'replies' => ['No rain in the end, somehow I timed it perfectly.', 'It really does. I barely saw another person.', 'I am definitely going back when the weather is better.'],
    ],
    'books' => [
        'posts' => [
            'Finally finished the novel I had been carrying around for weeks. The ending was not quite what I expected, but I liked that it trusted the reader to work out what happened rather than explaining every last detail.',
            'There is something satisfying about finding a second-hand book with someone else’s notes in the margins. I found one today with a few tiny pencil marks and a ticket tucked inside from a cinema that closed years ago.',
            'Started a book last night intending to read one chapter before bed. Four chapters later I was still awake, completely ignoring the sensible part of my brain telling me that tomorrow morning was going to arrive very quickly.',
        ],
        'comments' => ['Those are the books that make you lose track of time.', 'I love finding little pieces of someone else’s life in old books.', 'Was the ending satisfying or just surprising?', 'One chapter before bed is a dangerous promise.'],
        'replies' => ['A bit of both, but I think it worked.', 'Exactly. It makes the book feel like it has its own history.', 'I definitely regretted saying one chapter.'],
    ],
    'cafe' => [
        'posts' => [
            'Found a tiny café tucked away on a side street today. Nothing fancy, just good coffee, a couple of wooden tables, and someone playing quiet music behind the counter. I stayed much longer than planned.',
            'There is a particular kind of happiness in finding a café where nobody seems to mind if you sit with a book for an hour. Good coffee helps, obviously, but the quiet atmosphere is the real reason I will probably go back.',
            'Tried the little café everyone keeps recommending. The coffee was excellent, the cake was even better, and I now understand why people apparently queue outside on Saturday mornings.',
        ],
        'comments' => ['The quiet cafés are always worth keeping secret.', 'What cake did you have?', 'Sounds like I need to add this place to my list.', 'A good café can completely change the mood of a day.'],
        'replies' => ['Chocolate cake, and it was dangerously good.', 'I think the atmosphere was what sold me too.', 'Definitely worth trying if you are nearby.'],
    ],
    'cooking' => [
        'posts' => [
            'Tried a new pasta recipe tonight and it turned into one of those meals that tastes much more complicated than it actually was. Garlic, tomatoes, basil, parmesan, and enough chilli to make the whole kitchen smell wonderful.',
            'Made soup from whatever was left in the fridge instead of following a recipe. Carrots, lentils, onions, tomatoes, and a little smoked paprika somehow became exactly the kind of dinner I wanted on a cold evening.',
            'I finally made the recipe I had bookmarked months ago. It took longer than expected and used nearly every pan in the kitchen, but the finished meal was good enough that I immediately forgot about the washing up.',
        ],
        'comments' => ['Improvised recipes are surprisingly satisfying when they work.', 'That sounds delicious. The chilli is the important part.', 'Using every pan is the true sign of a serious dinner.', 'Would you make it again?'],
        'replies' => ['Absolutely. I would probably make a double batch next time.', 'The chilli definitely made it better.', 'Yes, although I might reduce the washing-up somehow.'],
    ],
    'sunset' => [
        'posts' => [
            'The sky went completely orange for about ten minutes this evening. I nearly ignored it because I was busy, then looked out the window and ended up standing there until the colours faded. Glad I stopped for a moment.',
            'Caught the sunset from the top of the hill today. The clouds were moving quickly and the light kept changing every few seconds, so every photo looked different even though I barely moved.',
            'One of those evenings where the sunset makes the whole street look unfamiliar. Same houses, same road, but everything had this warm golden glow for a few minutes.',
        ],
        'comments' => ['Those few minutes always disappear far too quickly.', 'Sometimes the best photos are the ones you almost did not take.', 'Golden hour makes everything look better.', 'I wish sunsets lasted about three times as long.'],
        'replies' => ['Exactly. I am glad I looked up when I did.', 'The light was changing too quickly to keep up with.', 'Same. Ten minutes feels unfairly short.'],
    ],
    'garden' => [
        'posts' => [
            'Spent the afternoon in the garden and found the first tiny signs that spring is actually on its way. A few shoots are appearing, the birds are getting noisier, and the whole place feels slightly less asleep than it did a few weeks ago.',
            'Started a few seeds on the windowsill today. I have absolutely no idea whether I am doing everything correctly, but there is something very satisfying about checking tiny pots every morning and looking for the first green shoots.',
            'Finally cleared the corner of the garden that had been bothering me all winter. It took most of the afternoon, but now there is room for a couple of new plants and the space already feels completely different.',
        ],
        'comments' => ['The first shoots always feel like a small miracle.', 'What are you hoping to grow?', 'I love that feeling when a neglected corner finally gets sorted.', 'Checking the pots every morning is half the fun.'],
        'replies' => ['Mostly herbs and a few flowers to start with.', 'Exactly. Even when nothing has changed, I still check.', 'It already feels much more useful.'],
    ],
    'films' => [
        'posts' => [
            'Rewatched one of my favourite films tonight and it still holds up surprisingly well. I had forgotten how good the soundtrack was, and there were several little details I noticed this time that completely passed me by before.',
            'Had a proper film night for the first time in ages. Lights off, snacks ready, phone in another room, and no attempt to multitask. It was much nicer than half-watching something while scrolling.',
            'Watched an old film I had never seen before and spent the first twenty minutes wondering why everyone had recommended it. Then it suddenly clicked and by the end I understood exactly why it had stayed popular for so long.',
        ],
        'comments' => ['A proper film night with the phone in another room sounds perfect.', 'Rewatching films is great when you notice new things.', 'What was the soundtrack like?', 'Sometimes a film takes a while to find its rhythm.'],
        'replies' => ['Much better than I remembered. It really carried the atmosphere.', 'Exactly. I think I appreciated it more this time.', 'The first half was slow, but the payoff was worth it.'],
    ],
    'music' => [
        'posts' => [
            'Found an old record I had completely forgotten about while sorting through a box today. Put it on while making dinner and immediately remembered why I liked it. Some songs seem to store an entire period of your life inside them.',
            'Spent the evening listening to an album from beginning to end instead of picking individual songs. I had forgotten how different an album feels when you hear the tracks in the order the artist intended.',
            'There is something very satisfying about putting a record on, turning the volume up slightly, and then actually listening instead of doing five other things at the same time.',
        ],
        'comments' => ['Old records really do bring memories back instantly.', 'Albums definitely feel different when you hear them from start to finish.', 'What record did you find?', 'Sometimes doing one thing properly is the best way to spend an evening.'],
        'replies' => ['It was an album I listened to constantly years ago.', 'That was exactly what I liked about it.', 'I think I am going to make a habit of doing this more often.'],
    ],
    'food' => [
        'posts' => [
            'Stopped at the market on the way home and came back with far more food than I needed. Fresh bread, tomatoes, apples, cheese, and a bunch of herbs. I had no plan for dinner, but the ingredients have already made the decision for me.',
            'Made a very simple lunch today with good ingredients and almost no effort. Fresh bread, ripe tomatoes, olive oil, cheese, and black pepper. It is difficult to improve on food that does not need much doing to it.',
            'The market was unusually good this morning. Lots of fresh produce, a bakery stall that smelled incredible, and enough samples to convince me that buying lunch there was probably the sensible option.',
        ],
        'comments' => ['Fresh bread makes almost any lunch better.', 'Sometimes the simplest meals are the most satisfying.', 'A market bakery is impossible to walk past.', 'What did you end up making for dinner?'],
        'replies' => ['Tomato pasta with everything I brought home.', 'Exactly. Good ingredients do most of the work.', 'I bought more bread than any reasonable person needs.'],
    ],
    'home' => [
        'posts' => [
            'Spent the evening doing absolutely nothing productive and it was wonderful. Clean sheets, a cup of tea, music in the background, and no urgent reason to leave the sofa. I think I needed a quiet night more than I realised.',
            'There is an underrated pleasure in getting the house completely tidy and then sitting down while everything stays exactly where it belongs. It probably will not last long, but for now I am enjoying the illusion of order.',
            'Rainy evening, warm lamp on, book beside me, and something cooking slowly in the oven. It is hard to complain about weather when it gives you a good excuse to stay indoors.',
        ],
        'comments' => ['Doing nothing productive is sometimes exactly what is needed.', 'The clean house feeling never lasts long enough.', 'That sounds like an ideal rainy evening.', 'Now I want tea and a book.'],
        'replies' => ['That was exactly the plan, and it worked.', 'I am enjoying it while it lasts.', 'Tea definitely made the evening better.'],
    ],
];

$userTopics = [
    'admin' => ['music', 'walking', 'home'],
    'paperbound' => ['garden', 'cafe', 'sunset'],
    'NebulaNinja' => ['walking', 'food', 'films'],
    'Pulse' => ['books', 'cafe', 'home'],
    'Hornpub' => ['cooking', 'breakfast', 'food'],
    'LagSpawn' => ['garden', 'breakfast', 'walking'],
    'Enigma' => ['music', 'films', 'cafe'],
];

$avatarPalettes = [
    [[48, 66, 84], [226, 232, 238]],
    [[76, 112, 84], [231, 239, 225]],
    [[93, 76, 58], [239, 229, 214]],
    [[91, 75, 112], [233, 226, 240]],
    [[119, 82, 64], [242, 226, 216]],
    [[57, 103, 100], [220, 238, 235]],
    [[99, 82, 55], [240, 234, 218]],
];

function progress(string $message): void
{
    echo $message . PHP_EOL;
    flush();
}

function removeGeneratedImages(string $directory): int
{
    $removed = 0;
    foreach (glob($directory . '/*.webp') ?: [] as $file) {
        $name = basename($file, '.webp');
        if (preg_match('/^[a-f0-9]{32}$/', $name) && is_file($file)) {
            unlink($file);
            $removed++;
        }
    }
    return $removed;
}

function createAvatar(string $directory, array $palette, int $variant): string
{
    $size = 150;
    $image = imagecreatetruecolor($size, $size);
    $background = imagecolorallocate($image, ...$palette[1]);
    $foreground = imagecolorallocate($image, ...$palette[0]);
    $accent = imagecolorallocate($image, min(255, $palette[0][0] + 28), min(255, $palette[0][1] + 28), min(255, $palette[0][2] + 28));

    imagefill($image, 0, 0, $background);
    imagefilledellipse($image, 75, 78, 92, 92, $foreground);

    if ($variant % 3 === 0) {
        imagefilledrectangle($image, 48, 34, 102, 58, $foreground);
    } elseif ($variant % 3 === 1) {
        imagefilledellipse($image, 75, 43, 58, 28, $foreground);
    } else {
        imagefilledpolygon($image, [44, 57, 75, 25, 106, 57], $foreground);
    }

    if ($variant % 2 === 0) {
        imagefilledellipse($image, 60, 76, 9, 9, $background);
        imagefilledellipse($image, 90, 76, 9, 9, $background);
    } else {
        imagefilledrectangle($image, 54, 72, 67, 82, $background);
        imagefilledrectangle($image, 83, 72, 96, 82, $background);
        imageline($image, 67, 77, 83, 77, $background);
    }

    imagefilledellipse($image, 75, 96, 28, 10, $accent);

    $filename = bin2hex(random_bytes(16)) . '.webp';
    $destination = $directory . '/' . $filename;

    if (!imagewebp($image, $destination, 78)) {
        unset($image);
        throw new RuntimeException('Unable to create a seeded avatar.');
    }

    unset($image);
    return $filename;
}

function createPostImage(string $directory): string
{
    // Smaller images and lower WebP quality keep the seeder fast while still
    // producing useful thumbnails for development data.
    $width = 640;
    $height = 360;
    $image = imagecreatetruecolor($width, $height);

    $sky = imagecolorallocate($image, random_int(65, 105), random_int(105, 155), random_int(150, 205));
    $ground = imagecolorallocate($image, random_int(45, 90), random_int(70, 115), random_int(45, 80));
    $sun = imagecolorallocate($image, random_int(225, 255), random_int(180, 225), random_int(90, 145));

    imagefill($image, 0, 0, $sky);
    imagefilledrectangle($image, 0, 225, $width, $height, $ground);
    imagefilledellipse($image, random_int(140, 500), random_int(70, 150), 90, 90, $sun);

    for ($shape = 0; $shape < 8; $shape++) {
        $treeX = random_int(0, $width);
        $treeY = random_int(170, 245);
        $treeHeight = random_int(35, 70);
        $treeWidth = random_int(18, 34);
        imagefilledpolygon($image, [
            $treeX, $treeY - $treeHeight,
            $treeX - $treeWidth, $treeY + 8,
            $treeX + $treeWidth, $treeY + 8,
        ], $ground);
    }

    $filename = bin2hex(random_bytes(16)) . '.webp';
    $destination = $directory . '/' . $filename;

    if (!imagewebp($image, $destination, 70)) {
        unset($image);
        throw new RuntimeException('Unable to create a seeded post image.');
    }

    unset($image);
    return $filename;
}

$pdo->beginTransaction();

try {
    $uploadBase = __DIR__ . '/../uploads';
    $avatarDir = $uploadBase . '/avatars';
    $postImageDir = $uploadBase . '/posts';

    foreach ([$avatarDir, $postImageDir] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Unable to create directory: {$dir}");
        }
    }

    // Remove only files matching the seeder's random 32-character names.
    // This makes interrupted development runs safe to repeat without deleting
    // manually uploaded assets.
    $removedAvatars = removeGeneratedImages($avatarDir);
    $removedPostImages = removeGeneratedImages($postImageDir);
    if ($removedAvatars || $removedPostImages) {
        progress("Cleaned up {$removedAvatars} generated avatars and {$removedPostImages} generated post images from the previous run.");
    }

    $insertUser = $pdo->prepare(
        'INSERT INTO users (username, password_hash, avatar_path) VALUES (?, ?, ?)'
    );
    $insertPost = $pdo->prepare(
        'INSERT INTO posts (user_id, content, image_path, created_at, updated_at, edit_count) VALUES (?, ?, ?, ?, NULL, 0)'
    );
    $insertComment = $pdo->prepare(
        'INSERT INTO comments (post_id, user_id, parent_id, content, created_at) VALUES (?, ?, ?, ?, ?)'
    );
    $insertLike = $pdo->prepare(
        'INSERT OR IGNORE INTO likes (user_id, post_id, created_at) VALUES (?, ?, ?)'
    );
    $insertFollow = $pdo->prepare(
        'INSERT OR IGNORE INTO follows (follower_id, following_id, created_at) VALUES (?, ?, ?)'
    );

    $userIds = [];
    $avatarTotal = count($users);
    progress("Creating {$avatarTotal} users and avatars...");

    foreach ($users as $index => [$username, $password]) {
        $filename = createAvatar($avatarDir, $avatarPalettes[$index], $index);
        $insertUser->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            'uploads/avatars/' . $filename,
        ]);
        $userIds[$username] = (int)$pdo->lastInsertId();
        progress('  Avatar ' . ($index + 1) . "/{$avatarTotal}: @{$username}");
    }

    $startTimestamp = (new DateTimeImmutable('2026-02-01 00:00:00', new DateTimeZone('UTC')))->getTimestamp();
    $endTimestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->getTimestamp();

    $posts = [];
    $usedPostText = [];

    progress('Building scattered post pool...');
    foreach ($userIds as $username => $userId) {
        $postCount = random_int(6, 10);
        for ($i = 0; $i < $postCount; $i++) {
            
            $topic = $userTopics[$username][array_rand($userTopics[$username])];
         $postText = $topics[$topic]['posts'][array_rand($topics[$topic]['posts'])];
            
            $posts[] = [
                'user_id' => $userId,
                'topic' => $topic,
                'content' => $postText,
                'created_timestamp' => random_int($startTimestamp, $endTimestamp),
            ];
        }
    }

    shuffle($posts);

    $postIds = [];
    $postOwners = [];
    $postTimestamps = [];
    $allUserIds = array_values($userIds);
    $allUsernames = array_keys($userIds);
    $postTotal = count($posts);
    $generatedImageCount = 0;

    progress("Creating {$postTotal} posts, conversations and occasional images...");

    foreach ($posts as $postIndex => $post) {
        $createdTimestamp = $post['created_timestamp'];
        $createdAt = gmdate('Y-m-d H:i:s', $createdTimestamp);
        $imagePath = null;

        if (random_int(1, 5) === 1) {
            $filename = createPostImage($postImageDir);
            $imagePath = 'uploads/posts/' . $filename;
            $generatedImageCount++;
        }

        $insertPost->execute([
            $post['user_id'],
            $post['content'],
            $imagePath,
            $createdAt,
        ]);

        $postId = (int)$pdo->lastInsertId();
        $postIds[] = $postId;
        $postOwners[$postId] = $post['user_id'];
        $postTimestamps[$postId] = $createdTimestamp;

        $commentCount = random_int(2, 4);
        for ($c = 0; $c < $commentCount; $c++) {
            $commentUsername = $allUsernames[array_rand($allUsernames)];
            $commentTimestamp = random_int(
                $createdTimestamp,
                min($createdTimestamp + (7 * 86400), $endTimestamp)
            );

            $commentText = $topics[$post['topic']]['comments'][array_rand($topics[$post['topic']]['comments'])];
            $insertComment->execute([
                $postId,
                $userIds[$commentUsername],
                null,
                $commentText,
                gmdate('Y-m-d H:i:s', $commentTimestamp),
            ]);

            $commentId = (int)$pdo->lastInsertId();

            if (random_int(1, 100) <= 70) {
                $replyUsername = $allUsernames[array_rand($allUsernames)];
                $replyTimestamp = random_int(
                    $commentTimestamp,
                    min($commentTimestamp + (3 * 86400), $endTimestamp)
                );

                $replyText = $topics[$post['topic']]['replies'][array_rand($topics[$post['topic']]['replies'])];
                $insertComment->execute([
                    $postId,
                    $userIds[$replyUsername],
                    $commentId,
                    $replyText,
                    gmdate('Y-m-d H:i:s', $replyTimestamp),
                ]);

                $replyId = (int)$pdo->lastInsertId();

                if (random_int(1, 100) <= 25) {
                    $nestedUsername = $allUsernames[array_rand($allUsernames)];
                    $nestedTimestamp = random_int(
                        $replyTimestamp,
                        min($replyTimestamp + (2 * 86400), $endTimestamp)
                    );

                    $insertComment->execute([
                        $postId,
                        $userIds[$nestedUsername],
                        $replyId,
                        $topics[$post['topic']]['replies'][array_rand($topics[$post['topic']]['replies'])],
                        gmdate('Y-m-d H:i:s', $nestedTimestamp),
                    ]);
                }
            }
        }

        progress('  Post ' . ($postIndex + 1) . "/{$postTotal}" . ($imagePath ? ' + image' : ''));
    }

    progress('Creating follow graph...');
$followPlan = [
    'admin' => ['Enigma', 'paperbound'],
    'Enigma' => ['admin', 'Hornpub', 'LagSpawn'],
    'NebulaNinja' => ['Pulse', 'Hornpub'],
    'paperbound' => ['Enigma', 'Hornpub'],
    'Pulse' => ['NebulaNinja', 'LagSpawn'],
    'Hornpub' => ['paperbound', 'NebulaNinja'],
    'LagSpawn' => ['Enigma', 'Pulse'],
];

    $followTime = gmdate('Y-m-d H:i:s', $endTimestamp);
    foreach ($followPlan as $follower => $followingUsers) {
        foreach ($followingUsers as $following) {
            $insertFollow->execute([
                $userIds[$follower],
                $userIds[$following],
                $followTime,
            ]);
        }
    }

    progress('Creating likes...');
    foreach ($postIds as $postId) {
        $ownerId = $postOwners[$postId];
        $likers = $allUserIds;
        shuffle($likers);
        $likeCount = random_int(1, 5);

        foreach ($likers as $userId) {
            if ($userId === $ownerId) {
                continue;
            }

            $likeTimestamp = random_int($postTimestamps[$postId], $endTimestamp);
            $insertLike->execute([
                $userId,
                $postId,
                gmdate('Y-m-d H:i:s', $likeTimestamp),
            ]);

            $likeCount--;
            if ($likeCount <= 0) {
                break;
            }
        }
    }

    $pdo->commit();

    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $postCount = (int)$pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    $commentCount = (int)$pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();
    $likeCount = (int)$pdo->query('SELECT COUNT(*) FROM likes')->fetchColumn();
    $followCount = (int)$pdo->query('SELECT COUNT(*) FROM follows')->fetchColumn();

    progress('');
    progress('Seed complete.');
    progress("Users: {$userCount}");
    progress("Posts: {$postCount}");
    progress("Posts with images: {$generatedImageCount}");
    progress("Comments/replies: {$commentCount}");
    progress("Likes: {$likeCount}");
    progress("Follows: {$followCount}");
    progress('Admin login: admin / pass');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Seeder failed: {$e->getMessage()}\n");
    exit(1);
}
