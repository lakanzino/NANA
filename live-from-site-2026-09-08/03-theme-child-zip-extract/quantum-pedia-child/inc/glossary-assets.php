<?php

defined( 'ABSPATH' ) || exit;

add_action(
    'wp_enqueue_scripts',
    static function (): void {

        if (
            ! is_singular(
                [
                    'quantum_article',
                    'quantum_scientist',
                ]
            )
        ) {
            return;
        }

        wp_enqueue_style(
            'quantum-glossary',
            get_stylesheet_directory_uri() . '/assets/css/quantum-glossary.css',
            [],
            '2.0.0'
        );

        wp_enqueue_script(
            'quantum-glossary',
            get_stylesheet_directory_uri() . '/assets/js/quantum-glossary.js',
            [],
            '2.0.0',
            true
        );
    }
);