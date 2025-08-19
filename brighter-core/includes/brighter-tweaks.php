<?php
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
        $total_pages = $q->max_num_pages ?: 1;
        if ($total_pages > 1){
          echo '<p>';
          for ($i=1;$i<=$total_pages;$i++){
            $link = add_query_arg(['page'=>'brighter_tweaks','s'=>$search,'paged'=>$i], admin_url('admin.php'));
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
