<?php
/**
 * Plugin Name: QPedia Edu Images Batch 1 FA - Fundamentals
 * Description: به روز رسانی دو مقاله بنیادی (what-is-quantum و wave-particle-duality) با تصاویر آموزشی فارسی و لوگوی qpedia.ir در گوشه. تصاویر فارسی با توضیح هایلایت زیر هر شکل. روی اسلاگ دقیق می افتد و محتوا را کامل جایگزین می کند.
 * Version: 2.0.0
 * Author: QPedia
 * Text Domain: qpedia-edu-batch1-fa
 */

if (!defined('ABSPATH')) { exit; }

define('QPEDU1_CPT', 'quantum_article');
define('QPEDU1_TAX', 'quantum_category');
define('QPEDU1_DIR', plugin_dir_path(__FILE__));
define('QPEDU1_URL', plugin_dir_url(__FILE__));

add_action('admin_menu', function () {
    add_management_page(
        'QPedia Edu Batch1 FA',
        'QPedia Edu Batch1 FA',
        'manage_options',
        'qpedia-edu-batch1-fa',
        'qpedu1_page'
    );
});

function qpedu1_load_manifest() {
    $f = QPEDU1_DIR . 'data/manifest.json';
    if (!file_exists($f)) {
        return new WP_Error('nofile', 'فایل data/manifest.json پیدا نشد.');
    }
    $d = json_decode(file_get_contents($f), true);
    if (!is_array($d) || empty($d['items'])) {
        return new WP_Error('badjson', 'ساختار JSON نامعتبر است.');
    }
    return $d;
}

function qpedu1_find_post_by_slug($slug) {
    $posts = get_posts(array(
        'name' => $slug,
        'post_type' => QPEDU1_CPT,
        'post_status' => array('publish','draft','pending','private','future'),
        'numberposts' => -1,
        'suppress_filters' => false,
    ));
    $exact = array();
    foreach ($posts as $p) {
        if ($p->post_name === $slug) {
            $exact[] = $p;
        }
    }
    if (empty($exact)) return null;
    // اولویت: منتشر شده، بعد بدون تصویر شاخص؟ ولی برای محتوا فرقی ندارد، اولین منتشر شده
    foreach ($exact as $p) {
        if ($p->post_status === 'publish') return $p;
    }
    return $exact[0];
}

function qpedu1_existing_attachment_by_name($filename) {
    global $wpdb;
    $id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
        '%' . $wpdb->esc_like($filename)
    ));
    return $id ? (int)$id : 0;
}

function qpedu1_sideload_image($path, $filename, $alt, $post_id) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $upload = wp_upload_bits($filename, null, file_get_contents($path));
    if (!empty($upload['error'])) {
        return new WP_Error('upload', $upload['error']);
    }
    $filetype = wp_check_filetype($upload['file'], null);
    $mime = !empty($filetype['type']) ? $filetype['type'] : 'image/webp';

    $attach_id = wp_insert_attachment(array(
        'guid' => $upload['url'],
        'post_mime_type' => $mime,
        'post_title' => pathinfo($filename, PATHINFO_FILENAME),
        'post_excerpt' => $alt,
        'post_content' => '',
        'post_status' => 'inherit',
    ), $upload['file'], $post_id);

    if (is_wp_error($attach_id) || !$attach_id) {
        return new WP_Error('attach', 'ثبت پیوست ناموفق بود.');
    }
    $meta = wp_generate_attachment_metadata($attach_id, $upload['file']);
    wp_update_attachment_metadata($attach_id, $meta);
    update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);

    return array('id' => (int)$attach_id, 'url' => $upload['url']);
}

