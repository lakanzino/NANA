<?php

defined( 'ABSPATH' ) || exit;

function quantum_get_glossary_terms(): array {

    $cached = get_transient(
        'quantum_glossary_terms'
    );

    if ( false !== $cached ) {
        return $cached;
    }

    $posts = get_posts(
        [
            'post_type'      => 'glossary',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'DESC',
        ]
    );

    $terms = [];

    foreach ( $posts as $post ) {

        $terms[] = [
            'term' => wp_strip_all_tags(
                $post->post_title
            ),

            'definition' => wp_strip_all_tags(
                $post->post_content
            ),
        ];
    }

    usort(
        $terms,
        static fn( $a, $b ) =>
        mb_strlen( $b['term'] ) <=> mb_strlen( $a['term'] )
    );

    set_transient(
        'quantum_glossary_terms',
        $terms,
        DAY_IN_SECONDS
    );

    return $terms;
}

add_action(
    'save_post_glossary',
    static function (): void {

        delete_transient(
            'quantum_glossary_terms'
        );
    }
);