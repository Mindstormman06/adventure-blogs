<?php

class Post {
    private $pdo;
    private $Parsedown;
    private $purifier;

    public function __construct($pdo, $Parsedown, $purifier) {
        $this->pdo = $pdo;
        $this->Parsedown = $Parsedown;
        $this->purifier = $purifier;
    }

    public function getParsedown() {
        return $this->Parsedown;
    }

    public function getPurifier() {
        return $this->purifier;
    }

    public function getAllPosts() {
        $stmt = $this->pdo->query("
            SELECT posts.id, posts.title, posts.content, posts.image_path, users.username, posts.created_at, users.profile_photo, location_name, latitude, longitude
            FROM posts 
            JOIN users ON posts.user_id = users.id 
            ORDER BY posts.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function getUserPosts($userId) {
        $stmt = $this->pdo->prepare("SELECT id, title, image_path, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getPostById($postId) {
        $stmt = $this->pdo->prepare("SELECT posts.*, users.username, users.profile_photo FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
        $stmt->execute([$postId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllTags() {
        $stmt = $this->pdo->query("
            SELECT tags.id, tags.name, post_tags.post_id
            FROM tags
            INNER JOIN post_tags ON tags.id = post_tags.tag_id
        ");
        return $stmt->fetchAll();
    }

    public function getAllPostFiles() {
        $stmt = $this->pdo->query("SELECT post_id, file_path, original_filename FROM post_files");
        $postFiles = [];
        $postFilesOriginal = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $postFiles[$row['post_id']][] = $row['file_path'];
            $postFilesOriginal[$row['post_id']][] = $row['original_filename'];
        }
        return [$postFiles, $postFilesOriginal];
    }

    public function getCommentsByPostId($postId) {
        $stmt = $this->pdo->prepare("SELECT comments.*, users.username, users.profile_photo FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? AND deleted_at IS NULL ORDER BY comments.created_at ASC");
        $stmt->execute([$postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addComment($postId, $userId, $commentText, $parentId = null) {
        $stmt = $this->pdo->prepare("INSERT INTO comments (post_id, user_id, comment_text, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$postId, $userId, $commentText, $parentId]);
    }

    public function editComment($commentId, $userId, $commentText) {
        $stmt = $this->pdo->prepare("UPDATE comments SET comment_text = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentText, $commentId, $userId]);
    }

    public function deleteComment($commentId, $userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentId, $userId]);

        if ($stmt->rowCount() == 0) {
            throw new Exception("You do not have permission to delete this comment.");
        }

        $stmt = $this->pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);
    }

    public function formatPostContent($content) {
        $postContent = $this->Parsedown->text($content);
        return $this->purifier->purify($postContent);
    }

    public function formatDate($datetime, $timezone = 'UTC') {
        $date = new DateTime($datetime, new DateTimeZone($timezone));
        return $date->format('Y/m/d');  // Format as YYYY/MM/DD
    }

    public function timeAgo($datetime, $timezone = 'UTC') {
        $now = new DateTime("now", new DateTimeZone($timezone));
        $postTime = new DateTime($datetime, new DateTimeZone($timezone));
        $diff = $now->diff($postTime);

        if ($diff->y > 0) {
            return $diff->y . " year" . ($diff->y > 1 ? "s" : "") . " ago";
        }
        if ($diff->m > 0) {
            return $diff->m . " month" . ($diff->m > 1 ? "s" : "") . " ago";
        }
        if ($diff->d > 0) {
            if ($diff->d >= 7) {
                $weeks = floor($diff->d / 7);
                return $weeks . " week" . ($weeks > 1 ? "s" : "") . " ago";
            }
            return $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
        }
        if ($diff->h > 0) {
            return $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
        }
        if ($diff->i > 0) {
            return $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
        }
        return "Just now";
    }

    public function createPost($user_id, $title, $content, $location_name, $latitude, $longitude) {
        $stmt = $this->pdo->prepare("INSERT INTO posts (user_id, title, content, location_name, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $content, $location_name, $latitude, $longitude]);
        return $this->pdo->lastInsertId();
    }

    public function uploadFiles($post_id, $files) {
        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "video/mp4",
            "video/webm",
            "video/quicktime",
            "audio/mpeg",
            "audio/wav",
            "audio/ogg",
            "audio/mp4",
            "audio/x-m4a",
            "audio/flac"
        ];
        $maxFileSize = 100 * 1024 * 1024; // 100MB max per file
        $targetDir = "uploads/";

        $totalFiles = count($files["name"]);
        if ($totalFiles > 10) {
            throw new Exception("You can upload a maximum of 10 files.");
        }

        for ($i = 0; $i < $totalFiles; $i++) {
            if ($files["size"][$i] > $maxFileSize) {
                throw new Exception("File " . $files["name"][$i] . " is too large.");
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileType = finfo_file($finfo, $files["tmp_name"][$i]);
            finfo_close($finfo);
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Invalid file type for " . $files["name"][$i] . ".");
            }

            $fileExt = pathinfo($files["name"][$i], PATHINFO_EXTENSION);
            $originalFileName = pathinfo($files["name"][$i], PATHINFO_FILENAME);
            $customFileName = $post_id . "-" . time() . "-" . $i . "." . $fileExt;
            $filePath = $targetDir . $customFileName;
            move_uploaded_file($files["tmp_name"][$i], $filePath);

            $stmt = $this->pdo->prepare("INSERT INTO post_files (post_id, file_path, original_filename) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $filePath, $originalFileName]);
        }
    }

    public function addTags($post_id, $tagsInput) {
        $tagsArray = array_map('trim', explode(',', $tagsInput));
        foreach ($tagsArray as $tag) {
            $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
            $stmt->execute([$tag]);
            $tag_id = $stmt->fetchColumn();

            if (!$tag_id) {
                $stmt = $this->pdo->prepare("INSERT INTO tags (name) VALUES (?)");
                $stmt->execute([$tag]);
                $tag_id = $this->pdo->lastInsertId();
            }

            $this->pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$post_id, $tag_id]);
        }
    }

    public function deletePost($postId, $userId) {
        // Fetch the post
        $stmt = $this->pdo->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$post) {
            throw new Exception("Post not found.");
        }

        // Check if the current user is the author of the post or an admin
        if ($userId != $post['user_id'] && $_SESSION['role'] != 'admin') {
            throw new Exception("Access denied.");
        }

        // Fetch associated tags
        $stmt = $this->pdo->prepare("SELECT tag_id FROM post_tags WHERE post_id = ?");
        $stmt->execute([$postId]);
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch associated files
        $stmt = $this->pdo->prepare("SELECT file_path FROM post_files WHERE post_id = ?");
        $stmt->execute([$postId]);
        $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Delete the references from post_tags
        $stmt = $this->pdo->prepare("DELETE FROM post_tags WHERE post_id = ?");
        $stmt->execute([$postId]);

        // Delete tags if no other posts are referencing them
        foreach ($tags as $tag) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM post_tags WHERE tag_id = ?");
            $stmt->execute([$tag['tag_id']]);
            $tagCount = $stmt->fetchColumn();

            // If no other posts are using this tag, delete the tag
            if ($tagCount == 0) {
                $stmt = $this->pdo->prepare("DELETE FROM tags WHERE id = ?");
                $stmt->execute([$tag['tag_id']]);
            }
        }

        // Delete the comments associated with the post
        $stmt = $this->pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$postId]);

        // Delete the post itself
        $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$postId]);

        // Delete the media files if they exist
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}