function qpedu1_fix_scientist_links($content) {
    // لیست دانشمندان موجود را بگیر
    $scientists = get_posts(array(
        'post_type' => 'quantum_scientist',
        'post_status' => array('publish','draft','pending','private','future'),
        'numberposts' => -1,
        'fields' => 'ids',
    ));
    $slugs = array();
    foreach ($scientists as $sid) {
        $post = get_post($sid);
        if ($post) $slugs[$post->post_name] = true;
    }
    // الگو: href="/albert-einstein" یا href="/albert-einstein/" که اسلاگ دانشمند باشد و scientists/ نداشته باشد
    $content = preg_replace_callback(
        '#href=["\']/(?!scientists/|topic/|page/|search/|wp-|feed/|author/|category/|tag/|scientists)([a-z0-9-]{3,})/?["\']#i',
        function($m) use ($slugs) {
            $slug = $m[1];
            if (isset($slugs[$slug])) {
                return 'href="/scientists/' . $slug . '/"';
            }
            return $m[0];
        },
        $content
    );
    return $content;
}

function qpedu1_run($dry = false) {
    $manifest = qpedu1_load_manifest();
    if (is_wp_error($manifest)) return $manifest;

    $results = array();
    $counts = array('updated'=>0, 'missing'=>0, 'error'=>0, 'images'=>0);

    foreach ($manifest['items'] as $item) {
        $slug = sanitize_title($item['slug']);
        $file = basename($item['file']);
        $title = isset($item['title']) ? $item['title'] : $slug;

        $html_path = QPEDU1_DIR . 'articles/' . $file;
        if (!file_exists($html_path)) {
            $counts['error']++;
            $results[] = array('slug'=>$slug, 'status'=>'error', 'msg'=>"فایل HTML {$file} پیدا نشد");
            continue;
        }
        $html = file_get_contents($html_path);
        $html = trim($html);

        $post = qpedu1_find_post_by_slug($slug);
        if (!$post) {
            $counts['missing']++;
            $results[] = array('slug'=>$slug, 'status'=>'missing', 'msg'=>"مقاله با اسلاگ {$slug} در ".QPEDU1_CPT." پیدا نشد - اول باید مقاله وجود داشته باشد");
            continue;
        }

        if ($dry) {
            $results[] = array('slug'=>$slug, 'status'=>'dry', 'msg'=>"پیدا شد: ID {$post->ID} ({$post->post_status}) - آماده به روز رسانی");
            continue;
        }

        // آپلود تصاویر این مقاله
        $url_map = array(); // filename => url
        if (!empty($item['images']) && is_array($item['images'])) {
            foreach ($item['images'] as $img_file) {
                $img_file = basename($img_file);
                $img_path = QPEDU1_DIR . 'images/' . $img_file;
                if (!file_exists($img_path)) {
                    $results[] = array('slug'=>$slug, 'status'=>'img_missing', 'msg'=>"تصویر {$img_file} در پوشه images/ نیست");
                    continue;
                }
                $existing_id = qpedu1_existing_attachment_by_name($img_file);
                if ($existing_id) {
                    $url = wp_get_attachment_url($existing_id);
                    $url_map[$img_file] = $url;
                    $counts['images']++;
                } else {
                    $alt = $title . ' - ' . pathinfo($img_file, PATHINFO_FILENAME);
                    $res = qpedu1_sideload_image($img_path, $img_file, $alt, $post->ID);
                    if (is_wp_error($res)) {
                        $results[] = array('slug'=>$slug, 'status'=>'img_error', 'msg'=>"خطا در آپلود {$img_file}: ".$res->get_error_message());
                    } else {
                        $url_map[$img_file] = $res['url'];
                        $counts['images']++;
                    }
                }
            }
        }

        // جایگزینی URL های placeholder با URL واقعی
        // placeholder ها به شکل /wp-content/uploads/2026/09/<filename> هستند
        foreach ($url_map as $fname => $real_url) {
            $html = str_replace('/wp-content/uploads/2026/09/' . $fname, $real_url, $html);
            $html = str_replace($fname, $real_url, $html); // fallback
        }

        // اصلاح لینک دانشمندان اشتباه
        $html = qpedu1_fix_scientist_links($html);

        // به روز رسانی محتوا
        $updated = wp_update_post(array(
            'ID' => $post->ID,
            'post_content' => $html,
        ), true);

        if (is_wp_error($updated)) {
            $counts['error']++;
            $results[] = array('slug'=>$slug, 'status'=>'error', 'msg'=>"خطا در به روز رسانی ID {$post->ID}: ".$updated->get_error_message());
        } else {
            $counts['updated']++;
            $results[] = array('slug'=>$slug, 'status'=>'updated', 'msg'=>"به روز شد: ID {$post->ID} - {$title} - ".count($url_map)." تصویر");
        }
    }

    return array('counts'=>$counts, 'rows'=>$results, 'manifest'=>$manifest);
}

