<?php
/**
 * Plugin Name: QPedia Article Updater (v2026.09.13d)
 * Description: به‌روزرسانی تک‌دکمه‌ای مقالات quantum_article از روی فایل‌های JSON در پوشه payloads. تصاویر درون‌متن ابتدا از پوشهٔ assets داخل خود افزونه خوانده می‌شوند (برای جلوگیری از timeout اینترنت بین‌الملل) و فقط در صورت نبودن از GitHub Pages دانلود می‌گردند (تا ۳ بار تلاش). تصویر شاخص را دست نمی‌زند.
 * Version: 2026.09.13d
 * Author: Arena Agent for QPedia
 * Depends: qpedia-fixes (optional, for CPT definitions if already active)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QAU_VERSION', '2026.09.13d' );
define( 'QAU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QAU_PAYLOAD_DIR', QAU_PLUGIN_DIR . 'payloads/' );
define( 'QAU_ASSETS_DIR', QAU_PLUGIN_DIR . 'assets/images/' );
define( 'QAU_ALLOWED_POST_TYPES', array( 'quantum_article', 'quantum_scientist' ) );
define( 'QAU_REMOTE_BASE', 'https://lakanzino.github.io/NANA/images/' );

/* -------------------- Admin menu -------------------- */
add_action( 'admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=quantum_article',
        'QPedia Updater',
        '📥 به‌روزرسانی مقاله',
        'manage_options',
        'qpedia-updater',
        'qau_render_admin'
    );
} );

/* -------------------- Handler -------------------- */
add_action( 'admin_init', function () {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( empty( $_GET['qau_action'] ) ) return;
    if ( ! isset( $_GET['qau_nonce'] ) || ! wp_verify_nonce( $_GET['qau_nonce'], 'qau_run' ) ) {
        wp_die( 'Nonce نامعتبر است.' );
    }

    $action  = sanitize_key( $_GET['qau_action'] );
    $payload = isset( $_GET['qau_payload'] ) ? sanitize_file_name( $_GET['qau_payload'] ) : '';

    if ( in_array( $action, array( 'dryrun', 'apply' ), true ) && $payload ) {
        qau_run( $payload, ( $action === 'dryrun' ) );
    }
} );

function qau_run( $filename, $dry_run = true ) {
    $path = QAU_PAYLOAD_DIR . $filename . '.json';
    if ( ! file_exists( $path ) ) {
        qau_flash( '❌ فایل payload پیدا نشد: ' . esc_html( $filename ), 'error' );
        wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
        exit;
    }
    $raw  = file_get_contents( $path );
    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) ) {
        qau_flash( '❌ JSON نامعتبر است.', 'error' );
        wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
        exit;
    }

    $data = wp_parse_args( $data, array(
        'slug'            => '',
        'post_type'       => 'quantum_article',
        'title'           => '',
        'excerpt'         => '',
        'body_html'       => '',
        'featured_image'  => '',
        'inline_images'   => array(),
        'categories'      => array(),
    ) );

    if ( ! in_array( $data['post_type'], QAU_ALLOWED_POST_TYPES, true ) ) {
        qau_flash( '❌ post_type نامعتبر: ' . esc_html( $data['post_type'] ), 'error' );
        wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
        exit;
    }

    $existing = get_page_by_path( $data['slug'], OBJECT, $data['post_type'] );
    if ( ! $existing ) {
        qau_flash( '❌ مقاله‌ای با این اسلاگ پیدا نشد — مطمئن شوید اسلاگ درست است: ' . esc_html( $data['slug'] ), 'error' );
        wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
        exit;
    }

    $post_id = $existing->ID;
    $log     = array();
    $log[]   = ( $dry_run ? '[DRY-RUN] ' : '[APPLY] ' ) . 'شروع به‌روزرسانی «' . $data['title'] . '» (اسلاگ: ' . $data['slug'] . '، شناسه: ' . $post_id . ')';

    $body = $data['body_html'];

    // در حالت طراحی ما featured_image همیشه خالی است؛ ولی اگر بود هم محترمانه عمل می‌کنیم
    $featured_id = 0;
    if ( ! empty( $data['featured_image'] ) ) {
        $res = qau_maybe_sideload( $data['featured_image'], $post_id, $dry_run );
        $featured_id = $res['id'];
        $log[] = '🖼 تصویر شاخص: ' . $res['log'];
    } else {
        $log[] = 'ℹ️ تصویر شاخص در payload خالی است — تصویر شاخص فعلی سایت حفظ می‌شود.';
    }

    $replace_map = array();
    foreach ( (array) $data['inline_images'] as $remote => $meta ) {
        $res = qau_maybe_sideload( $remote, $post_id, $dry_run );
        if ( ! empty( $res['url'] ) ) {
            $replace_map[ $remote ] = $res['url'];
        }
        $log[] = '🖼 درون‌متن: ' . $res['log'];
    }
    if ( $replace_map ) {
        $body = str_replace( array_keys( $replace_map ), array_values( $replace_map ), $body );
    }

    $postarr = array(
        'ID'           => $post_id,
        'post_title'   => $data['title'],
        'post_content' => $body,
        'post_excerpt' => $data['excerpt'],
        'post_type'    => $data['post_type'],
    );

    if ( ! empty( $data['categories'] ) ) {
        $cat_ids = array();
        foreach ( (array) $data['categories'] as $cat_slug ) {
            $t = get_term_by( 'slug', $cat_slug, 'quantum_category' );
            if ( $t ) { $cat_ids[] = (int) $t->term_id; }
            else      { $log[] = '⚠️ دسته پیدا نشد: ' . $cat_slug; }
        }
        if ( $cat_ids ) {
            wp_set_object_terms( $post_id, $cat_ids, 'quantum_category', false );
        }
    }

    if ( ! $dry_run ) {
        $pid = wp_update_post( wp_slash( $postarr ), true );
        if ( is_wp_error( $pid ) ) {
            qau_flash( '❌ خطا در به‌روزرسانی: ' . $pid->get_error_message(), 'error' );
            wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
            exit;
        }
        if ( $featured_id ) { set_post_thumbnail( $pid, $featured_id ); }
        $log[] = '✅ مقاله به‌روز شد (شناسه: ' . $pid . ').';
    } else {
        $log[] = '🟡 در حالت dry-run هیچ تغییری در دیتابیس اعمال نشد.';
    }

    set_transient( 'qau_last_log', $log, 60 );
    wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater&qau_done=1' ) );
    exit;
}

