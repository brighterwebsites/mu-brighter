<?php
//Preload Images - Individual pages 
//Preload Images on Single Posts Select Post type
// Add status custom column to posts & pages

defined('ABSPATH') || exit;

class Brighter_Tweaks {
  const OPT = 'bw_preloads_map';     // array: [ page_id => [urls...] ]
  const OPT_THEME = 'theme_colour';  // hex code
  

  public static function boot() {
    // Admin UI
    add_action('admin_init', [__CLASS__, 'register_settings']);
    add_action('admin_menu', [__CLASS__, 'hook_tab_into_support'], 20);

    // Front end output
    add_action('wp_head', [__CLASS__, 'output_preloads'], 1);
  }

  /** Register settings */
  public static function register_settings() {
    register_setting('brighter_tweaks', self::OPT, [
      'type' => 'array',
      'sanitize_callback' => [__CLASS__, 'sanitise_preloads_map'],
      'default' => [],
    ]);

    register_setting('brighter_tweaks', self::OPT_THEME, [
      'type' => 'string',
      'sanitize_callback' => [__CLASS__, 'sanitise_hex'],
      'default' => '',
    ]);
  }

  /** Add a “Brighter Tweaks” tab/section to your existing page */
  public static function hook_tab_into_support() {
     // No submenu creation needed; Tweaks will be rendered as a tab via brighter_support_render_page().

  }





  /** Admin page render */
  public static function render_page() {
    if (!current_user_can('manage_options')) return;

    // Persist saves
    if (!empty($_POST['bw_tweaks_nonce']) && wp_verify_nonce($_POST['bw_tweaks_nonce'], 'bw_tweaks_save')) {
      // Theme colour
      if (isset($_POST[self::OPT_THEME])) {
        update_option(self::OPT_THEME, self::sanitise_hex(wp_unslash($_POST[self::OPT_THEME])));
      }
      // Preloads map (textarea matrix)
      $map = [];
      if (!empty($_POST[self::OPT]) && is_array($_POST[self::OPT])) {
        foreach ($_POST[self::OPT] as $pid => $raw) {
          $pid = (int)$pid;
          $lines = array_filter(array_map('trim', explode("\n", wp_unslash($raw))));
          if ($pid > 0 && $lines) $map[$pid] = array_values(array_unique(array_map([__CLASS__,'sanitise_url'],$lines)));
        }
      }
      update_option(self::OPT, $map);
      echo '<div class="updated"><p>Brighter Tweaks saved.</p></div>';
    }

    // Load data
    $theme = get_option(self::OPT_THEME, '');
    $map   = get_option(self::OPT, []);

    // Basic page query with search and pagination
    $paged     = max(1, intval($_GET['paged'] ?? 1));
    $search    = sanitize_text_field($_GET['s'] ?? '');
    $per_page  = 20;

    $q = new WP_Query([
      'post_type'      => 'page',
      'posts_per_page' => $per_page,
      'paged'          => $paged,
      's'              => $search,
      'orderby'        => 'title',
      'order'          => 'ASC',
      'post_status'    => ['publish','draft','pending','private'],
      'fields'         => 'ids',
    ]);

    ?>
    <div class="wrap">
      <h1>Brighter Tweaks</h1>

      <form method="get" style="margin-top:10px;">
        <input type="hidden" name="page" value="brighter_tweaks">
        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search pages…">
        <button class="button">Search</button>
      </form>

      <form method="post" style="margin-top:20px;">
        <?php wp_nonce_field('bw_tweaks_save', 'bw_tweaks_nonce'); ?>

        <h2 class="title">Theme Colour</h2>
        <p>Used across Brighter tools where a brand colour is needed.</p>
        <input type="text" name="<?php echo esc_attr(self::OPT_THEME); ?>"
               value="<?php echo esc_attr($theme); ?>" class="regular-text" placeholder="#193b2d"
               pattern="^#?[0-9a-fA-F]{3,6}$" />
        <p class="description">Accepts 3 or 6-digit hex. The hash is optional.</p>

        <hr>

        <h2 class="title">Per-Page Preloads</h2>
        <p>Enter one asset URL per line. These will be preloaded only on that page. Supports images, fonts, CSS and JS.</p>

        <table class="widefat striped">
          <thead>
            <tr>
              <th style="width:35%">Page</th>
              <th>Assets to Preload (one per line)</th>
            </tr>
          </thead>
          <tbody>
          <?php
          if ($q->have_posts()):
            foreach ($q->posts as $pid):
              $title = get_the_title($pid) ?: '(no title)';
              $url   = get_permalink($pid);
              $val   = isset($map[$pid]) ? implode("\n", $map[$pid]) : '';
              ?>
              <tr>
                <td>
                  <strong><?php echo esc_html($title); ?></strong><br>
                  <code><?php echo esc_html($url); ?></code><br>
                  <small>ID: <?php echo (int)$pid; ?> | Status: <?php echo esc_html(get_post_status($pid)); ?></small>
                </td>
                <td>
                  <textarea name="<?php echo esc_attr(self::OPT); ?>[<?php echo (int)$pid; ?>]"
                            rows="4" style="width:100%;font-family:monospace;"><?php echo esc_textarea($val); ?></textarea>
                </td>
              </tr>
              <?php
            endforeach;
          else:
            echo '<tr><td colspan="2">No pages found.</td></tr>';
          endif;
          ?>
          </tbody>
        </table>

        <?php
        // pagination
// pagination (force parent page + tab param)
$total_pages = $q->max_num_pages ?: 1;
if ($total_pages > 1){
  echo '<p>';
  for ($i=1;$i<=$total_pages;$i++){
    $link = add_query_arg([
      'page' => 'brighter_support',
      'tab'  => 'brighter_tweaks',
      's'    => $search,
      'paged'=> $i,
    ], admin_url('admin.php'));

    echo ($i===$paged)
      ? "<span class='button button-primary' style='margin-right:6px;'>$i</span>"
      : "<a class='button' style='margin-right:6px;' href='".esc_url($link)."'>$i</a>";
  }
  echo '</p>';
}
        ?>

        <p><button class="button button-primary">Save Tweaks</button></p>
      </form>
    </div>
    <?php
    wp_reset_postdata();
  }