function qpedu1_page() {
    if (!current_user_can('manage_options')) return;

    $dry = isset($_POST['dry']);
    $run = isset($_POST['run']);

    echo '<div class="wrap" dir="rtl" style="font-family:Vazirmatn, Tahoma, sans-serif;">';
    echo '<h1>QPedia Edu Images Batch 1 FA - مقالات بنیادی با تصویر فارسی</h1>';
    echo '<p>این افزونه دو مقاله بنیادی <code>what-is-quantum</code> و <code>wave-particle-duality</code> را با تصاویر آموزشی فارسی و لوگوی <code>qpedia.ir</code> در گوشه (شیک و ظریف و ثابت) به روز می کند.</p>';
    echo '<p>ویژگی ها:</p><ul style="list-style:disc; margin-right:20px;">';
    echo '<li>متن فارسی مختصر داخل تصویر، توضیح کامل در باکس هایلایت زیر هر تصویر (<code>qp-callout</code>)</li>';
    echo '<li>استفاده از مثال های بومی (بازار تبریز، تهران-مشهد، خیابان ولیعصر)</li>';
    echo '<li>لوگو در گوشه پایین راست، ثابت، با پس زمینه سفید نیمه شفاف و متن qpedia.ir زیر لوگو</li>';
    echo '<li>اصلاح خودکار لینک دانشمندان از <code>/albert-einstein</code> به <code>/scientists/albert-einstein</code></li>';
    echo '<li>دقیقا روی اسلاگ موجود می افتد - محتوای جدید جایگزین محتوای قدیمی می شود</li>';
    echo '</ul>';

    if ($dry || $run) {
        check_admin_referer('qpedu1_action');
        $res = qpedu1_run($dry);
        if (is_wp_error($res)) {
            echo '<div class="notice notice-error"><p>خطا: '.esc_html($res->get_error_message()).'</p></div>';
        } else {
            $counts = $res['counts'];
            echo '<div class="notice notice-success"><p>نتیجه: '.$counts['updated'].' به روز شد، '.$counts['missing'].' پیدا نشد، '.$counts['error'].' خطا، '.$counts['images'].' تصویر آپلود/بازیافت شد.</p></div>';
            echo '<table class="widefat striped"><thead><tr><th>اسلاگ</th><th>وضعیت</th><th>پیام</th></tr></thead><tbody>';
            foreach ($res['rows'] as $r) {
                echo '<tr><td><code>'.esc_html($r['slug']).'</code></td><td>'.esc_html($r['status']).'</td><td>'.esc_html($r['msg']).'</td></tr>';
            }
            echo '</tbody></table>';
            if (!$dry) {
                echo '<div class="notice notice-info" style="margin-top:15px;"><p>تمام شد! حالا می توانید این افزونه را حذف کنید. تصاویر در کتابخانه رسانه باقی می مانند.</p></div>';
            }
        }
    }

    echo '<form method="post" style="margin-top:20px;">';
    wp_nonce_field('qpedu1_action');
    echo '<p><button type="submit" name="dry" class="button">پیش نمایش (Dry Run) - فقط چک کند مقالات وجود دارند یا نه</button> ';
    echo '<button type="submit" name="run" class="button button-primary">اجرای نهایی - به روز رسانی مقالات با تصاویر فارسی</button></p>';
    echo '</form>';

    // نمایش محتوای manifest
    $manifest = qpedu1_load_manifest();
    if (!is_wp_error($manifest)) {
        echo '<h2>محتویات بسته</h2><ul>';
        foreach ($manifest['items'] as $it) {
            echo '<li><strong>'.esc_html($it['title']).'</strong> (<code>'.esc_html($it['slug']).'</code>) - '.count($it['images']).' تصویر: '.esc_html(implode(', ', $it['images'])).'</li>';
        }
        echo '</ul>';
    }

    echo '</div>';
}