/**
 * دانلود/sideload تصویر. اولویت:
 *   ۱) اگر قبلاً با همین _qau_source_url پیوست شده، همان را برمی‌گرداند.
 *   ۲) اگر فایل در پوشهٔ assets/images/ داخل افزونه هست، مستقیم از روی دیسک sideload می‌کند (بدون اینترنت).
 *   ۳) در غیر این صورت از آدرس remote با ۳ بار تلاش و تایم‌اوت ۳۰ ثانیه دانلود می‌کند.
 */
function qau_maybe_sideload( $url, $post_id, $dry_run ) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $filename = basename( wp_parse_url( $url, PHP_URL_PATH ) );

    $existing = get_posts( array(
        'post_type'      => 'attachment',
        'meta_key'       => '_qau_source_url',
        'meta_value'     => $url,
        'posts_per_page' => 1,
    ) );
    if ( $existing ) {
        $att_id  = $existing[0]->ID;
        $att_url = wp_get_attachment_url( $att_id );
        return array( 'id' => $att_id, 'url' => $att_url, 'log' => 'از قبل موجود است (شناسه ' . $att_id . '): ' . $filename );
    }

    if ( $dry_run ) {
        $local_hint = file_exists( QAU_ASSETS_DIR . $filename ) ? ' (از پوشهٔ محلی افزونه)' : ' (نیازمند دانلود از GitHub Pages)';
        return array( 'id' => 0, 'url' => $url, 'log' => 'در apply پیوست خواهد شد' . $local_hint . ': ' . $filename );
    }

    // گام ۱: اگر فایل داخل خود افزونه هست، مستقیم از دیسک بردار و داخل tmp کپی کن که media_handle_sideload بتواند مدیریت کند.
    $local_file = QAU_ASSETS_DIR . $filename;
    if ( file_exists( $local_file ) && is_readable( $local_file ) ) {
        $tmp = wp_tempnam( $filename );
        if ( $tmp && @copy( $local_file, $tmp ) ) {
            $file_array = array( 'name' => $filename, 'tmp_name' => $tmp );
            $att_id = media_handle_sideload( $file_array, $post_id );
            if ( ! is_wp_error( $att_id ) ) {
                update_post_meta( $att_id, '_qau_source_url', $url );
                $att_url = wp_get_attachment_url( $att_id );
                return array(
                    'id'  => $att_id,
                    'url' => $att_url,
                    'log' => '✅ از پوشهٔ داخلی افزونه پیوست شد: ' . $filename . ' (' . size_format( filesize( get_attached_file( $att_id ) ) ) . ')',
                );
            }
            @unlink( $tmp );
        }
    }

    // گام ۲: دانلود از remote با retry
    $tmp = false;
    $last_err = '';
    for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
        $timeout = 20 + ( $attempt - 1 ) * 15; // 20s, 35s, 50s
        $tmp = download_url( $url, $timeout );
        if ( ! is_wp_error( $tmp ) ) { break; }
        $last_err = $tmp->get_error_message();
        if ( $attempt < 3 ) { sleep( 2 ); }
    }
    if ( is_wp_error( $tmp ) ) {
        return array( 'id' => 0, 'url' => $url, 'log' => '❌ دانلود پس از ۳ تلاش شکست خورد: ' . $filename . ' — ' . $last_err );
    }

    $file_array = array( 'name' => $filename, 'tmp_name' => $tmp );
    $att_id = media_handle_sideload( $file_array, $post_id );
    if ( is_wp_error( $att_id ) ) {
        @unlink( $tmp );
        return array( 'id' => 0, 'url' => $url, 'log' => '❌ sideload شکست: ' . $filename . ' — ' . $att_id->get_error_message() );
    }

    update_post_meta( $att_id, '_qau_source_url', $url );
    $att_url = wp_get_attachment_url( $att_id );
    return array(
        'id'  => $att_id,
        'url' => $att_url,
        'log' => '✅ دانلود و پیوست شد (اینترنت): ' . $filename . ' (' . size_format( filesize( get_attached_file( $att_id ) ) ) . ')',
    );
}