  /** Front end: print rel=preload for current page */
  public static function output_preloads() {
    if (!is_page()) return;
    $map = get_option(self::OPT, []);
    if (empty($map)) return;

    $pid = get_queried_object_id();
    if (empty($map[$pid])) return;

    foreach ($map[$pid] as $u) {
      $attr = self::infer_preload_attrs($u);
      if (!$attr) continue;
      printf(
        "<link rel=\"preload\" href=\"%s\" as=\"%s\"%s%s>\n",
        esc_url($u),
        esc_attr($attr['as']),
        !empty($attr['type']) ? ' type="'.esc_attr($attr['type']).'"' : '',
        !empty($attr['crossorigin']) ? ' crossorigin' : ''
      );
    }
  }

  /** Sanitisation helpers */
  public static function sanitise_preloads_map($map) {
    $out = [];
    if (!is_array($map)) return $out;
    foreach ($map as $pid => $lines) {
      $pid = (int)$pid;
      if ($pid <= 0) continue;
      if (is_string($lines)) $lines = explode("\n", $lines);
      $lines = array_filter(array_map('trim', $lines));
      $urls  = [];
      foreach ($lines as $u) {
        $u = self::sanitise_url($u);
        if ($u) $urls[] = $u;
      }
      if ($urls) $out[$pid] = array_values(array_unique($urls));
    }
    return $out;
  }
  public static function sanitise_url($u) {
    // allow absolute or site-relative
    if (strpos($u, '//') === 0) $u = 'https:' . $u;
    if (preg_match('#^/[^ ]#', $u)) return esc_url_raw($u);
    $ok = filter_var($u, FILTER_VALIDATE_URL);
    return $ok ? esc_url_raw($u) : '';
  }
  public static function sanitise_hex($hex) {
    $hex = ltrim(trim((string)$hex), '#');
    if (!preg_match('/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $hex)) return '';
    return '#' . strtolower($hex);
  }

  /** Guess proper as= and type= attributes */
  public static function infer_preload_attrs($url) {
    $u = parse_url($url);
    $path = $u['path'] ?? '';
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    switch ($ext) {
      case 'css': return ['as'=>'style','type'=>'text/css'];
      case 'js':  return ['as'=>'script','type'=>'application/javascript'];
      case 'woff2': return ['as'=>'font','type'=>'font/woff2','crossorigin'=>true];
      case 'woff':  return ['as'=>'font','type'=>'font/woff','crossorigin'=>true];
      case 'ttf':   return ['as'=>'font','type'=>'font/ttf','crossorigin'=>true];
      case 'otf':   return ['as'=>'font','type'=>'font/otf','crossorigin'=>true];
      case 'jpg':
      case 'jpeg': return ['as'=>'image','type'=>'image/jpeg'];
      case 'png':  return ['as'=>'image','type'=>'image/png'];
      case 'webp': return ['as'=>'image','type'=>'image/webp'];
      case 'gif':  return ['as'=>'image','type'=>'image/gif'];
      case 'svg':  return ['as'=>'image','type'=>'image/svg+xml'];
      default:
        // Unknown, still allow as "fetch" for generic assets
        return ['as'=>'fetch','type'=>''];
    }
  }
}

Brighter_Tweaks::boot();


// Register page meta
add_action('init', function () {
  register_post_meta('page', '_bw_preloads', [
    'type'              => 'array',
    'single'            => true,
    'show_in_rest'      => true,
    'sanitize_callback' => function ($val) {
      $out = [];
      if (is_string($val)) $val = preg_split('/\r\n|\r|\n/', $val);
      if (!is_array($val)) return [];
      foreach ($val as $u) {
        $u = trim((string)$u);
        if ($u === '') continue;
        if (strpos($u, '//') === 0) $u = 'https:' . $u;          // allow protocol-relative
        if ($u[0] === '/') { $out[] = esc_url_raw($u); continue; } // site-relative
        if (filter_var($u, FILTER_VALIDATE_URL)) $out[] = esc_url_raw($u);
      }
      return array_values(array_unique($out));
    },
    'auth_callback'     => function () { return current_user_can('edit_pages'); },
    'default'           => [],
  ]);
});

// Add meta box
add_action('add_meta_boxes', function () {
  add_meta_box('bw_preloads', 'Preload Assets', function ($post) {
    $vals = get_post_meta($post->ID, '_bw_preloads', true);
    $text = is_array($vals) ? implode("\n", $vals) : '';
    echo '<p>Enter one asset URL per line. Supports CSS, JS, fonts, and images.</p>';
    echo '<textarea style="width:100%;min-height:140px" name="bw_preloads_field">' . esc_textarea($text) . '</textarea>';
    wp_nonce_field('bw_preloads_save', 'bw_preloads_nonce');
  }, 'page', 'side', 'default');
});

// Save meta box
add_action('save_post_page', function ($post_id) {
  if (wp_is_post_revision($post_id)) return;
  if (!isset($_POST['bw_preloads_nonce']) || !wp_verify_nonce($_POST['bw_preloads_nonce'], 'bw_preloads_save')) return;
  if (!current_user_can('edit_page', $post_id)) return;
  $lines = isset($_POST['bw_preloads_field']) ? (string) wp_unslash($_POST['bw_preloads_field']) : '';
  // Reuse the sanitizer above by writing a string; register_post_meta will run sanitize when updating via update_post_meta?
  // We’ll sanitize here to be explicit:
  $arr = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $lines)));
  // Minimal URL sanitise
  $clean = [];
  foreach ($arr as $u) {
    if (strpos($u, '//') === 0) $u = 'https:' . $u;
    if ($u !== '' && ($u[0] === '/' || filter_var($u, FILTER_VALIDATE_URL))) $clean[] = esc_url_raw($u);
  }
  update_post_meta($post_id, '_bw_preloads', array_values(array_unique($clean)));
});

