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

$users = [
    ['admin', 'pass'],
    ['alice', 'sunnydays'],
    ['ben', 'mountains'],
    ['clara', 'booklover'],
    ['daniel', 'weekendcook'],
    ['emma', 'gardenlife'],
    ['frank', 'vinylfan'],
];

$postSubjects = [
    'The best breakfast I have had in ages',
    'A quiet walk before the rain arrived',
    'Finally finished that novel',
    'The little café around the corner',
    'Trying a new recipe tonight',
    'An unexpectedly beautiful sunset',
    'The joy of finding an old record',
    'A very productive afternoon in the garden',
    'Three films I would happily watch again',
    'A train journey with a great view',
    'Fresh bread straight from the oven',
    'The first signs of spring',
    'A rainy afternoon and a good cup of tea',
    'Found a brilliant little bookshop today',
    'The perfect Sunday lunch',
    'Listening to music while cooking',
    'A surprisingly warm February afternoon',
    'The market was especially good today',
    'A long walk along the river',
    'Trying to grow something from seed',
    'A favourite song I had forgotten about',
    'Homemade soup weather',
    'A lovely afternoon with friends',
    'The view from the top of the hill',
    'Rediscovered an old favourite film',
    'Coffee and cake on a cold morning',
    'The garden is slowly waking up',
    'A very satisfying loaf of bread',
    'A peaceful evening at home',
    'The best thing about taking the scenic route',
    'A small win today',
    'The smell of rain after a sunny morning',
    'Found a new favourite walking route',
    'Making plans for the warmer months',
    'A simple dinner that turned out perfectly',
    'The kind of afternoon that makes you slow down',
    'A good book makes time disappear',
    'The clouds looked incredible this evening',
    'A weekend well spent',
    'Trying something I have never cooked before',
    'A favourite corner of the local park',
    'Nothing beats a slow morning',
    'A song that instantly takes me somewhere else',
    'A beautiful view on the way home',
    'The pleasure of a completely unplanned afternoon',
    'A new plant for the windowsill',
    'A surprisingly good homemade pizza',
    'The first daffodils are out',
    'A chilly morning followed by sunshine',
    'Found some great second-hand books',
    'A perfect evening for a film',
    'The smell of fresh coffee in the morning',
    'A walk that turned into an adventure',
    'Trying to make the perfect cake',
    'A very good day for being outdoors',
    'The simple pleasure of clean sheets',
    'A quiet night with music and a book',
    'Spring cannot come soon enough',
    'A memorable meal with good company',
    'The local park was full of birds today',
    'A beautiful little moment I nearly missed',
    'Cooking without a recipe for once',
    'An afternoon spent doing absolutely nothing',
    'A new favourite tea',
    'The sun finally came out',
    'A walk through the woods in winter',
    'A very good reason to bake something',
    'The nicest light I have seen all week',
    'A lazy Sunday and no complaints',
];

$comments = [
    'That sounds lovely.',
    'I completely agree with this.',
    'This made me smile.',
    'That sounds like a perfect afternoon.',
    'I need to try this sometime.',
    'What a great idea.',
    'That sounds delicious.',
    'Beautiful.',
    'I have been meaning to do something similar.',
    'This sounds wonderful.',
    'Now I want to do the same.',
    'That sounds like a good day.',
    'I can almost picture it.',
    'Very nice.',
    'That is one of the best little pleasures.',
    'I love this.',
    'Sounds like a memorable one.',
    'I might have to give that a go.',
    'This is exactly my kind of afternoon.',
    'Lovely little update.',
];

$replies = [
    'Absolutely!',
    'Yes, it really was.',
    'You should definitely try it.',
    'I would recommend it.',
    'That was my favourite part too.',
    'Same here.',
    'It was even better than expected.',
    'I think you would enjoy it.',
    'Hopefully I can do it again soon.',
    'It was worth the effort.',
    'Could not agree more.',
    'I am already looking forward to doing it again.',
];

