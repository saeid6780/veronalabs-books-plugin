<?php
namespace BookInfoPlugin\Providers;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class BooksMenuServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function register(): void {}

    public function boot(): void
    {
        add_action( 'admin_menu', [ $this, 'register_books_menu' ] );
    }

    public function register_books_menu(): void
    {
        // books primary menu
        add_menu_page(
            __( 'Books', BOOK_INFO_LABEL ),
            __( 'Books', BOOK_INFO_LABEL ),
            'manage_options',
            'book_info_main',
            [ $this, 'render_books_list_page' ], // books list custom page
            'dashicons-book-alt',
            20
        );

        // submenu: view books list
        add_submenu_page(
            'book_info_main',
            __( 'All Books', BOOK_INFO_LABEL ),
            __( 'All Books', BOOK_INFO_LABEL ),
            'manage_options',
            'book_info_main',
            [ $this, 'render_books_list_page' ]
        );

        // submenu: add book
        add_submenu_page(
            'book_info_main',
            __( 'Add New Book', BOOK_INFO_LABEL ),
            __( 'Add New', BOOK_INFO_LABEL ),
            'manage_options',
            'post-new.php?post_type=book'
        );

        // submenu: managing Publisher tax
        add_submenu_page(
            'book_info_main',
            __( 'Publishers', BOOK_INFO_LABEL ),
            __( 'Publishers', BOOK_INFO_LABEL ),
            'manage_options',
            'edit-tags.php?taxonomy=publisher&post_type=book'
        );

        // submenu: managing Authors tax
        add_submenu_page(
            'book_info_main',
            __( 'Authors', BOOK_INFO_LABEL ),
            __( 'Authors', BOOK_INFO_LABEL ),
            'manage_options',
            'edit-tags.php?taxonomy=book_author&post_type=book'
        );
    }

    /**
     * Callback for books list page
     */
    public function render_books_list_page(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Books List', BOOK_INFO_LABEL ) . '</h1>';

        // will complete after books wp_list_class implementation
        echo '<p>' . esc_html__( 'Custom table of books (from book_info) will be rendered here.', BOOK_INFO_LABEL ) . '</p>';

        echo '</div>';
    }
}
