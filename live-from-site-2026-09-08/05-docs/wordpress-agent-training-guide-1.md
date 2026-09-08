# WordPress Development Reference Guide (for AI Coding Agent)

Purpose: internal knowledge base. Learn these principles once, apply them to 
every WordPress task — template building, layout fixes, content structuring, 
plugin cleanup, and performance. Optimize toward: **plugin-independent, 
code-driven, portable, fast, and unbreakable** front-end design.

---

## 1. Core Principle: Separate Content from Design

The #1 cause of "site went naked" incidents: design logic lives inside a 
plugin (page builder) instead of the theme. When the plugin is deleted, the 
design dies with it, because the plugin controlled the HTML/CSS markup 
directly in post content or via shortcodes.

**Rule:** Content = data (text, images, fields). Design = code in the theme. 
Never let a plugin be the only thing standing between raw data and a 
rendered page.

```
Post/CPT data (title, fields, images)
        ↓
Theme template file (fixed HTML structure + CSS classes)
        ↓
Rendered page (always works, plugin-agnostic)
```

---

## 2. WordPress Template Hierarchy (must memorize)

WordPress picks a template file automatically based on this priority order. 
Knowing this lets you target ANY content type precisely without plugins.

For a single post of custom post type `quantum_article`:
```
single-quantum_article-{slug}.php   (most specific)
single-quantum_article.php          (all posts of this type)
single.php                          (all singular posts, fallback)
index.php                           (final fallback, always exists)
```

For a taxonomy archive (e.g. category "scientists"):
```
taxonomy-{taxonomy}-{term}.php
taxonomy-{taxonomy}.php
archive.php
index.php
```

For custom post type archive:
```
archive-{post_type}.php
archive.php
index.php
```

**Practical use:** if you want quantum_article and quantum_scientist to look 
completely different — just create `single-quantum_article.php` and 
`single-quantum_scientist.php`. WordPress loads the right one automatically. 
No plugin, no conditional logic needed for basic cases.

---

## 3. Child Theme Structure (correct, safe setup)

```
child-theme/
├── style.css              → theme header + custom CSS
├── functions.php          → hooks, enqueues, CPT/ACF registration
├── header.php             → OPTIONAL override (only if changing header)
├── footer.php              → OPTIONAL override
├── single-{posttype}.php  → one per content type needing a unique layout
├── archive-{posttype}.php → listing page layout
├── template-parts/
│   ├── content-hero.php
│   ├── content-body.php
│   └── content-related.php
└── assets/
    └── css/custom-layouts.css
```

**functions.php must always include:**
```php
<?php
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', 
        [ 'parent-style' ] );
}, 10 );
```
Without this, child theme CSS may not load correctly, especially with block themes.

---

## 4. Template File Rule: ALWAYS Call header/footer

Every custom single/archive template MUST start and end with these two lines. 
This is the #1 fix for "no header, no footer" bugs:

```php
<?php get_header(); ?>

<!-- your fixed layout HTML here -->

<?php get_footer(); ?>
```

If a template is missing these calls (common when copied from a page-builder 
export), the page renders as a bare content block — exactly the "naked site" 
symptom.

---

## 5. Fixed Layout Pattern (the "approved design, forever" method)

Instead of letting each post carry its own design (via builder), define ONE 
canonical layout per post type in code. Content authors only fill fields — 
the layout is baked into the template and CSS, so it's literally impossible 
to "accidentally break" it.

**Step 1 — Register fields with ACF** (Advanced Custom Fields, free plugin — 
but note: ACF stores raw data in postmeta, so even if ACF itself is later 
disabled, the data ISN'T lost, only the admin UI for editing it. This is 
very different from a page builder, where removing the plugin destroys 
the actual rendered markup.)

Example field group for `quantum_scientist`:
- photo (image)
- birth_year (text/number)
- field_of_study (text)
- biography (textarea/WYSIWYG)
- achievements (repeater)

**Step 2 — Template outputs fields inside fixed HTML/CSS:**

```php
<?php get_header(); ?>

<article class="qpedia-scientist-layout">
    <div class="scientist-grid">
        <div class="scientist-photo">
            <?php $photo = get_field('photo'); ?>
            <?php if ( $photo ): ?>
                <img src="<?php echo esc_url($photo['url']); ?>" 
                     alt="<?php echo esc_attr($photo['alt']); ?>">
            <?php endif; ?>
        </div>
        <div class="scientist-info">
            <h1><?php the_title(); ?></h1>
            <p class="field"><?php the_field('field_of_study'); ?></p>
            <p class="birth"><?php the_field('birth_year'); ?></p>
            <div class="bio"><?php the_field('biography'); ?></div>
        </div>
    </div>
</article>

<?php get_footer(); ?>
```

