<?php
header('Content-Type: application/json');
require '../config.php';
require '../vendor/erusev/parsedown/Parsedown.php';

// Initialize Parsedown
$Parsedown = new Parsedown();

try {
    // Fetch posts
    $stmt = $pdo->query("SELECT posts.id, posts.title, posts.content, posts.image_path, users.username, posts.created_at, users.profile_photo, location_name, latitude, longitude
                          FROM posts
                          JOIN users ON posts.user_id = users.id
                          ORDER BY posts.created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch tags
    $stmt = $pdo->query("SELECT tags.id, tags.name, post_tags.post_id FROM tags INNER JOIN post_tags ON tags.id = post_tags.tag_id");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch post files
    $stmt = $pdo->query("SELECT post_id, file_path, original_filename FROM post_files");
    $postFiles = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $postFiles[$row['post_id']][] = [
            'file_path' => $row['file_path'],
            'original_filename' => $row['original_filename']
        ];
    }

    // Process posts
    $result = [];
    foreach ($posts as $post) {
        $postTags = array_filter($tags, fn($t) => $t['post_id'] == $post['id']);
        $formattedTags = array_map(fn($t) => $t['name'], $postTags);

        $result[] = [
            'id' => $post['id'],
            'title' => $post['title'],
            'content' => $post['content'],
            'image' => $post['image_path'],
            'username' => $post['username'],
            'profile_photo' => $post['profile_photo'] ?: 'profile_photos/default_profile.png',
            'created_at' => $post['created_at'],
            'location' => [
                'name' => $post['location_name'],
                'latitude' => $post['latitude'],
                'longitude' => $post['longitude']
            ],
            'tags' => $formattedTags,
            'files' => $postFiles[$post['id']] ?? []
        ];
    }

    echo json_encode(['success' => true, 'posts' => $result], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
