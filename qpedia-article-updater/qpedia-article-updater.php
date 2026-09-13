<?php
/**
 * Plugin Name: QPedia Article Updater (v2026.09.13)
 * Description: به‌روزرسانی تک‌دکمه‌ای مقالات quantum_article از روی فایل‌های JSON در پوشه payloads. یک دکمه در پیشخوان، یک لاگ ساده، و ایمپورت تصویر شاخص + تصاویر درون‌متن. برای به‌روزرسانی مقالهٔ ازقبل‌منتشرشده (نوشتن روی اسلاگ موجود)، نه ساخت مقالهٔ جدید.
 * Version: 2026.09.13
 * Author: Arena Agent for QPedia
 * Requires Plugins: qpedia-fixes (برای CPT quantum_article)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QAU_VERSION', '2026.09.13' );
define( 'QAU_PAYLOAD_DIR', plugin_dir_path( __FILE__ ) . 'payloads/' );
define( 'QAU_ALLOWED_POST_TYPES', array( 'quantum_article', 'quantum_scientist' ) );
define( 'QAU_REMOTE_BASE', 'https://lakanzino.github.io/NANA/' ); // آدرس PWA برای دانلود تصاویر

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

    if ( $action === 'dryrun' && $payload ) {
        qau_run( $payload, true );
    } elseif ( $action === 'apply' && $payload ) {
        qau_run( $payload, false );
    }
} );

/**
 * اجرای به‌روزرسانی روی یک payload.
 */
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

    // Field defaults
    $data = wp_parse_args( $data, array(
        'slug'            => '',
        'post_type'       => 'quantum_article',
        'title'           => '',
        'excerpt'         => '',
        'body_html'       => '',
        'featured_image'  => '',
        'inline_images'   => array(),
        'categories'      => array(), // slugs of quantum_category
        'tags'            => array(),
    ) );

    if ( ! in_array( $data['post_type'], QAU_ALLOWED_POST_TYPES, true ) ) {
        qau_flash( '❌ post_type نامعتبر: ' . esc_html( $data['post_type'] ), 'error' );
        wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
        exit;
    }

    // Find existing post by slug
    $existing = get_page_by_path( $data['slug'], OBJECT, $data['post_type'] );
    if ( ! $existing ) {
        qau_flash( '❌ مقاله‌ای با این اسلاگ پیدا نشد — اول دستی در وردپرس بسازید یا مطمئن شوید اسلاگ درست است: ' . esc_html( $data['slug'] ), 'error' );
        wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
        exit;
    }

    $post_id = $existing->ID;
    $log     = array();
    $log[]   = ( $dry_run ? '[DRY-RUN] ' : '[APPLY] ' ) . 'شروع به‌روزرسانی «' . $data['title'] . '» (اسلاگ: ' . $data['slug'] . '، شناسه: ' . $post_id . ')';

    // Body HTML (replace remote image placeholders with local URLs after sideload)
    $body = $data['body_html'];

    // --- Sideload images ---
    // 1. featured
    $featured_id = 0;
    if ( ! empty( $data['featured_image'] ) ) {
        $res = qau_maybe_sideload( $data['featured_image'], $post_id, $dry_run );
        $featured_id = $res['id'];
        $log[] = '🖼 تصویر شاخص: ' . $res['log'];
    }

    // 2. inline images: map remote URL => local URL
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

    // --- Build post args ---
    $postarr = array(
        'ID'           => $post_id,
        'post_title'   => $data['title'],
        'post_content' => $body,
        'post_excerpt' => $data['excerpt'],
        'post_type'    => $data['post_type'],
    );

    // --- Taxonomies ---
    $tax_input = array();
    if ( ! empty( $data['categories'] ) ) {
        $cat_ids = array();
        foreach ( (array) $data['categories'] as $cat_slug ) {
            $t = get_term_by( 'slug', $cat_slug, 'quantum_category' );
            if ( $t ) $cat_ids[] = (int) $t->term_id;
            else $log[] = '⚠️ دسته پیدا نشد: ' . $cat_slug;
        }
        if ( $cat_ids ) {
            $tax_input['quantum_category'] = wp_set_object_terms( $post_id, $cat_ids, 'quantum_category', false );
        }
    }

    // --- Apply ---
    if ( ! $dry_run ) {
        $post_id = wp_update_post( wp_slash( $postarr ), true );
        if ( is_wp_error( $post_id ) ) {
            qau_flash( '❌ خطا در به‌روزرسانی مقاله: ' . $post_id->get_error_message(), 'error' );
            wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
            exit;
        }
        if ( $featured_id ) {
            set_post_thumbnail( $post_id, $featured_id );
        }
        $log[] = '✅ مقاله به‌روز شد (شناسه: ' . $post_id . ').';
    } else {
        $log[] = '🟡 در حالت dry-run هیچ تغییری در دیتابیس اعمال نشد.';
    }

    set_transient( 'qau_last_log', $log, 60 );
    wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater&qau_done=1' ) );
    exit;
}

/**
 * دانلود تصویر از remote و sideload به عنوان پیوست این پست.
 * اگر قبلاً با همین نام فایل پیوست شده (با جستجو بر اساس عنوان فایل)، دانلود مجدد نمی‌کند.
 */
