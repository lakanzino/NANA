<?php
/**
 * Plugin Name: QPedia Article Importer (v2026.09.13g2)
 * Description: درون‌ریزی/به‌روزرسانی تک‌دکمه‌ای مقالات quantum_article. اگر اسلاگ موجود باشد مقاله آپدیت می‌شود (بدون دست زدن به تصویر شاخص در صورتی که در payload خالی باشد)؛ اگر اسلاگ موجود نباشد، مقالهٔ جدید به صورت پیش‌نویس (draft) با تمام جزئیات — تایتل، متا، تصویر شاخص و alt، دیاگرام درون‌متن و alt، دسته/زیردسته، اسلاگ — ساخته می‌شود.
 * Version: 2026.09.13g2
 * Author: Arena Agent for QPedia
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QAU_VERSION', '2026.09.13g2' );
define( 'QAU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QAU_PAYLOAD_DIR', QAU_PLUGIN_DIR . 'payloads/' );
define( 'QAU_ASSETS_DIR', QAU_PLUGIN_DIR . 'assets/images/' );
define( 'QAU_ALLOWED_POST_TYPES', array( 'quantum_article', 'quantum_scientist' ) );
define( 'QAU_REMOTE_BASE', 'https://lakanzino.github.io/NANA/images/' );

/* -------------------- Admin menu -------------------- */
add_action( 'admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=quantum_article',
        'QPedia Importer',
        '📥 درون‌ریزی مقاله',
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

/**
 * اجرای درون‌ریزی روی یک payload.
 * منطق:
 *  - اگر پستی با این اسلاگ از قبل وجود داشت: به‌روزرسانی می‌شود (wp_update_post).
 *  - اگر وجود نداشت: پست جدید با وضعیت draft ساخته می‌شود (wp_insert_post).
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

    $data = wp_parse_args( $data, array(
        'slug'                => '',
        'post_type'           => 'quantum_article',
        'post_status'         => 'draft',
        'title'               => '',
        'excerpt'             => '',
        'body_html'           => '',
        'featured_image'      => '',
        'featured_image_alt'  => '',
        'inline_images'       => array(),
        'categories'          => array(),
        'tags'                => array(),
        'meta'                => array(),   // meta_key => value برای post_meta اختیاری
    ) );

    if ( ! in_array( $data['post_type'], QAU_ALLOWED_POST_TYPES, true ) ) {
        qau_flash( '❌ post_type نامعتبر: ' . esc_html( $data['post_type'] ), 'error' );
        wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
        exit;
    }
    if ( ! $data['slug'] ) {
        qau_flash( '❌ فیلد slug در payload خالی است.', 'error' );
        wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
        exit;
    }

    // بررسی وجود پست
    $existing = get_page_by_path( $data['slug'], OBJECT, $data['post_type'] );
    $is_new   = ! $existing;

    if ( $is_new ) {
        $log[] = ( $dry_run ? '[DRY-RUN] ' : '[APPLY] ' ) . '📝 مقالهٔ جدید ساخته خواهد شد (پیش‌نویس): «' . $data['title'] . '» (اسلاگ: ' . $data['slug'] . ')';
        $post_id = 0;
    } else {
        $post_id = $existing->ID;
        $log[]   = ( $dry_run ? '[DRY-RUN] ' : '[APPLY] ' ) . '🔄 به‌روزرسانی مقالهٔ موجود «' . $data['title'] . '» (اسلاگ: ' . $data['slug'] . '، شناسه: ' . $post_id . ')';
    }

    // ---- تصویر شاخص (فقط اگر در payload معرفی شده باشد: برای مقالات جدید کاور و برای آپدیت‌های دلخواه)
    $featured_id = 0;
    if ( ! empty( $data['featured_image'] ) ) {
        // برای مقالات جدید post_id هنوز ۰ است اما media_handle_sideload در صورت 0 در کتابخانه آپلود می‌کند؛ بعد از insert/post متصل می‌کنیم.
        $attach_to = $post_id ? $post_id : 0;
        $res = qau_maybe_sideload( $data['featured_image'], $attach_to, $dry_run );
        $featured_id = $res['id'];
        $log[] = '🖼 تصویر شاخص: ' . $res['log'];
        if ( ! $dry_run && $featured_id && ! empty( $data['featured_image_alt'] ) ) {
            update_post_meta( $featured_id, '_wp_attachment_image_alt', sanitize_text_field( $data['featured_image_alt'] ) );
        }
    } else {
        $log[] = 'ℹ️ تصویر شاخص در payload خالی است — ' . ( $is_new ? 'هشدار: بدون تصویر شاخص ذخیره می‌شود' : 'تصویر شاخص فعلی حفظ می‌شود' ) . '.';
    }

    // ---- بدنه + تصاویر درون‌متن
    $body = $data['body_html'];
    $replace_map = array();
    foreach ( (array) $data['inline_images'] as $remote => $meta ) {
        $alt  = is_array( $meta ) && ! empty( $meta['alt'] ) ? $meta['alt'] : '';
        $res  = qau_maybe_sideload( $remote, $post_id ? $post_id : 0, $dry_run );
        if ( ! empty( $res['url'] ) ) {
            $replace_map[ $remote ] = $res['url'];
        }
        $log[] = '🖼 درون‌متن: ' . $res['log'];
        if ( ! $dry_run && $res['id'] && $alt ) {
            update_post_meta( $res['id'], '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
        }
    }
    if ( $replace_map ) {
        $body = str_replace( array_keys( $replace_map ), array_values( $replace_map ), $body );
    }

    // ---- آرگومان‌های پست
    $postarr = array(
        'post_title'   => $data['title'],
        'post_content' => $body,
        'post_excerpt' => $data['excerpt'],
        'post_type'    => $data['post_type'],
        'post_name'    => sanitize_title( $data['slug'] ),
        'post_status'  => $is_new ? $data['post_status'] : $existing->post_status,
    );
    if ( ! $is_new ) {
        $postarr['ID'] = $post_id;
    }

    if ( ! $dry_run ) {
        if ( $is_new ) {
            $pid = wp_insert_post( wp_slash( $postarr ), true );
            if ( is_wp_error( $pid ) ) {
                qau_flash( '❌ خطا در ساخت مقاله: ' . $pid->get_error_message(), 'error' );
                wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
                exit;
            }
            $post_id = $pid;
            // بعد از ساخت پست، پیوست‌هایی را که به ۰ چسبیده بودند، به post_id نسبت دهیم
            if ( $featured_id ) { wp_update_post( array( 'ID' => $featured_id, 'post_parent' => $post_id ) ); }
            // اتصال تصاویر درون‌متن (اگر در طول sideload به ۰ وصل شده بودند)
            foreach ( $replace_map as $remote => $local_url ) {
                $att_id = attachment_url_to_postid( $local_url );
                if ( $att_id ) { wp_update_post( array( 'ID' => $att_id, 'post_parent' => $post_id ) ); }
            }
            $log[] = '✅ مقالهٔ جدید ساخته شد (شناسه: ' . $post_id . '، وضعیت: ' . $data['post_status'] . ').';
        } else {
            $pid = wp_update_post( wp_slash( $postarr ), true );
            if ( is_wp_error( $pid ) ) {
                qau_flash( '❌ خطا در به‌روزرسانی: ' . $pid->get_error_message(), 'error' );
                wp_redirect( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater' ) );
                exit;
            }
            $log[] = '✅ مقاله به‌روز شد (شناسه: ' . $pid . ').';
        }

        // تصویر شاخص
        if ( $featured_id ) { set_post_thumbnail( $post_id, $featured_id ); }

        // دسته‌ها
        if ( ! empty( $data['categories'] ) ) {
            $cat_ids = array();
            foreach ( (array) $data['categories'] as $cat_slug ) {
                $t = get_term_by( 'slug', $cat_slug, 'quantum_category' );
                if ( $t ) { $cat_ids[] = (int) $t->term_id; }
                else      { $log[] = '⚠️ دسته پیدا نشد: ' . $cat_slug; }
            }
            if ( $cat_ids ) { wp_set_object_terms( $post_id, $cat_ids, 'quantum_category', false ); }
        }

        // meta fields اختیاری
        if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
            foreach ( $data['meta'] as $mk => $mv ) {
                update_post_meta( $post_id, sanitize_key( $mk ), $mv );
            }
        }
    } else {
        // در dry-run دسته‌ها را هم چک کنیم
        if ( ! empty( $data['categories'] ) ) {
            foreach ( (array) $data['categories'] as $cat_slug ) {
                $t = get_term_by( 'slug', $cat_slug, 'quantum_category' );
                if ( ! $t ) { $log[] = '⚠️ دسته پیدا نشد: ' . $cat_slug; }
            }
        }
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
 *   ۳) در غیر این صورت از آدرس remote با ۳ بار تلاش و تایم‌اوت افزایش‌یافته دانلود می‌کند.
 */
function qau_maybe_sideload( $url, $post_id = 0, $dry_run = false ) {
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

    // گام ۱: فایل محلی درون افزونه
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
        $timeout = 20 + ( $attempt - 1 ) * 15;
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
            $slug = isset( $data['slug'] ) ? $data['slug'] : '';
            $exists = $slug && get_page_by_path( $slug, OBJECT, isset( $data['post_type'] ) ? $data['post_type'] : 'quantum_article' );
            $files[] = array(
                'file'     => basename( $f, '.json' ),
                'title'    => isset( $data['title'] ) ? $data['title'] : '(عنوان ندارد)',
                'slug'     => $slug,
                'is_new'   => ! $exists,
                'modified' => date( 'Y-m-d H:i', filemtime( $f ) ),
                'size'     => size_format( filesize( $f ) ),
            );
        }
    }
    ?>
    <div class="wrap">
        <h1>📥 درون‌ریزی مقالات QPedia</h1>
        <p style="max-width:720px">
            این افزونه فایل‌های JSON پوشهٔ <code>payloads/</code> را یکی‌یکی درون‌ریزی می‌کند.<br>
            <strong>اگر اسلاگ موجود باشد:</strong> همان مقاله به‌روز می‌شود (و اگر <code>featured_image</code> خالی باشد تصویر شاخص فعلی دست نمی‌خورد).<br>
            <strong>اگر اسلاگ موجود نباشد:</strong> مقالهٔ جدید به‌صورت <strong>پیش‌نویس (draft)</strong> با همهٔ جزئیات — تایتل، متا، تصویر شاخص و alt، دیاگرام درون‌متن و alt، دسته/زیردسته، اسلاگ — ساخته می‌شود.
        </p>
        <p>
            تصاویر ابتدا از پوشهٔ <code>assets/images/</code> داخل خود افزونه بارگذاری می‌شوند (بدون نیاز به اینترنت).<br>
            <strong>نسخهٔ افزونه:</strong> <?php echo esc_html( QAU_VERSION ); ?> — تعداد payloadها: <?php echo count( $files ); ?>
        </p>

        <?php if ( $log ) : ?>
            <div class="notice notice-info" style="white-space:pre-wrap;font-family:monospace"><?php echo esc_html( implode( "\n", $log ) ); ?></div>
        <?php endif; ?>

        <table class="wp-list-table widefat fixed striped" style="max-width:1020px">
            <thead><tr>
                <th>فایل</th><th>عنوان</th><th>اسلاگ</th><th>وضعیت</th><th>تاریخ</th><th>حجم</th><th>عملیات</th>
            </tr></thead>
            <tbody>
            <?php if ( ! $files ) : ?>
                <tr><td colspan="7">هیچ فایل payload پیدا نشد.</td></tr>
            <?php else : foreach ( $files as $f ) :
                $dry_url   = wp_nonce_url( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater&qau_action=dryrun&qau_payload=' . rawurlencode( $f['file'] ) ), 'qau_run', 'qau_nonce' );
                $apply_url = wp_nonce_url( admin_url( 'edit.php?post_type=quantum_article&page=qpedia-updater&qau_action=apply&qau_payload=' . rawurlencode( $f['file'] ) ), 'qau_run', 'qau_nonce' );
                $badge = $f['is_new']
                    ? '<span style="background:#06b6d4;color:#fff;padding:2px 8px;border-radius:10px;font-size:.75rem">جدید (پیش‌نویس)</span>'
                    : '<span style="background:#16a34a;color:#fff;padding:2px 8px;border-radius:10px;font-size:.75rem">موجود (آپدیت)</span>';
            ?>
                <tr>
                    <td><code><?php echo esc_html( $f['file'] ); ?></code></td>
                    <td><?php echo esc_html( $f['title'] ); ?></td>
                    <td><code><?php echo esc_html( $f['slug'] ); ?></code></td>
                    <td><?php echo $badge; ?></td>
                    <td><?php echo esc_html( $f['modified'] ); ?></td>
                    <td><?php echo esc_html( $f['size'] ); ?></td>
                    <td>
                        <a class="button" href="<?php echo esc_url( $dry_url ); ?>">🟡 پیش‌نمایش</a>
                        <a class="button button-primary" href="<?php echo esc_url( $apply_url ); ?>" onclick="return confirm('اعمال درون‌ریزی؟');"><?php echo $f['is_new'] ? '✅ درون‌ریزی (draft)' : '✅ به‌روزرسانی'; ?></a>
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
