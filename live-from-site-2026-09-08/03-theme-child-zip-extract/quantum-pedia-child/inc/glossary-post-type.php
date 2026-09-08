<?php

defined( 'ABSPATH' ) || exit;

add_action(
    'init',
    static function (): void {

        register_post_type(
            'glossary',
            [
                'labels' => [
                    'name'          => 'اصطلاح‌نامه',
                    'singular_name' => 'اصطلاح',
                    'add_new_item'  => 'افزودن اصطلاح',
                    'edit_item'     => 'ویرایش اصطلاح',
                ],

                'public'             => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'menu_icon'          => 'dashicons-book-alt',
                'supports'           => [
                    'title',
                    'editor',
                ],
                'show_in_rest'       => true,
                'rewrite'            => false,
                'query_var'          => false,
                'exclude_from_search'=> true,
            ]
        );
    }
);