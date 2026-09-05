<?php

defined( 'ABSPATH' ) || exit;

add_filter(
    'the_content',
    static function ( string $content ): string {

        if (
            is_admin()
            || ! is_singular(
                [
                    'quantum_article',
                    'quantum_scientist',
                ]
            )
        ) {
            return $content;
        }

        $terms = quantum_get_glossary_terms();

        if ( empty( $terms ) ) {
            return $content;
        }

        foreach ( $terms as $item ) {

            $term = trim( $item['term'] );

            if ( '' === $term ) {
                continue;
            }

            $pattern =
                '/(?<![\p{L}\p{N}])'
                . preg_quote( $term, '/' )
                . '(?![\p{L}\p{N}])/u';

            $replacement =
                '<span class="quantum-glossary-term" data-definition="' .
                esc_attr( $item['definition'] ) .
                '">' .
                esc_html( $term ) .
                '</span>';

            $content = preg_replace(
                $pattern,
                $replacement,
                $content,
                1
            );
        }

        return $content;
    },
    20
);