add_action('wp_head', function () {
  if (!is_page()) return;
  $assets = get_post_meta(get_queried_object_id(), '_bw_preloads', true);
  if (empty($assets) || !is_array($assets)) return;

  foreach ($assets as $u) {
    $attr = bw_infer_preload_attrs($u);
    if (!$attr) continue;
    printf(
      "<link rel=\"preload\" href=\"%s\" as=\"%s\"%s%s>\n",
      esc_url($u),
      esc_attr($attr['as']),
      !empty($attr['type']) ? ' type="'.esc_attr($attr['type']).'"' : '',
      !empty($attr['crossorigin']) ? ' crossorigin' : ''
    );
  }
}, 1);

// Helper: guess proper as= and type=
function bw_infer_preload_attrs($url) {
  $path = parse_url($url, PHP_URL_PATH) ?: '';
  $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
  switch ($ext) {
    case 'css':   return ['as'=>'style','type'=>'text/css'];
    case 'js':    return ['as'=>'script','type'=>'application/javascript'];
    case 'woff2': return ['as'=>'font','type'=>'font/woff2','crossorigin'=>true];
    case 'woff':  return ['as'=>'font','type'=>'font/woff','crossorigin'=>true];
    case 'ttf':   return ['as'=>'font','type'=>'font/ttf','crossorigin'=>true];
    case 'otf':   return ['as'=>'font','type'=>'font/otf','crossorigin'=>true];
    case 'jpg': case 'jpeg': return ['as'=>'image','type'=>'image/jpeg'];
    case 'png':   return ['as'=>'image','type'=>'image/png'];
    case 'webp':  return ['as'=>'image','type'=>'image/webp'];
    case 'gif':   return ['as'=>'image','type'=>'image/gif'];
    case 'svg':   return ['as'=>'image','type'=>'image/svg+xml'];
    default:      return ['as'=>'fetch','type'=>''];
  }
}

