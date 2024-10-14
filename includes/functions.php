<?php

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function get_user_by_id($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function update_user_profile($pdo, $user_id, $email, $bio) {
    $stmt = $pdo->prepare("UPDATE users SET email = ?, bio = ? WHERE id = ?");
    return $stmt->execute([$email, $bio, $user_id]);
}

function get_user_comments($pdo, $user_id, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT c.id, c.content, c.created_at, a.id as article_id, a.title as article_title
        FROM comments c
        JOIN articles a ON c.article_id = a.id
        WHERE c.user_id = :user_id
        ORDER BY c.created_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function update_comment($pdo, $comment_id, $user_id, $content) {
    $stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ? AND user_id = ?");
    return $stmt->execute([$content, $comment_id, $user_id]);
}

function delete_comment($pdo, $comment_id, $user_id) {
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    return $stmt->execute([$comment_id, $user_id]);
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_username($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function validate_password($password) {
    return strlen($password) >= 8;
}

function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function display_error($message) {
    return "<p class='error'>$message</p>";
}

function display_success($message) {
    return "<p class='success'>$message</p>";
}

function redirect($location) {
    header("Location: $location");
    exit;
}

function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function get_related_articles($pdo, $article_id, $category_id, $limit = 3) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.created_at, u.username as author
        FROM articles a
        JOIN users u ON a.author_id = u.id
        WHERE a.id != ? AND a.category_id = ?
        ORDER BY a.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$article_id, $category_id, $limit]);
    return $stmt->fetchAll();
}

function get_article_tags($pdo, $article_id) {
    $stmt = $pdo->prepare("
        SELECT t.id, t.name
        FROM tags t
        JOIN article_tags at ON t.id = at.tag_id
        WHERE at.article_id = ?
    ");
    $stmt->execute([$article_id]);
    return $stmt->fetchAll();
}

function track_page_view($pdo, $page_url) {
    $stmt = $pdo->prepare("INSERT INTO page_views (page_url, view_count) 
                           VALUES (?, 1) 
                           ON DUPLICATE KEY UPDATE 
                           view_count = view_count + 1, 
                           last_viewed = CURRENT_TIMESTAMP");
    $stmt->execute([$page_url]);
}

function track_article_view($pdo, $article_id) {
    $stmt = $pdo->prepare("INSERT INTO article_views (article_id, view_count) 
                           VALUES (?, 1) 
                           ON DUPLICATE KEY UPDATE 
                           view_count = view_count + 1, 
                           last_viewed = CURRENT_TIMESTAMP");
    $stmt->execute([$article_id]);
}

function get_popular_articles($pdo, $limit = 5) {
    $stmt = $pdo->prepare("SELECT a.id, a.title, av.view_count 
                           FROM articles a 
                           JOIN article_views av ON a.id = av.article_id 
                           ORDER BY av.view_count DESC 
                           LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function upload_profile_picture($file) {
    $target_dir = "uploads/profile_pictures/";
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_file_name = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_file_name;

    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return "File is not an image.";
    }

    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return "Sorry, your file is too large. Max file size is 5MB.";
    }

    // Allow certain file formats
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif" ) {
        return "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    }

    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $new_file_name;
    } else {
        return "Sorry, there was an error uploading your file.";
    }
}

function get_user_articles($pdo, $user_id, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.created_at, c.name as category
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        WHERE a.author_id = :user_id
        ORDER BY a.created_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function upload_image($file, $upload_dir) {
    $target_file = $upload_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return false;
    }
    
    // Check file size
    if ($file["size"] > 500000) {
        return false;
    }
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" ) {
        return false;
    }
    
    // Generate a unique filename
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return 'uploads/' . $new_filename;
    } else {
        return false;
    }
}

function update_article_tags($pdo, $article_id, $tags) {
    // Remove existing tags
    $stmt = $pdo->prepare("DELETE FROM article_tags WHERE article_id = ?");
    $stmt->execute([$article_id]);

    // Add new tags
    foreach ($tags as $tag_name) {
        $tag_name = trim($tag_name);
        if (!empty($tag_name)) {
            // Check if the tag exists, if not create it
            $stmt = $pdo->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
            $stmt->execute([$tag_name]);
            
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
            $stmt->execute([$tag_name]);
            $tag_id = $stmt->fetchColumn();
            
            // Add the tag to the article
            $stmt = $pdo->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$article_id, $tag_id]);
        }
    }
}

function highlight_search_term($text, $search_term) {
    $search_term = preg_quote($search_term, '/');
    return preg_replace("/($search_term)/i", '<mark>$1</mark>', $text);
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
