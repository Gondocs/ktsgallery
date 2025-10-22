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

define('KTS_GALLERY_VERSION', '1.1.1');
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
            'normal',
            'default'
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
        // Core settings
        $columns  = (int) get_post_meta($post->ID, '_kts_columns', true);
        $gap      = get_post_meta($post->ID, '_kts_gap', true);
        $height   = get_post_meta($post->ID, '_kts_height', true);
        $lightbox = get_post_meta($post->ID, '_kts_lightbox', true);
        $layout   = get_post_meta($post->ID, '_kts_layout', true);
        $autoCols = get_post_meta($post->ID, '_kts_auto_columns', true);
        $minWidth = get_post_meta($post->ID, '_kts_min_width', true);
        $lazy     = get_post_meta($post->ID, '_kts_lazy', true);
    $imgSize  = get_post_meta($post->ID, '_kts_image_size', true);
    $imgW     = get_post_meta($post->ID, '_kts_img_w', true);
    $imgH     = get_post_meta($post->ID, '_kts_img_h', true);
        $crop     = get_post_meta($post->ID, '_kts_crop', true);
        $rowH     = get_post_meta($post->ID, '_kts_row_height', true);
        $margins  = get_post_meta($post->ID, '_kts_margins', true);

    if (!$columns) $columns = 3;
        if ($gap === '') $gap = '8px';
        if ($height === '') $height = '200px';
        if ($layout === '') $layout = 'grid';
        if ($minWidth === '') $minWidth = '220px';
        if ($rowH === '') $rowH = '220px';
        if ($margins === '') $margins = '8px';
    $lightbox = $lightbox === '' ? '1' : $lightbox;
        $autoCols = $autoCols === '' ? '0' : $autoCols;
        $lazy     = $lazy === '' ? '1' : $lazy;
        $crop     = $crop === '' ? '0' : $crop;

    // Appearance / extra options (subset of requested)
    $align   = get_post_meta($post->ID, '_kts_align', true); if ($align === '') $align = 'center';
    $widthPc = get_post_meta($post->ID, '_kts_width_pc', true); if ($widthPc === '') $widthPc = '100';
    $padding = get_post_meta($post->ID, '_kts_padding', true); if ($padding === '') $padding = '0px';
    $radius  = get_post_meta($post->ID, '_kts_radius', true); if ($radius === '') $radius = '8px';
    $borderW = get_post_meta($post->ID, '_kts_border_w', true); if ($borderW === '') $borderW = '0px';
    $borderC = get_post_meta($post->ID, '_kts_border_c', true); if ($borderC === '') $borderC = 'transparent';
    $shadow  = get_post_meta($post->ID, '_kts_shadow', true); if ($shadow === '') $shadow = '0';
    $noRC    = get_post_meta($post->ID, '_kts_no_rclick', true); if ($noRC === '') $noRC = '0';

        // Image sizes
        $sizes = apply_filters('image_size_names_choose', [
            'thumbnail' => __('Thumbnail'),
            'medium'    => __('Medium'),
            'large'     => __('Large'),
            'full'      => __('Original Image'),
        ]);
        if (!$imgSize) $imgSize = 'large';

        // Public shortcode ID
        $public_id = get_post_meta($post->ID, '_kts_public_id', true);
        if (!$public_id) $public_id = __('not assigned yet (will be generated on save)', 'kts-gallery');

        ?>
        <div class="kts-tabs">
            <div class="kts-tab-container">
            <div class="kts-tab-nav">
                <button type="button" class="kts-tab-btn is-active" data-for="layout"><?php _e('Layout', 'kts-gallery'); ?></button>
                <button type="button" class="kts-tab-btn" data-for="media"><?php _e('Media', 'kts-gallery'); ?></button>
                <button type="button" class="kts-tab-btn" data-for="appearance"><?php _e('Appearance', 'kts-gallery'); ?></button>
                <button type="button" class="kts-tab-btn" data-for="lightbox"><?php _e('Lightbox', 'kts-gallery'); ?></button>
            </div>

            <div class="kts-tab-content">
            <div class="kts-tab-panel is-active" data-tab="layout">
                <div class="kts-settings-grid">
                    <div class="kts-field">
                        <label for="kts_layout" title="Choose how images are arranged."><?php _e('Layout', 'kts-gallery'); ?></label>
                        <select id="kts_layout" name="kts_layout">
                            <?php
                            $opts = [
                                'automatic' => __('Automatic', 'kts-gallery'),
                                'mason'     => __('Mason', 'kts-gallery'),
                                'grid'      => __('Grid', 'kts-gallery'),
                                'square'    => __('Square', 'kts-gallery'),
                                'blogroll'  => __('Blogroll', 'kts-gallery'),
                            ];
                            foreach ($opts as $val => $label) {
                                echo '<option value="' . esc_attr($val) . '"' . selected($layout, $val, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="kts-field">
                        <label for="kts_columns" title="Number of columns for Grid/Square/Blogroll. Automatic layout ignores this."><?php _e('Columns', 'kts-gallery'); ?></label>
                        <input type="number" min="1" max="12" id="kts_columns" name="kts_columns" value="<?php echo esc_attr($columns); ?>" />
                        <label class="kts-inline" title="Use CSS auto-fit to keep columns responsive"><input type="checkbox" name="kts_auto_columns" value="1" <?php checked($autoCols, '1'); ?>/> <?php _e('Automatic Columns (responsive)', 'kts-gallery'); ?></label>
                        <input type="text" id="kts_min_width" name="kts_min_width" value="<?php echo esc_attr($minWidth); ?>" placeholder="min column width e.g. 220px" title="Minimum width of a column before the grid drops to fewer columns" />
                    </div>

                    <div class="kts-field">
                        <label for="kts_gap" title="Gap between items."><?php _e('Gap', 'kts-gallery'); ?></label>
                        <input type="text" id="kts_gap" name="kts_gap" value="<?php echo esc_attr($gap); ?>" placeholder="e.g., 8px or 0.5rem" />
                    </div>

                    <div class="kts-field">
                        <label for="kts_height" title="Visible image height for Grid/Square. Turn off Crop to show natural height."><?php _e('Image Height', 'kts-gallery'); ?></label>
                        <input type="text" id="kts_height" name="kts_height" value="<?php echo esc_attr($height); ?>" placeholder="e.g., 200px, 20vh" />
                    </div>

                    <div class="kts-field">
                        <label title="Settings specific to Automatic layout."><?php _e('Automatic Layout', 'kts-gallery'); ?></label>
                        <input type="text" id="kts_row_height" name="kts_row_height" value="<?php echo esc_attr($rowH); ?>" placeholder="Row Height e.g., 220px" title="Target row height (images scale to fit)" />
                        <input type="text" id="kts_margins" name="kts_margins" value="<?php echo esc_attr($margins); ?>" placeholder="Margins e.g., 8px" title="Spacing between images for Automatic layout" />
                    </div>
                </div>
            </div>

            <div class="kts-tab-panel" data-tab="media">
                <div class="kts-settings-grid">
                    <div class="kts-field">
                        <label for="kts_image_size" title="Choose a WordPress image size to load."><?php _e('Image Size', 'kts-gallery'); ?></label>
                        <select id="kts_image_size" name="kts_image_size">
                            <?php foreach ($sizes as $k => $label) echo '<option value="' . esc_attr($k) . '"' . selected($imgSize, $k, false) . '>' . esc_html($label) . '</option>'; ?>
                        </select>
                        <label class="kts-inline" title="Crop images to fit the grid height."><input type="checkbox" name="kts_crop" value="1" <?php checked($crop, '1'); ?>/> <?php _e('Crop Images?', 'kts-gallery'); ?></label>
                    </div>
                    <div class="kts-field">
                        <label title="Optional explicit width × height attributes."><?php _e('Image Dimensions', 'kts-gallery'); ?></label>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="number" min="0" id="kts_img_w" name="kts_img_w" value="<?php echo esc_attr($imgW); ?>" placeholder="width" />
                            <span style="opacity:.7;">×</span>
                            <input type="number" min="0" id="kts_img_h" name="kts_img_h" value="<?php echo esc_attr($imgH); ?>" placeholder="height" />
                            <span style="opacity:.7;">px</span>
                        </div>
                    </div>
                    <div class="kts-field">
                        <label title="Delay image loading until they are near the viewport."><input type="checkbox" name="kts_lazy" value="1" <?php checked($lazy, '1'); ?>/> <?php _e('Enable Lazy Loading', 'kts-gallery'); ?></label>
                    </div>
                </div>
            </div>

            <div class="kts-tab-panel" data-tab="appearance">
                <div class="kts-settings-grid">
                    <div class="kts-field">
                        <label><?php _e('Gallery alignment', 'kts-gallery'); ?></label>
                        <select name="kts_align" id="kts_align">
                            <option value="left" <?php selected($align,'left'); ?>><?php _e('Left','kts-gallery'); ?></option>
                            <option value="center" <?php selected($align,'center'); ?>><?php _e('Center','kts-gallery'); ?></option>
                            <option value="right" <?php selected($align,'right'); ?>><?php _e('Right','kts-gallery'); ?></option>
                        </select>
                        <label class="kts-inline" for="kts_width_pc" title="Max width of the gallery container."><?php _e('Gallery width (%)', 'kts-gallery'); ?></label>
                        <input type="number" min="10" max="100" id="kts_width_pc" name="kts_width_pc" value="<?php echo esc_attr($widthPc); ?>" />
                        <label class="kts-inline" for="kts_padding" title="Padding inside the gallery container."><?php _e('Gallery padding', 'kts-gallery'); ?></label>
                        <input type="text" id="kts_padding" name="kts_padding" value="<?php echo esc_attr($padding); ?>" placeholder="e.g., 0px or 1rem" />
                    </div>
                    <div class="kts-field">
                        <label title="Corner radius, border and color for each image."><?php _e('Image styling', 'kts-gallery'); ?></label>
                        <input type="text" id="kts_radius" name="kts_radius" value="<?php echo esc_attr($radius); ?>" placeholder="Radius e.g., 8px or 5%" />
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="text" id="kts_border_w" name="kts_border_w" value="<?php echo esc_attr($borderW); ?>" placeholder="Border width e.g., 1px" />
                            <input type="text" id="kts_border_c" name="kts_border_c" value="<?php echo esc_attr($borderC); ?>" placeholder="Border color e.g., #ddd" />
                        </div>
                        <label class="kts-inline"><input type="checkbox" name="kts_shadow" value="1" <?php checked($shadow,'1'); ?>/> <?php _e('Shadow', 'kts-gallery'); ?></label>
                        <label class="kts-inline"><input type="checkbox" name="kts_no_rclick" value="1" <?php checked($noRC,'1'); ?>/> <?php _e('Disable right click', 'kts-gallery'); ?></label>
                    </div>
                </div>
            </div>

            <div class="kts-tab-panel" data-tab="lightbox">
                <div class="kts-settings-grid">
                    <div class="kts-field">
                        <label title="Enable/disable the popup image viewer."><input type="checkbox" name="kts_lightbox" value="1" <?php checked($lightbox, '1'); ?>/> <?php _e('Enable Lightbox', 'kts-gallery'); ?></label>
                    </div>
                </div>
            </div>
            </div><!-- /.kts-tab-content -->
            </div><!-- /.kts-tab-container -->
        </div>

        <p><strong><?php _e('Shortcodes', 'kts-gallery'); ?>:</strong><br/>
            <code>[kts_gallery id="<?php echo esc_attr($public_id); ?>"]</code> &nbsp; <?php _e('(public number)', 'kts-gallery'); ?><br/>
            <code>[kts_gallery id="<?php echo esc_attr($post->post_name); ?>"]</code> &nbsp; <?php _e('(slug)', 'kts-gallery'); ?>
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
        $columns  = isset($_POST['kts_columns']) ? intval($_POST['kts_columns']) : 3;
        $gap      = isset($_POST['kts_gap']) ? sanitize_text_field($_POST['kts_gap']) : '8px';
        $height   = isset($_POST['kts_height']) ? sanitize_text_field($_POST['kts_height']) : '200px';
        $lightbox = isset($_POST['kts_lightbox']) ? '1' : '0';
    $layout   = isset($_POST['kts_layout']) ? sanitize_text_field($_POST['kts_layout']) : 'grid';
        $autoCols = isset($_POST['kts_auto_columns']) ? '1' : '0';
        $minWidth = isset($_POST['kts_min_width']) ? sanitize_text_field($_POST['kts_min_width']) : '220px';
        $lazy     = isset($_POST['kts_lazy']) ? '1' : '0';
    $imgSize  = isset($_POST['kts_image_size']) ? sanitize_text_field($_POST['kts_image_size']) : 'large';
    $imgW     = isset($_POST['kts_img_w']) ? intval($_POST['kts_img_w']) : 0;
    $imgH     = isset($_POST['kts_img_h']) ? intval($_POST['kts_img_h']) : 0;
        $crop     = isset($_POST['kts_crop']) ? '1' : '0';
        $rowH     = isset($_POST['kts_row_height']) ? sanitize_text_field($_POST['kts_row_height']) : '220px';
        $margins  = isset($_POST['kts_margins']) ? sanitize_text_field($_POST['kts_margins']) : '8px';
    // extras
    $align   = isset($_POST['kts_align']) ? sanitize_text_field($_POST['kts_align']) : 'center';
    $widthPc = isset($_POST['kts_width_pc']) ? intval($_POST['kts_width_pc']) : 100;
    $padding = isset($_POST['kts_padding']) ? sanitize_text_field($_POST['kts_padding']) : '0px';
    $radius  = isset($_POST['kts_radius']) ? sanitize_text_field($_POST['kts_radius']) : '8px';
    $borderW = isset($_POST['kts_border_w']) ? sanitize_text_field($_POST['kts_border_w']) : '0px';
    $borderC = isset($_POST['kts_border_c']) ? sanitize_text_field($_POST['kts_border_c']) : 'transparent';
    $shadow  = isset($_POST['kts_shadow']) ? '1' : '0';
    $noRC    = isset($_POST['kts_no_rclick']) ? '1' : '0';

        update_post_meta($post_id, '_kts_columns', $columns);
        update_post_meta($post_id, '_kts_gap', $gap);
        update_post_meta($post_id, '_kts_height', $height);
        update_post_meta($post_id, '_kts_lightbox', $lightbox);
        update_post_meta($post_id, '_kts_layout', $layout);
        update_post_meta($post_id, '_kts_auto_columns', $autoCols);
        update_post_meta($post_id, '_kts_min_width', $minWidth);
        update_post_meta($post_id, '_kts_lazy', $lazy);
        update_post_meta($post_id, '_kts_image_size', $imgSize);
        update_post_meta($post_id, '_kts_crop', $crop);
        update_post_meta($post_id, '_kts_row_height', $rowH);
        update_post_meta($post_id, '_kts_margins', $margins);
    update_post_meta($post_id, '_kts_img_w', $imgW);
    update_post_meta($post_id, '_kts_img_h', $imgH);
    // extras
    update_post_meta($post_id, '_kts_align', $align);
    update_post_meta($post_id, '_kts_width_pc', $widthPc);
    update_post_meta($post_id, '_kts_padding', $padding);
    update_post_meta($post_id, '_kts_radius', $radius);
    update_post_meta($post_id, '_kts_border_w', $borderW);
    update_post_meta($post_id, '_kts_border_c', $borderC);
    update_post_meta($post_id, '_kts_shadow', $shadow);
    update_post_meta($post_id, '_kts_no_rclick', $noRC);

        // Ensure public sequential shortcode id exists
        $public_id = get_post_meta($post_id, '_kts_public_id', true);
        if (!$public_id) {
            global $wpdb;
            $max = (int) $wpdb->get_var("SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = '_kts_public_id'");
            $next = $max > 0 ? $max + 1 : 1;
            update_post_meta($post_id, '_kts_public_id', $next);
        }
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
            'layout'  => '',
            'auto'    => '',
            'min'     => '',
            'size'    => '',
            'crop'    => '',
            'row_height' => '',
            'margins' => '',
            'class'   => '',
        ], $atts, 'kts_gallery');
        
        // Resolve id: accept sequential public id, slug, or numeric post ID
        $post_id = 0;
        $raw = $atts['id'];
        if (is_numeric($raw)) {
            // Try public id first
            $query = new WP_Query([
                'post_type' => 'kts_gallery',
                'post_status' => 'any',
                'meta_key' => '_kts_public_id',
                'meta_value' => (int) $raw,
                'fields' => 'ids',
                'posts_per_page' => 1,
            ]);
            if (!empty($query->posts)) {
                $post_id = (int) $query->posts[0];
            } else {
                // Fallback to WP post ID
                $post_id = (int) $raw;
            }
        } else {
            // Assume slug
            $p = get_page_by_path(sanitize_title($raw), OBJECT, 'kts_gallery');
            if ($p) $post_id = $p->ID;
        }
        if (!$post_id) return '';

        $ids = get_post_meta($post_id, '_kts_images', true);
        if (!is_array($ids) || empty($ids)) return '';

        $columns = $atts['columns'] !== '' ? intval($atts['columns']) : (int) get_post_meta($post_id, '_kts_columns', true);
        $gap     = $atts['gap']     !== '' ? $atts['gap']               : get_post_meta($post_id, '_kts_gap', true);
        $height  = $atts['height']  !== '' ? $atts['height']            : get_post_meta($post_id, '_kts_height', true);
        $lightbox = $atts['lightbox'] !== '' ? $atts['lightbox']        : get_post_meta($post_id, '_kts_lightbox', true);
        $layout   = $atts['layout']   !== '' ? $atts['layout']          : get_post_meta($post_id, '_kts_layout', true);
        $autoCols = $atts['auto']     !== '' ? $atts['auto']            : get_post_meta($post_id, '_kts_auto_columns', true);
        $minWidth = $atts['min']      !== '' ? $atts['min']             : get_post_meta($post_id, '_kts_min_width', true);
    $imgSize  = $atts['size']     !== '' ? $atts['size']            : get_post_meta($post_id, '_kts_image_size', true);
        $crop     = $atts['crop']     !== '' ? $atts['crop']            : get_post_meta($post_id, '_kts_crop', true);
        $rowH     = $atts['row_height'] !== '' ? $atts['row_height']   : get_post_meta($post_id, '_kts_row_height', true);
        $margins  = $atts['margins']  !== '' ? $atts['margins']         : get_post_meta($post_id, '_kts_margins', true);
    $imgW     = (int) get_post_meta($post_id, '_kts_img_w', true);
    $imgH     = (int) get_post_meta($post_id, '_kts_img_h', true);
    if (!$columns) $columns = 3;
        if ($gap === '') $gap = '8px';
        if ($height === '') $height = '200px';
    if ($layout === '') $layout = 'grid';
        if ($minWidth === '') $minWidth = '220px';
        if ($rowH === '') $rowH = '220px';
        if ($margins === '') $margins = '8px';

    // Extras
    $align   = get_post_meta($post_id, '_kts_align', true);
    $widthPc = (int) get_post_meta($post_id, '_kts_width_pc', true);
    $padding = get_post_meta($post_id, '_kts_padding', true);
    $radius  = get_post_meta($post_id, '_kts_radius', true);
    $borderW = get_post_meta($post_id, '_kts_border_w', true);
    $borderC = get_post_meta($post_id, '_kts_border_c', true);
    $shadow  = get_post_meta($post_id, '_kts_shadow', true);
    $noRC    = get_post_meta($post_id, '_kts_no_rclick', true);
    if ($align === '') $align = 'center';
    if (!$widthPc) $widthPc = 100;
    if ($padding === '') $padding = '0px';
    if ($radius === '') $radius = '8px';
    if ($borderW === '') $borderW = '0px';
    if ($borderC === '') $borderC = 'transparent';
    $shadowCss = ($shadow === '1') ? '0 8px 24px rgba(0,0,0,.15)' : 'none';
    $justify = $align === 'left' ? 'flex-start' : ($align === 'right' ? 'flex-end' : 'center');

        wp_enqueue_style('kts-frontend');
        if ($lightbox === '1' || $lightbox === 1 || $lightbox === true) {
            wp_enqueue_script('kts-frontend');
        }

    $classes = 'kts-gallery';
        if (!empty($atts['class'])) $classes .= ' ' . sanitize_html_class($atts['class']);
    if ($crop === '0' || $crop === 0) $classes .= ' is-no-crop';

        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes . ' kts-layout-' . esc_attr($layout)); ?>" data-kts-gallery="<?php echo esc_attr($post_id); ?>" data-auto="<?php echo esc_attr($autoCols ? '1' : '0'); ?>" data-no-rclick="<?php echo esc_attr($noRC ? '1' : '0'); ?>"
            style="--kts-columns: <?php echo esc_attr($columns); ?>; --kts-gap: <?php echo esc_attr($gap); ?>; --kts-height: <?php echo esc_attr($height); ?>; --kts-min: <?php echo esc_attr($minWidth); ?>; --kts-row-height: <?php echo esc_attr($rowH); ?>; --kts-margins: <?php echo esc_attr($margins); ?>; --kts-radius: <?php echo esc_attr($radius); ?>; --kts-border-width: <?php echo esc_attr($borderW); ?>; --kts-border-color: <?php echo esc_attr($borderC); ?>; --kts-shadow: <?php echo esc_attr($shadowCss); ?>; max-width: <?php echo esc_attr($widthPc); ?>%; padding: <?php echo esc_attr($padding); ?>; margin-left: <?php echo $align==='left'?'0':'auto'; ?>; margin-right: <?php echo $align==='right'?'0':'auto'; ?>;">
            <?php foreach ($ids as $i => $aid):
                $full = wp_get_attachment_image_src($aid, 'full');
                if (!$full) continue;
                $image_args = [];
                $image_args['loading'] = ($lazy === '1' || $lazy === 1) ? 'lazy' : 'eager';
                if ($imgW) $image_args['width'] = $imgW;
                if ($imgH) $image_args['height'] = $imgH;
                $img = wp_get_attachment_image($aid, $imgSize ? $imgSize : 'large', false, $image_args);
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
            $public = get_post_meta($post_id, '_kts_public_id', true);
            $slug = get_post_field('post_name', $post_id);
            if ($public) {
                echo '<code>[kts_gallery id="' . esc_html($public) . '"]</code><br/>';
            }
            echo '<code>[kts_gallery id="' . esc_html($slug) . '"]</code>';
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
            if ($key === '_kts_public_id') continue; // regenerate public id
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
