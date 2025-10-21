<?php
/**
 * Plugin Name: KTS Gallery
 * Plugin URI: https://example.com/
 * Description: A simple, no-nonsense gallery plugin with lightbox. Create, clone, rename, delete, and embed galleries via shortcode.
 * Version: 1.0.0
 * Author: You
 * Author URI: https://example.com/
 * Text Domain: kts-gallery
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) { exit; }

define('KTS_GALLERY_VERSION', '1.0.0');
define('KTS_GALLERY_SLUG', 'kts-gallery');
define('KTS_GALLERY_PATH', plugin_dir_path(__FILE__));
define('KTS_GALLERY_URL', plugin_dir_url(__FILE__));

class KTS_Gallery_Plugin {

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_kts_gallery', [$this, 'save_gallery_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('manage_kts_gallery_posts_columns', [$this, 'manage_gallery_columns']);
        add_action('manage_kts_gallery_posts_custom_column', [$this, 'render_gallery_columns'], 10, 2);
        add_filter('post_row_actions', [$this, 'row_actions'], 10, 2);
        add_action('admin_action_kts_duplicate_gallery', [$this, 'duplicate_gallery_action']);
        add_shortcode('kts_gallery', [$this, 'shortcode_render']);
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_assets']);
    }

    public function register_post_type() {
        $labels = [
            'name'               => __('Galleries', 'kts-gallery'),
            'singular_name'      => __('Gallery', 'kts-gallery'),
            'add_new'            => __('Add New', 'kts-gallery'),
            'add_new_item'       => __('Add New Gallery', 'kts-gallery'),
            'edit_item'          => __('Edit Gallery', 'kts-gallery'),
            'new_item'           => __('New Gallery', 'kts-gallery'),
            'view_item'          => __('View Gallery', 'kts-gallery'),
            'search_items'       => __('Search Galleries', 'kts-gallery'),
            'not_found'          => __('No galleries found', 'kts-gallery'),
            'not_found_in_trash' => __('No galleries found in Trash', 'kts-gallery'),
            'all_items'          => __('All Galleries', 'kts-gallery'),
            'menu_name'          => __('KTS Gallery', 'kts-gallery'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-format-gallery',
            'supports'           => ['title'],
            'capability_type'    => 'post',
            'has_archive'        => false,
        ];

        register_post_type('kts_gallery', $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'kts_gallery_images',
            __('Gallery Images', 'kts-gallery'),
            [$this, 'render_images_metabox'],
            'kts_gallery',
            'normal',
            'high'
        );

        add_meta_box(
            'kts_gallery_settings',
            __('Gallery Settings', 'kts-gallery'),
            [$this, 'render_settings_metabox'],
            'kts_gallery',
            'side'
        );
    }

    public function render_images_metabox($post) {
        wp_nonce_field('kts_save_gallery', 'kts_gallery_nonce');
        $ids = get_post_meta($post->ID, '_kts_images', true);
        if (!is_array($ids)) { $ids = []; }
        ?>
        <div class="kts-wrap">
            <p><?php _e('Add or upload images. Drag to reorder. Click ✕ to remove.', 'kts-gallery'); ?></p>
            <button type="button" class="button button-primary" id="kts-select-images"><?php _e('Select / Upload Images', 'kts-gallery'); ?></button>
            <input type="hidden" id="kts-images-input" name="kts_images" value="<?php echo esc_attr(implode(',', array_map('intval', $ids))); ?>" />
            <ul id="kts-images-list" class="kts-images-list">
                <?php foreach ($ids as $aid): 
                    $thumb = wp_get_attachment_image_src($aid, 'thumbnail');
                    if ($thumb): ?>
                    <li class="kts-image-item" data-id="<?php echo esc_attr($aid); ?>">
                        <img src="<?php echo esc_url($thumb[0]); ?>" alt="" />
                        <span class="kts-remove" title="<?php esc_attr_e('Remove', 'kts-gallery'); ?>">✕</span>
                    </li>
                <?php endif; endforeach; ?>
            </ul>
        </div>
        <?php
    }

    public function render_settings_metabox($post) {
        $columns = (int) get_post_meta($post->ID, '_kts_columns', true);
        $gap     = get_post_meta($post->ID, '_kts_gap', true);
        $height  = get_post_meta($post->ID, '_kts_height', true);
        $lightbox = get_post_meta($post->ID, '_kts_lightbox', true);
        if (!$columns) $columns = 3;
        if ($gap === '') $gap = '8px';
        if ($height === '') $height = '200px';
        $lightbox = $lightbox === '' ? '1' : $lightbox;
        ?>
        <p><label for="kts_columns"><?php _e('Columns', 'kts-gallery'); ?></label>
        <input type="number" min="1" max="12" id="kts_columns" name="kts_columns" value="<?php echo esc_attr($columns); ?>" class="small-text" /></p>

        <p><label for="kts_gap"><?php _e('Gap (e.g., 8px or 0.5rem)', 'kts-gallery'); ?></label>
        <input type="text" id="kts_gap" name="kts_gap" value="<?php echo esc_attr($gap); ?>" class="regular-text" /></p>

        <p><label for="kts_height"><?php _e('Image Height (e.g., 200px, 20vh)', 'kts-gallery'); ?></label>
        <input type="text" id="kts_height" name="kts_height" value="<?php echo esc_attr($height); ?>" class="regular-text" /></p>

        <p><label><input type="checkbox" name="kts_lightbox" value="1" <?php checked($lightbox, '1'); ?>/> <?php _e('Enable Lightbox', 'kts-gallery'); ?></label></p>

        <p><strong><?php _e('Shortcode', 'kts-gallery'); ?>:</strong>
            <code>[kts_gallery id="<?php echo esc_attr($post->ID); ?>"]</code>
        </p>
        <?php
    }

    public function save_gallery_meta($post_id) {
        if (!isset($_POST['kts_gallery_nonce']) || !wp_verify_nonce($_POST['kts_gallery_nonce'], 'kts_save_gallery')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Images
        $raw = isset($_POST['kts_images']) ? sanitize_text_field($_POST['kts_images']) : '';
        $ids = array_filter(array_map('intval', array_filter(array_map('trim', explode(',', $raw)))));
        update_post_meta($post_id, '_kts_images', $ids);

        // Settings
        $columns = isset($_POST['kts_columns']) ? intval($_POST['kts_columns']) : 3;
        $gap = isset($_POST['kts_gap']) ? sanitize_text_field($_POST['kts_gap']) : '8px';
        $height = isset($_POST['kts_height']) ? sanitize_text_field($_POST['kts_height']) : '200px';
        $lightbox = isset($_POST['kts_lightbox']) ? '1' : '0';

        update_post_meta($post_id, '_kts_columns', $columns);
        update_post_meta($post_id, '_kts_gap', $gap);
        update_post_meta($post_id, '_kts_height', $height);
        update_post_meta($post_id, '_kts_lightbox', $lightbox);
    }

    public function enqueue_admin_assets($hook) {
        global $typenow;
        if ($typenow !== 'kts_gallery') return;

        wp_enqueue_media();
        wp_enqueue_style('kts-admin', KTS_GALLERY_URL . 'assets/css/admin.css', [], KTS_GALLERY_VERSION);
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('kts-admin', KTS_GALLERY_URL . 'assets/js/admin.js', ['jquery', 'jquery-ui-sortable'], KTS_GALLERY_VERSION, true);
    }

    public function register_frontend_assets() {
        wp_register_style('kts-frontend', KTS_GALLERY_URL . 'assets/css/frontend.css', [], KTS_GALLERY_VERSION);
        wp_register_script('kts-frontend', KTS_GALLERY_URL . 'assets/js/frontend.js', [], KTS_GALLERY_VERSION, true);
    }

    public function shortcode_render($atts) {
        $atts = shortcode_atts([
            'id'      => 0,
            'columns' => '',
            'gap'     => '',
            'height'  => '',
            'lightbox'=> '',
            'class'   => '',
        ], $atts, 'kts_gallery');

        $post_id = intval($atts['id']);
        if (!$post_id) return '';

        $ids = get_post_meta($post_id, '_kts_images', true);
        if (!is_array($ids) || empty($ids)) return '';

        $columns = $atts['columns'] !== '' ? intval($atts['columns']) : (int) get_post_meta($post_id, '_kts_columns', true);
        $gap     = $atts['gap']     !== '' ? $atts['gap']               : get_post_meta($post_id, '_kts_gap', true);
        $height  = $atts['height']  !== '' ? $atts['height']            : get_post_meta($post_id, '_kts_height', true);
        $lightbox = $atts['lightbox'] !== '' ? $atts['lightbox']        : get_post_meta($post_id, '_kts_lightbox', true);
        if (!$columns) $columns = 3;
        if ($gap === '') $gap = '8px';
        if ($height === '') $height = '200px';

        wp_enqueue_style('kts-frontend');
        if ($lightbox === '1' || $lightbox === 1 || $lightbox === true) {
            wp_enqueue_script('kts-frontend');
        }

        $classes = 'kts-gallery';
        if (!empty($atts['class'])) $classes .= ' ' . sanitize_html_class($atts['class']);

        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>" data-kts-gallery="<?php echo esc_attr($post_id); ?>"
            style="--kts-columns: <?php echo esc_attr($columns); ?>; --kts-gap: <?php echo esc_attr($gap); ?>; --kts-height: <?php echo esc_attr($height); ?>;">
            <?php foreach ($ids as $i => $aid):
                $full = wp_get_attachment_image_src($aid, 'full');
                if (!$full) continue;
                $img = wp_get_attachment_image($aid, 'large', false, ['loading' => 'lazy']);
                ?>
                <a href="<?php echo esc_url($full[0]); ?>" class="kts-item" data-kts-lightbox="gallery-<?php echo esc_attr($post_id); ?>" data-index="<?php echo esc_attr($i); ?>">
                    <?php echo $img; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function manage_gallery_columns($columns) {
        $new = [];
        $new['cb'] = $columns['cb'];
        $new['kts_thumb'] = __('Preview', 'kts-gallery');
        $new['title'] = __('Title', 'kts-gallery');
        $new['kts_shortcode'] = __('Shortcode', 'kts-gallery');
        $new['date'] = $columns['date'];
        return $new;
    }

    public function render_gallery_columns($column, $post_id) {
        if ($column === 'kts_thumb') {
            $ids = get_post_meta($post_id, '_kts_images', true);
            if (is_array($ids) && !empty($ids)) {
                $thumb = wp_get_attachment_image($ids[0], 'thumbnail', false, ['style' => 'width:60px;height:60px;object-fit:cover;border-radius:4px;']);
                echo $thumb ? $thumb : '<span class="dashicons dashicons-format-image"></span>';
            } else {
                echo '<span class="dashicons dashicons-format-image"></span>';
            }
        }
        if ($column === 'kts_shortcode') {
            echo '<code>[kts_gallery id="' . esc_html($post_id) . '"]</code>';
        }
    }

    public function row_actions($actions, $post) {
        if ($post->post_type !== 'kts_gallery') return $actions;
        $nonce = wp_create_nonce('kts_duplicate_' . $post->ID);
        $url = admin_url('admin.php?action=kts_duplicate_gallery&post=' . $post->ID . '&_wpnonce=' . $nonce);
        $actions['kts_duplicate'] = '<a href="' . esc_url($url) . '">' . esc_html__('Duplicate', 'kts-gallery') . '</a>';
        return $actions;
    }

    public function duplicate_gallery_action() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to duplicate this item.', 'kts-gallery'));
        }
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
        if (!$post_id || !wp_verify_nonce($nonce, 'kts_duplicate_' . $post_id)) {
            wp_die(__('Invalid request.', 'kts-gallery'));
        }
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'kts_gallery') {
            wp_die(__('Invalid gallery.', 'kts-gallery'));
        }
        $new_post = [
            'post_title'   => $post->post_title . ' ' . __('(Copy)', 'kts-gallery'),
            'post_type'    => 'kts_gallery',
            'post_status'  => 'draft',
        ];
        $new_id = wp_insert_post($new_post);

        // Copy meta
        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $values) {
            if (is_protected_meta($key, 'post')) continue;
            foreach ($values as $v) {
                add_post_meta($new_id, $key, maybe_unserialize($v));
            }
        }

        wp_safe_redirect(admin_url('post.php?action=edit&post=' . $new_id . '&kts_duplicated=1'));
        exit;
    }
}

new KTS_Gallery_Plugin();

// Admin notice after duplicate
add_action('admin_notices', function() {
    if (isset($_GET['kts_duplicated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Gallery duplicated. You can now edit the copy.', 'kts-gallery') . '</p></div>';
    }
});