//Register Settings for Preload on Single Post Types
add_action('admin_init', function () {

    register_setting('brighter_tweaks', 'brighter_preload_post_types', [
        'type' => 'array',
        'sanitize_callback' => function($input) {
            return array_map('sanitize_text_field', (array)$input);
        },
        'default' => []
    ]);

    add_settings_section(
        'preload_on_singles',
        '?Preload Featured Images on Singles',
        function () { 
            echo '<p>Select the post types where featured images should be preloaded on single pages.</p>'; 
        },
        'brighter_tweaks'
    );

    add_settings_field('brighter_preload_post_types', 'Post Types', function () {
        $selected = (array) get_option('brighter_preload_post_types', []);
        $post_types = get_post_types(['public' => true], 'objects');

        foreach ($post_types as $type => $obj) {
            $checked = in_array($type, $selected) ? 'checked' : '';
            echo '<label style="display:block;margin-bottom:4px">';
            echo '<input type="checkbox" name="brighter_preload_post_types[]" value="' . esc_attr($type) . '" ' . $checked . '> ';
            echo esc_html($obj->labels->singular_name . " ($type)");
            echo '</label>';
        }
    }, 'brighter_tweaks', 'preload_on_singles');
});


// Update the preload function to use setting
function brighterweb_preload_featured_image() {
    if (is_singular()) {
        $enabled = (array) get_option('brighter_preload_post_types', []);
        $post_type = get_post_type();

        if (in_array($post_type, $enabled) && has_post_thumbnail()) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
            if ($image) {
                echo '<link rel="preload" as="image" href="' . esc_url($image[0]) . '" />' . "\n";
            }
        }
    }
}
add_action('wp_head', 'brighterweb_preload_featured_image');





/**
 * Admin list-table: Content Status badges with AJAX save
 */

// ---------- Column registration (posts + pages) ----------
add_filter('manage_edit-post_columns', 'brighter_add_status_column');
add_filter('manage_edit-page_columns', 'brighter_add_status_column');
function brighter_add_status_column($cols) {
    $cols['brighter_status'] = __('Content Status', 'brighter');
    return $cols;
}

// ---------- Column render ----------
add_action('manage_post_posts_custom_column', 'brighter_render_status_column', 10, 2);
add_action('manage_page_posts_custom_column', 'brighter_render_status_column', 10, 2);
function brighter_render_status_column($col, $post_id) {
    if ($col !== 'brighter_status') return;

    $current = get_post_meta($post_id, '_brighter_content_status', true);

    $map = brighter_status_options(); // key => [label, cssClass]
    // Badge (click to cycle)
    $label = isset($map[$current]) ? $map[$current][0] : '— Select —';
    $css   = isset($map[$current]) ? $map[$current][1] : 'bg-gray';

    printf(
        '<button type="button" class="button brighter-status-badge %1$s" data-postid="%2$d" data-value="%3$s" aria-label="%4$s">%5$s</button>',
        esc_attr($css),
        (int) $post_id,
        esc_attr($current),
        esc_attr__('Change content status', 'brighter'),
        esc_html($label)
    );

    // Fallback <select> for keyboard/screen-readers or if JS fails
    echo '<noscript><select disabled>';
    foreach ($map as $k => $def) {
        printf('<option value="%s" %s>%s</option>',
            esc_attr($k),
            selected($current, $k, false),
            esc_html($def[0])
        );
    }
    echo '</select></noscript>';
}

// ---------- AJAX save ----------
add_action('wp_ajax_brighter_save_status', function () {
    check_ajax_referer('brighter_status_nonce', 'nonce');

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => 'No permission or bad post_id.']);
    }

    $value = isset($_POST['value']) ? sanitize_text_field(wp_unslash($_POST['value'])) : '';
    $allowed = array_keys(brighter_status_options());
    if (!in_array($value, $allowed, true)) {
        wp_send_json_error(['message' => 'Invalid status value.']);
    }

    update_post_meta($post_id, '_brighter_content_status', $value);
    $label = brighter_status_options()[$value][0];
    wp_send_json_success(['value' => $value, 'label' => $label]);
});

