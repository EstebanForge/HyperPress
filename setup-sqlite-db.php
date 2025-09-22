<?php

/**
 * SQLite Database Setup Script for WordPress Testing
 */

// Create SQLite database for WordPress tests
$dbPath = __DIR__ . '/tests/db/test.sqlite';

if (file_exists($dbPath)) {
    unlink($dbPath);
}

try {
    // Create SQLite database
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set pragmas for better performance
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA synchronous = NORMAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    // Create WordPress database tables (simplified version for testing)
    $tables = [
        // WordPress core tables
        "CREATE TABLE IF NOT EXISTS options (
            option_id INTEGER PRIMARY KEY AUTOINCREMENT,
            option_name VARCHAR(191) NOT NULL UNIQUE,
            option_value LONGTEXT NOT NULL,
            autoload VARCHAR(20) DEFAULT 'yes'
        )",
        
        "CREATE TABLE IF NOT EXISTS posts (
            ID INTEGER PRIMARY KEY AUTOINCREMENT,
            post_author INTEGER DEFAULT 0,
            post_date DATETIME DEFAULT '0000-00-00 00:00:00',
            post_date_gmt DATETIME DEFAULT '0000-00-00 00:00:00',
            post_content LONGTEXT,
            post_title TEXT,
            post_excerpt TEXT,
            post_status VARCHAR(20) DEFAULT 'publish',
            comment_status VARCHAR(20) DEFAULT 'open',
            ping_status VARCHAR(20) DEFAULT 'open',
            post_password VARCHAR(255) DEFAULT '',
            post_name VARCHAR(200) DEFAULT '',
            to_ping TEXT,
            pinged TEXT,
            post_modified DATETIME DEFAULT '0000-00-00 00:00:00',
            post_modified_gmt DATETIME DEFAULT '0000-00-00 00:00:00',
            post_content_filtered LONGTEXT,
            post_parent INTEGER DEFAULT 0,
            guid VARCHAR(255) DEFAULT '',
            menu_order INTEGER DEFAULT 0,
            post_type VARCHAR(20) DEFAULT 'post',
            post_mime_type VARCHAR(100) DEFAULT '',
            comment_count INTEGER DEFAULT 0
        )",
        
        "CREATE TABLE IF NOT EXISTS postmeta (
            meta_id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER DEFAULT 0,
            meta_key VARCHAR(255) DEFAULT '',
            meta_value LONGTEXT
        )",
        
        "CREATE TABLE IF NOT EXISTS users (
            ID INTEGER PRIMARY KEY AUTOINCREMENT,
            user_login VARCHAR(60) NOT NULL,
            user_pass VARCHAR(255) NOT NULL,
            user_nicename VARCHAR(50) DEFAULT '',
            user_email VARCHAR(100) NOT NULL,
            user_url VARCHAR(100) DEFAULT '',
            user_registered DATETIME DEFAULT '0000-00-00 00:00:00',
            user_activation_key VARCHAR(255) DEFAULT '',
            user_status INTEGER DEFAULT 0,
            display_name VARCHAR(250) DEFAULT ''
        )",
        
        "CREATE TABLE IF NOT EXISTS usermeta (
            umeta_id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER DEFAULT 0,
            meta_key VARCHAR(255) DEFAULT '',
            meta_value LONGTEXT
        )",
        
        "CREATE TABLE IF NOT EXISTS terms (
            term_id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL,
            term_group INTEGER DEFAULT 0
        )",
        
        "CREATE TABLE IF NOT EXISTS term_taxonomy (
            term_taxonomy_id INTEGER PRIMARY KEY AUTOINCREMENT,
            term_id INTEGER DEFAULT 0,
            taxonomy VARCHAR(32) NOT NULL,
            description LONGTEXT NOT NULL,
            parent INTEGER DEFAULT 0,
            count INTEGER DEFAULT 0
        )",
        
        "CREATE TABLE IF NOT EXISTS term_relationships (
            object_id INTEGER DEFAULT 0,
            term_taxonomy_id INTEGER DEFAULT 0,
            term_order INTEGER DEFAULT 0
        )"
    ];
    
    // Execute all table creation queries
    foreach ($tables as $table) {
        $pdo->exec($table);
    }
    
    // Insert basic WordPress options
    $options = [
        ['siteurl', 'http://example.org'],
        ['home', 'http://example.org'],
        ['blogname', 'Test Blog'],
        ['blogdescription', 'Just another WordPress site'],
        ['users_can_register', '0'],
        ['default_role', 'subscriber'],
        ['timezone_string', 'UTC'],
        ['date_format', 'F j, Y'],
        ['time_format', 'g:i a'],
        ['start_of_week', '1'],
        ['WPLANG', ''],
        ['blog_charset', 'UTF-8'],
        ['html_type', 'text/html'],
        ['admin_email', 'admin@example.org'],
        ['ping_sites', 'http://rpc.pingomatic.com/'],
        ['comment_moderation', '0'],
        ['moderation_notify', '1'],
        ['comment_registration', '0'],
        ['show_on_front', 'posts'],
        ['page_on_front', '0'],
        ['page_for_posts', '0'],
        ['posts_per_page', '10'],
        ['posts_per_rss', '10'],
        ['rss_use_excerpt', '0'],
        ['blog_public', '1'],
        ['default_ping_status', 'open'],
        ['default_comment_status', 'open'],
        ['require_name_email', '1'],
        ['comment_notify', '1'],
        ['comment_max_links', '2'],
        ['moderation_keys', ''],
        ['close_comments_for_old_posts', '0'],
        ['close_comments_days_old', '14'],
        ['thread_comments', '1'],
        ['thread_comments_depth', '5'],
        ['page_comments', '0'],
        ['comments_per_page', '50'],
        ['default_comments_page', 'newest'],
        ['comment_order', 'asc'],
        ['use_smilies', '1'],
        ['smilies_convert', '1'],
        ['use_trackback', '1'],
        ['gmt_offset', '0'],
        ['default_email_category', '1'],
        ['default_link_category', '2'],
        ['default_post_format', '0'],
        ['template', 'twentytwentyone'],
        ['stylesheet', 'twentytwentyone'],
        ['active_plugins', 'a:0:{}'],
        ['current_theme', 'Twenty Twenty-One'],
        ['theme_switched', '0'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO options (option_name, option_value, autoload) VALUES (?, ?, 'yes')");
    foreach ($options as $option) {
        $stmt->execute($option);
    }
    
    // Create a default admin user
    $stmt = $pdo->prepare("INSERT INTO users (user_login, user_pass, user_email, user_registered, display_name) VALUES (?, ?, ?, datetime('now'), ?)");
    $stmt->execute(['admin', password_hash('password', PASSWORD_DEFAULT), 'admin@example.org', 'Admin']);
    
    echo "SQLite database created successfully at: $dbPath\n";
    
} catch (PDOException $e) {
    echo "Error creating SQLite database: " . $e->getMessage() . "\n";
    exit(1);
}