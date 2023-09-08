<?php

/*
Plugin Name: SEO keywords search and replace
Author URI:
Description: Easily allows to replace keywords in a post title, content and meta-description of your Blog posts
Version: 1.0
Author:
Text Domain: seo-keywords-search-and-replace
*/
declare(strict_types=1);

namespace KSR;

if (! defined('ABSPATH')) {
    die;
}
require_once __DIR__ . "/vendor/autoload.php";
use KSR\Helpers\StringTransformation;

define('KSR_DIR_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('KSR_DOMAIN', 'ksr');

class KSR
{
    protected static $instance = null;

    function __construct()
    {

        //Plugin settings init
        add_action('admin_menu', array($this, 'add_plugin_page'));

        //Enqueue plugin js/css
        add_action('wp_enqueue_scripts', array($this, 'assets'), 9999);
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'), 9999);

         //Handle ajax request
        add_action('wp_ajax_ajax_get_posts', array($this, 'ajax_get_posts'));
        add_action('wp_ajax_nopriv_ajax_get_posts', array($this, 'ajax_get_posts'));
        add_action('wp_ajax_ajax_update_posts', array($this, 'ajax_update_posts'));
        add_action('wp_ajax_nopriv_ajax_update_posts', array($this, 'ajax_update_posts'));
    }
    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function admin_assets($hook)
    {
        wp_enqueue_style(KSR_DOMAIN, plugins_url('/assets/css/style.css', __FILE__), array(), '1.0');
        wp_enqueue_script(KSR_DOMAIN . '-admin', plugins_url('/assets/js/admin.js', __FILE__), array( 'jquery'));
        wp_localize_script(KSR_DOMAIN . '-admin', 'ksr_ajax_object', array( 'ajaxurl' => admin_url('admin-ajax.php')));
    }


    public function add_plugin_page()
    {
        add_menu_page(
            'SEO Keyword Search & Replace',
            'KSR',
            'manage_options',
            'plugin-ksr',
            array( $this, 'plugin_create_admin_page' ),
            'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImZpbGw6I2E3YWFhZCIgdmlld0JveD0iMCAwIDUxMiA1MTIiIHJvbGU9ImltZyIgYXJpYS1oaWRkZW49InRydWUiIGZvY3VzYWJsZT0iZmFsc2UiPjxnPjxnPjxnPjxnPjxwYXRoIGQ9Ik0yMDMuNiwzOTVjNi44LTE3LjQsNi44LTM2LjYsMC01NGwtNzkuNC0yMDRoNzAuOWw0Ny43LDE0OS40bDc0LjgtMjA3LjZIMTE2LjRjLTQxLjgsMC03NiwzNC4yLTc2LDc2VjM1N2MwLDQxLjgsMzQuMiw3Niw3Niw3NkgxNzNDMTg5LDQyNC4xLDE5Ny42LDQxMC4zLDIwMy42LDM5NXoiLz48L2c+PGc+PHBhdGggZD0iTTQ3MS42LDE1NC44YzAtNDEuOC0zNC4yLTc2LTc2LTc2aC0zTDI4NS43LDM2NWMtOS42LDI2LjctMTkuNCw0OS4zLTMwLjMsNjhoMjE2LjJWMTU0Ljh6Ii8+PC9nPjwvZz48cGF0aCBzdHJva2Utd2lkdGg9IjIuOTc0IiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIGQ9Ik0zMzgsMS4zbC05My4zLDI1OS4xbC00Mi4xLTEzMS45aC04OS4xbDgzLjgsMjE1LjJjNiwxNS41LDYsMzIuNSwwLDQ4Yy03LjQsMTktMTksMzcuMy01Myw0MS45bC03LjIsMXY3Nmg4LjNjODEuNywwLDExOC45LTU3LjIsMTQ5LjYtMTQyLjlMNDMxLjYsMS4zSDMzOHogTTI3OS40LDM2MmMtMzIuOSw5Mi02Ny42LDEyOC43LTEyNS43LDEzMS44di00NWMzNy41LTcuNSw1MS4zLTMxLDU5LjEtNTEuMWM3LjUtMTkuMyw3LjUtNDAuNywwLTYwbC03NS0xOTIuN2g1Mi44bDUzLjMsMTY2LjhsMTA1LjktMjk0aDU4LjFMMjc5LjQsMzYyeiIvPjwvZz48L2c+PC9zdmc+',
            65
        );
    }

    public function plugin_create_admin_page()
    {
        require_once KSR_DIR_PATH . '\includes\templates\template-admin-page.php';
    }


    /**
     * get_posts_match
     *
     * @param  mixed $content_type
     * @param  mixed $keyword
     * @param  mixed $post_type
     * @return array
     */
    public static function get_posts_match(string $content_type, $keyword = '', $post_type = 'post'): array
    {
        global $wpdb;

        if ($content_type === 'title' || $content_type === 'content') {
            $query = $wpdb->prepare(
                "SELECT post_title, post_content, ID FROM {$wpdb->prefix}posts
                WHERE post_type = '{$post_type}' 
                AND post_status = 'publish' 
                AND (post_title LIKE '%%%s%%')",
                $wpdb->esc_like($keyword)
            );
        }

        if ($content_type === 'meta_desc') {
            $query = $wpdb->prepare(
                "SELECT p.ID, p.post_title, pm.meta_value
                FROM {$wpdb->prefix}posts AS p
                JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
                WHERE p.post_type = '{$post_type}'
                AND p.post_status = 'publish'
                AND pm.meta_key = '_yoast_wpseo_metadesc'
                AND pm.meta_value LIKE '%%%s%%'",
                $keyword
            );
        }

        $results = $wpdb->get_results($query);

        set_transient($keyword . '_' . $content_type, $results, 600);

        return $results;
    }

    /**
     * update_post
     *
     * @param  mixed $post_id
     * @param  mixed $old_keyword
     * @param  mixed $new_keyword
     * @param  mixed $type
     * @return void
     */
    public function update_post(string $post_id, string $old_keyword, string $new_keyword, string $type)
    {

        if (!$post_id) {
            return;
        }

        global $wpdb;

        if ($type === 'title' || $type === 'content') {
            $content = $wpdb->get_var($wpdb->prepare("SELECT post_{$type} FROM $wpdb->posts WHERE ID = %d", $post_id));

            if ($content !== null) {
                $new_content = StringTransformation::case_sensitive_replace($old_keyword, $new_keyword, $content, true);

                $wpdb->update(
                    $wpdb->posts,
                    array('post_' . $type => $new_content),
                    array('ID' => $post_id),
                    array('%s'),
                    array('%d')
                );
            }
        }

        if ($type === 'meta_desc') {
            $content = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);

            if ($content !== null) {
                $new_content = StringTransformation::case_sensitive_replace($old_keyword, $new_keyword, $content, true);

                update_post_meta((int) $post_id, '_yoast_wpseo_metadesc', $new_content);
            }
        }
    }


    /**
     * ajax_get_posts
     *
     * @return void
     */
    public function ajax_get_posts(): void
    {

        if (!isset($_POST["val"])) {
              die();
        }
        $keyword = sanitize_text_field($_POST["val"]);

        $content_types = [
        ['title' => 'Title', 'type' => 'title'],
        ['title' => 'Content', 'type' => 'content'],
        ['title' => 'Meta-description', 'type' => 'meta_desc']
        ];
        ob_start();
        ?>

        <?php foreach ($content_types as $type) :
            $posts = $this->get_posts_match($type['type'], $keyword); ?>
<div class="ksr-posts__wrapper">
    <h2 class="ksr-posts__title"><?php echo $type['title'] ?></h2>
            <?php if (count($posts) > 0) :?>
    <div class="ksr-posts__form">
        <input type="text" name="" placeholder="new keyword">
        <button class="button" id="js-keyword-btn" data-type="<?php echo $type['type'] ?>">Replace</button>
    </div>
            <?php endif;?>
    <ul class="ksr-posts__list">
                <?php if (count($posts) > 0) :?>
                    <?php foreach ($posts as $post) :
                        if ($type['type'] === 'title') {
                            $string = $post->post_title;
                        } elseif ($type['type'] === 'content') {
                            $string = $post->post_content;
                        } elseif ($type['type'] === 'meta_desc') {
                            $string = $post->meta_value;
                        }
                        ?>
        <li>
                        <?php
                            $content = StringTransformation::case_sensitive_replace($keyword, $keyword, $string, true, true);
                            $max_char = 200;

                        if (strlen($string) > $max_char) {
                            $content = substr($content, 0, $max_char) . '...';
                        }
                            echo $content;
                        ?>
        </li>
                    <?php endforeach; ?>
                <?php else :?>
        <li>
            <div class="ksr-posts-not-found">No posts found</div>
        </li>
                <?php endif;?>
    </ul>
</div>
        <?php endforeach; ?>
</div>
            <?php
            $content = ob_get_contents();
            ob_end_clean();
            echo $content;
            die();
    }

    /**
     * ajax_update_posts
     *
     * @return void
     */
    public function ajax_update_posts(): void
    {

        if (
            !isset($_POST["newKeyword"]) ||
            !isset($_POST["oldKeyword"]) ||
            !isset($_POST["type"])
        ) {
            die();
        }

        $new_keyword = sanitize_text_field($_POST["newKeyword"]);
        $old_keyword = sanitize_text_field($_POST["oldKeyword"]);
        $content_type = $_POST["type"];

        $posts = get_transient($old_keyword . '_' . $content_type);

        if (!$posts) {
            $posts = $this->get_posts_match($content_type, $old_keyword);
        }

        foreach ($posts as $post) {
            $this->update_post($post->ID, $old_keyword, $new_keyword, $content_type);
        }

        delete_transient($old_keyword . '_' . $content_type);

        die();
    }
}
KSR::get_instance();