/* -------------------- Admin page -------------------- */
function qau_render_admin() {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }

    $log = get_transient( 'qau_last_log' );
    if ( $log ) { delete_transient( 'qau_last_log' ); }

    $files = array();
    if ( is_dir( QAU_PAYLOAD_DIR ) ) {
        foreach ( glob( QAU_PAYLOAD_DIR . '*.json' ) as $f ) {
            $raw  = file_get_contents( $f );
            $data = json_decode( $raw, true );
            $files[] = array(
                'file'     => basename( $f, '.json' ),
                'title'    => isset( $data['title'] ) ? $data['title'] : '(عنوان ندارد)',
                'slug'     => isset( $data['slug'] ) ? $data['slug'] : '',
                'modified' => date( 'Y-m-d H:i', filemtime( $f ) ),
                'size'     => size_format( filesize( $f ) ),
            );
        }
    }
    ?>
    <div class="wrap">
        <h1>📥 به‌روزرسانی مقالات QPedia</h1>
        <p style="max-width:720px">
            این افزونه مقالهٔ <strong>ازقبل‌منتشرشده</strong> را بر اساس فایل JSON در پوشهٔ <code>payloads/</code> به‌روز می‌کند.
            تصاویر درون‌متن ابتدا از پوشهٔ <code>assets/images/</code> داخل خود افزونه خوانده می‌شوند (نیازی به اینترنت نیست) و در صورت نبودن تا ۳ بار از GitHub Pages دانلود می‌گردند.
            تصویر شاخص فعلی سایت دست نمی‌خورد.
        </p>
        <p><strong>نسخهٔ افزونه:</strong> <?php echo esc_html( QAU_VERSION ); ?> — تعداد payloadها: <?php echo count( $files ); ?></p>

        <?php if ( $log ) : ?>
            <div class="notice notice-info" style="white-space:pre-wrap;font-family:monospace"><?php echo esc_html( implode( "\n", $log ) ); ?></div>
        <?php endif; ?>

        <table class="wp-list-table widefat fixed striped" style="max-width:960px">
            <thead><tr>
                <th>فایل payload</th><th>عنوان</th><th>اسلاگ</th><th>تاریخ</th><th>حجم</th><th>عملیات</th>
            </tr></thead>
            <tbody>
            <?php if ( ! $files ) : ?>
                <tr><td colspan="6">هیچ فایل payload پیدا نشد.</td></tr>
            <?php else : foreach ( $files as $f ) :
                $dry_url   = wp_nonce_url( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater&qau_action=dryrun&qau_payload=' . rawurlencode( $f['file'] ) ), 'qau_run', 'qau_nonce' );
                $apply_url = wp_nonce_url( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater&qau_action=apply&qau_payload=' . rawurlencode( $f['file'] ) ), 'qau_run', 'qau_nonce' );
            ?>
                <tr>
                    <td><code><?php echo esc_html( $f['file'] ); ?></code></td>
                    <td><?php echo esc_html( $f['title'] ); ?></td>
                    <td><code><?php echo esc_html( $f['slug'] ); ?></code></td>
                    <td><?php echo esc_html( $f['modified'] ); ?></td>
                    <td><?php echo esc_html( $f['size'] ); ?></td>
                    <td>
                        <a class="button" href="<?php echo esc_url( $dry_url ); ?>">🟡 پیش‌نمایش (Dry-Run)</a>
                        <a class="button button-primary" href="<?php echo esc_url( $apply_url ); ?>" onclick="return confirm('اعمال به‌روزرسانی؟');">✅ اعمال به‌روزرسانی</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function qau_flash( $msg ) {
    $m = get_transient( 'qau_last_log' ) ?: array();
    array_unshift( $m, $msg );
    set_transient( 'qau_last_log', $m, 60 );
}