**Step 3 — Lock the design in CSS (scoped class names, never inline styles):**

```css
.qpedia-scientist-layout .scientist-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
}
.qpedia-scientist-layout .scientist-photo img {
    border-radius: 8px;
    width: 100%;
}
```

Result: author writes biography text once, layout is 100% guaranteed 
identical across all scientist pages, forever — regardless of plugin state.

---

## 6. Column Layouts Without a Page Builder

For "scientific work laid out in columns" — this is pure CSS Grid/Flexbox, 
no plugin needed:

```css
.qpedia-article-layout .content-columns {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}
@media (max-width: 768px) {
    .qpedia-article-layout .content-columns { grid-template-columns: 1fr; }
}
```
Always add a mobile breakpoint — column layouts must collapse to 1 column 
on small screens.

---

## 7. Fallback Safety Net (never go "naked" again)

Always ship a generic `single.php` and `archive.php` that render header, 
footer, and minimal safe styling for ANY post type without a dedicated 
template. This guarantees the site is never fully broken, even for 
content types you forgot to build a template for.

```php
<?php get_header(); ?>
<main class="site-content">
    <?php while ( have_posts() ) : the_post(); ?>
        <article <?php post_class(); ?>>
            <h1><?php the_title(); ?></h1>
            <div class="entry-content"><?php the_content(); ?></div>
        </article>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
```

---

## 8. Diagnosing "Site Went Naked After Plugin Deletion"

Checklist, in order:
1. Open the affected post → Page Attributes / editor sidebar → check 
   "Template" dropdown. If it references a deleted plugin's template 
   (e.g. "Elementor Canvas", "Full Width"), switch to Default Template.
2. Check if post content contains shortcodes like `[vc_row]`, 
   `[elementor-template id=...]` — these become dead/broken markup once 
   the plugin is gone. Content must be rebuilt in Gutenberg blocks or ACF.
3. Verify header.php / footer.php exist in the active (child) theme and 
   contain `wp_head()` / `wp_footer()` calls respectively — many visual 
   issues (missing menu, missing scripts) trace back to a missing 
   `wp_head()`/`wp_footer()` call, not just get_header()/get_footer().
4. Check Appearance → Widgets and Menus — some builder plugins hijack 
   these too.

---

## 9. Performance & Reliability Best Practices

- **Never use inline `<style>` in templates** — always enqueue CSS files 
  via `wp_enqueue_style()`, this allows caching and avoids duplication.
- **Use `wp_enqueue_scripts` hook**, never hardcode `<script>` tags in 
  templates — ensures proper load order and dependency management.
- **Lazy-load images**: add `loading="lazy"` to `<img>` tags in templates.
- **Avoid plugin-heavy layouts** — every visual plugin (sliders, builders, 
  animated grids) adds JS/CSS payload and a point of failure. Prefer 
  native Gutenberg blocks + custom CSS for anything reusable.
- **Escape all dynamic output**: `esc_html()`, `esc_url()`, `esc_attr()` 
  — required for security (XSS prevention) and WordPress.org coding 
  standards compliance.
- **Cache-friendly structure**: static templates + ACF fields work well 
  with page caching plugins (LiteSpeed Cache, WP Rocket) since markup is 
  server-rendered PHP, not client-side JS from a builder.
- **Use `get_template_part()`** to break large templates into reusable 
  chunks (hero, body, related-posts) — reduces duplication, easier to 
  maintain across post types.

---

## 10. Golden Rules Summary (memorize these)

1. Design lives in the **theme** (code + CSS), never solely in a plugin.
2. One template file per post type = automatic, guaranteed consistency.
3. Content authors touch **fields only**, never raw layout/HTML.
4. Every custom template calls `get_header()` and `get_footer()`.
5. Always have a generic fallback template — site must never render 
   "naked," regardless of what plugin is added/removed.
6. Scope all CSS with unique class names per post type — avoids 
   conflicts, style loss, and specificity wars.
7. ACF/postmeta data survives plugin removal; page-builder-generated 
   markup does not. Prefer field-based content over builder shortcodes.
8. Test the resilience of every layout by deactivating all non-essential 
   plugins and confirming the site still renders fully.

---

## 11. When Given a New Task

Apply this checklist automatically:
- [ ] Which post type / template file does this affect?
- [ ] Does the fixed template already exist, or do I need to create it 
      per Section 5?
- [ ] Are get_header()/get_footer() present?
- [ ] Is styling scoped and in an enqueued CSS file (not inline, not 
      plugin-dependent)?
- [ ] Is there a fallback if this specific template is missing?
- [ ] Have I tested with the relevant plugin disabled to confirm no 
      breakage?