$pdo->beginTransaction();

try {
    $insertUser = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
    $userIds = [];

    foreach ($users as [$username, $password]) {
        $insertUser->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        $userIds[$username] = (int)$pdo->lastInsertId();
    }

    $insertPost = $pdo->prepare(
        'INSERT INTO posts (user_id, content, created_at, updated_at, edit_count) VALUES (?, ?, ?, NULL, 0)'
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

    $usedSubjects = [];
    $postIds = [];
    $postOwners = [];

    $startDate = new DateTimeImmutable('2026-02-01 00:00:00');
    $endDate = new DateTimeImmutable('now');
    $startTimestamp = $startDate->getTimestamp();
    $endTimestamp = $endDate->getTimestamp();

    foreach ($userIds as $username => $userId) {
        $postCount = random_int(6, 10);

        for ($i = 0; $i < $postCount; $i++) {
            do {
                $subject = $postSubjects[array_rand($postSubjects)];
            } while (isset($usedSubjects[$username . '|' . $subject]));

            $usedSubjects[$username . '|' . $subject] = true;

            $createdAt = date('Y-m-d H:i:s', random_int($startTimestamp, $endTimestamp));
            $postText = $subject;

            $insertPost->execute([$userId, $postText, $createdAt]);

            $postId = (int)$pdo->lastInsertId();
            $postIds[] = $postId;
            $postOwners[$postId] = $userId;

            $postTimestamp = strtotime($createdAt);
            $commentCount = random_int(2, 4);

            for ($c = 0; $c < $commentCount; $c++) {
                $commentUserId = $userIds[array_rand($userIds)];
                $commentTimestamp = random_int($postTimestamp, min($postTimestamp + (7 * 86400), $endTimestamp));
                $commentAt = date('Y-m-d H:i:s', $commentTimestamp);

                $insertComment->execute([
                    $postId,
                    $commentUserId,
                    null,
                    $comments[array_rand($comments)],
                    $commentAt,
                ]);

                $commentId = (int)$pdo->lastInsertId();

                if (random_int(0, 100) < 70) {
                    $replyUserId = $userIds[array_rand($userIds)];
                    $replyTimestamp = random_int($commentTimestamp, min($commentTimestamp + (3 * 86400), $endTimestamp));
                    $replyAt = date('Y-m-d H:i:s', $replyTimestamp);

                    $insertComment->execute([
                        $postId,
                        $replyUserId,
                        $commentId,
                        $replies[array_rand($replies)],
                        $replyAt,
                    ]);
                }
            }
        }
    }

    $allUserIds = array_values($userIds);
    $now = date('Y-m-d H:i:s');

    foreach ($allUserIds as $followerId) {
        foreach ($allUserIds as $followingId) {
            if ($followerId === $followingId || random_int(0, 100) >= 45) {
                continue;
            }

            $insertFollow->execute([$followerId, $followingId, $now]);
        }
    }

    foreach ($postIds as $postId) {
        $ownerId = $postOwners[$postId];

        foreach ($allUserIds as $userId) {
            if ($userId === $ownerId || random_int(0, 100) >= 35) {
                continue;
            }

            $insertLike->execute([$userId, $postId, $now]);
        }
    }

    $pdo->commit();

    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $postCount = (int)$pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    $commentCount = (int)$pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();
    $likeCount = (int)$pdo->query('SELECT COUNT(*) FROM likes')->fetchColumn();
    $followCount = (int)$pdo->query('SELECT COUNT(*) FROM follows')->fetchColumn();

    echo "Seed complete.\n";
    echo "Users: {$userCount}\n";
    echo "Posts: {$postCount}\n";
    echo "Comments/replies: {$commentCount}\n";
    echo "Likes: {$likeCount}\n";
    echo "Follows: {$followCount}\n";
    echo "Admin login: admin / pass\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Seeder failed: {$e->getMessage()}\n");
    exit(1);
}