// ---------- Enqueue (list screens only) ----------
add_action('admin_enqueue_scripts', function($hook) {
    // Both Posts and Pages lists are 'edit.php' (pages are edit.php?post_type=page)
    if ($hook !== 'edit.php') return;

    // Make sure jQuery is present
    wp_enqueue_script('jquery');

    // JS
    wp_enqueue_script(
        'brighter-status',
        plugin_dir_url(__FILE__) . 'js/brighter-status.js',
        ['jquery'],
        '1.3',
        true
    );

    // Localize config + nonce
    wp_localize_script('brighter-status', 'BRIGHTER_STATUS', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('brighter_status_nonce'),
        // send the ordered status cycle to JS
        'cycle'    => array_keys(brighter_status_options()),
    ]);

    // CSS
    wp_enqueue_style(
        'brighter-status-css',
        plugin_dir_url(__FILE__) . 'css/brighter-status.css',
        [],
        '1.2'
    );
});

// ---------- Shared map ----------
function brighter_status_options() {
    // key => [Label, CSS class]
    return [
        ''            => ['— Select —',      'bg-gray'],
        'done'        => ['Done',            'bg-green'],
        'opt90'       => ['Optimised 90+',   'bg-emerald'],
        'opt80'       => ['Optimised 80+',   'bg-teal'],
        'opt70'       => ['Optimised 70+',   'bg-cyan'],
        'improve'     => ['Improve',         'bg-orange'],
        'leave'       => ['Leave',           'bg-slate'],
        'consolidate' => ['Consolidate',     'bg-purple'],
        'repurpose'   => ['Repurpose',       'bg-blue'],
    ];
}

// Prove the hook is firing and JS runs on the list screen.
add_action('admin_footer-edit.php', function () {
    ?>
    <script>
      console.log('[Brighter] admin_footer-edit.php inline script loaded');
      // Also log clicks to prove delegation works even if external JS is missing:
      document.addEventListener('click', function(e){
        if (e.target && e.target.classList.contains('brighter-status-badge')) {
          console.log('[Brighter] badge clicked for post', e.target.dataset.postid, 'value=', e.target.dataset.value);
        }
      });
    </script>
    <?php
});
/**
 * Enqueue list-table assets from the MU plugin root, even though this file lives in /includes/.
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'edit.php') return; // posts & pages list screens

    // 1) Point to the MU plugin ROOT using the core loader file.
    $root_file = dirname(__DIR__) . '/brighter-core.php'; // …/mu-plugins/brighter-core/brighter-core.php
    $root_url  = plugin_dir_url($root_file);              // => /wp-content/mu-plugins/brighter-core/
    $root_path = plugin_dir_path($root_file);             // => filesystem path to that folder

    // 2) Asset locations relative to the ROOT.
    $js_rel  = 'js/brighter-status.js';
    $css_rel = 'css/brighter-status.css'; // optional; inline fallback provided

    $js_url  = $root_url  . $js_rel;
    $css_url = $root_url  . $css_rel;
    $js_path = $root_path . $js_rel;
    $css_path= $root_path . $css_rel;

    $js_ver  = file_exists($js_path)  ? filemtime($js_path)  : '1.0';
    $css_ver = file_exists($css_path) ? filemtime($css_path) : '1.0';

    wp_enqueue_script('jquery');

    wp_enqueue_script('brighter-status', $js_url, ['jquery'], $js_ver, true);
    wp_localize_script('brighter-status', 'BRIGHTER_STATUS', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('brighter_status_nonce'),
        'cycle'    => array_keys(brighter_status_options()),
    ]);

    // Optional external CSS + inline fallback so badges never appear as tiny blank squares
    wp_enqueue_style('brighter-status-css', $css_url, [], $css_ver);
    wp_add_inline_style('brighter-status-css', '
      .brighter-status-badge{border-radius:9999px;padding:2px 10px;line-height:1.6;border:0;color:#fff;font-weight:600;cursor:pointer;background:#6b7280}
      .brighter-status-badge.is-saving{opacity:.6;pointer-events:none}
      .bg-gray{background:#6b7280}.bg-green{background:#16a34a}.bg-emerald{background:#059669}.bg-teal{background:#0d9488}
      .bg-cyan{background:#0891b2}.bg-orange{background:#f59e0b}.bg-slate{background:#475569}.bg-purple{background:#7c3aed}.bg-blue{background:#2563eb}
    ');

    // Tiny console ping so you can see it's loaded
    add_action('admin_footer-edit.php', function () {
        echo "<script>console.log('[Brighter] status assets enqueued from MU root');</script>";
    });
});