function qau_maybe_sideload( $url, $post_id, $dry_run ) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $filename = basename( wp_parse_url( $url, PHP_URL_PATH ) );

    // ببینیم قبلاً پیوست شده یا نه
    $existing = get_posts( array(
        'post_type'  => 'attachment',
        'meta_key'   => '_qau_source_url',
        'meta_value' => $url,
        'posts_per_page' => 1,
    ) );
    if ( $existing ) {
        $att_id  = $existing[0]->ID;
        $att_url = wp_get_attachment_url( $att_id );
        return array( 'id' => $att_id, 'url' => $att_url, 'log' => 'از قبل موجود است (شناسه ' . $att_id . '): ' . $filename );
    }

    if ( $dry_run ) {
        return array( 'id' => 0, 'url' => $url, 'log' => 'در apply دانلود خواهد شد: ' . $filename . ' ← ' . $url );
    }

    $tmp = download_url( $url, 30 );
    if ( is_wp_error( $tmp ) ) {
        return array( 'id' => 0, 'url' => $url, 'log' => '❌ دانلود شکست خورد: ' . $filename . ' — ' . $tmp->get_error_message() );
    }

    $file_array = array(
        'name'     => $filename,
        'tmp_name' => $tmp,
    );

    $att_id = media_handle_sideload( $file_array, $post_id );
    if ( is_wp_error( $att_id ) ) {
        @unlink( $tmp );
        return array( 'id' => 0, 'url' => $url, 'log' => '❌ sideload شکست: ' . $filename . ' — ' . $att_id->get_error_message() );
    }

    update_post_meta( $att_id, '_qau_source_url', $url );
    $att_url = wp_get_attachment_url( $att_id );
    return array( 'id' => $att_id, 'url' => $att_url, 'log' => '✅ دانلود و پیوست شد: ' . $filename . ' (' . size_format( filesize( get_attached_file( $att_id ) ) ) . ')' );
}

/* -------------------- Admin page -------------------- */
function qau_render_admin() {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }

    $log = get_transient( 'qau_last_log' );
    if ( $log ) { delete_transient( 'qau_last_log' ); }

    $files = array();
    if ( is_dir( QAU_PAYLOAD_DIR ) ) {
        foreach ( glob( QAU_PAYLOAD_DIR . '*.json' ) as $f ) {
            $name = basename( $f, '.json' );
            $raw  = file_get_contents( $f );
            $data = json_decode( $raw, true );
            $files[] = array(
                'file'     => $name,
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
        <p style="max-width:720px">این افزونه مقالهٔ <strong>ازقبل‌منتشرشده</strong> را بر اساس فایل JSON در پوشهٔ <code>payloads/</code> به‌روز می‌کند.
        مراحل: ۱) ابتدا دکمهٔ «پیش‌نمایش (Dry-Run)» را بزنید تا لاگ را ببینید. ۲) اگر درست بود «اعمال به‌روزرسانی» را بزنید. تصاویر شاخص و درون‌متن به‌طور خودکار از آدرس GitHub Pages دانلود و در کتابخانهٔ رسانه وردپرس ذخیره می‌شوند.</p>

        <?php if ( $log ) : ?>
            <div class="notice notice-info" style="white-space:pre-wrap;font-family:monospace"><?php echo esc_html( implode( "\n", $log ) ); ?></div>
        <?php endif; ?>

        <table class="wp-list-table widefat fixed striped" style="max-width:960px">
            <thead>
                <tr>
                    <th>فایل payload</th>
                    <th>عنوان</th>
                    <th>اسلاگ</th>
                    <th>تاریخ</th>
                    <th>حجم</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! $files ) : ?>
                    <tr><td colspan="6">هیچ فایل payload در پوشهٔ <code>wp-content/plugins/qpedia-article-updater/payloads/</code> پیدا نشد.</td></tr>
                <?php else : ?>
                    <?php foreach ( $files as $f ) :
                        $dry_url  = wp_nonce_url( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater&qau_action=dryrun&qau_payload=' . rawurlencode( $f['file'] ) ), 'qau_run', 'qau_nonce' );
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
                            <a class="button button-primary" href="<?php echo esc_url( $apply_url ); ?>" onclick="return confirm('اعمال به‌روزرسانی روی مقاله؟ این کار محتوای فعلی را روی اسلاگ می‌نویسد.');">✅ اعمال به‌روزرسانی</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <h2>راهنمای سریع</h2>
        <ol>
            <li>فایل JSON مقاله را در <code>wp-content/plugins/qpedia-article-updater/payloads/</code> آپلود کنید (مثلاً با FTP یا از طریق افزونه File Manager).</li>
            <li>صفحه را رفرش کنید؛ فایل در جدول بالا ظاهر می‌شود.</li>
            <li>اول <strong>پیش‌نمایش</strong> را بزنید تا مطمئن شوید اسلاگ پیدا می‌شود و تصاویر قابل دانلودند.</li>
            <li>بعد <strong>اعمال به‌روزرسانی</strong> را بزنید.</li>
            <li>پس از پایان، مقاله را در سایت بررسی کنید. در صورت نیاز به ویرایش، مثل همیشه از ویرایشگر وردپرس استفاده کنید.</li>
        </ol>
    </div>
    <?php
}

/* -------------------- Flash via transient -------------------- */
function qau_flash( $msg, $type = 'success' ) {
    $m = get_transient( 'qau_last_log' ) ?: array();
    array_unshift( $m, $msg );
    set_transient( 'qau_last_log', $m, 60 );
}
