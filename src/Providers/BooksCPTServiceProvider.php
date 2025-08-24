<?php
namespace BookInfoPlugin\Providers;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class BooksCPTServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function register(): void {}

    public function boot(): void
    {
        add_action( 'init', [ $this, 'register_post_type_and_taxonomies' ]);
    }

    public function register_post_type_and_taxonomies(): void
    {
        // Labels for CPT: book
        $book_labels = [
            'name'                  => __( 'Books', BOOK_INFO_LABEL ),
            'singular_name'         => __( 'Book', BOOK_INFO_LABEL ),
            'menu_name'             => __( 'Books', BOOK_INFO_LABEL ),
            'name_admin_bar'        => __( 'Book', BOOK_INFO_LABEL ),
            'add_new'               => __( 'Add New', BOOK_INFO_LABEL ),
            'add_new_item'          => __( 'Add New Book', BOOK_INFO_LABEL ),
            'edit_item'             => __( 'Edit Book', BOOK_INFO_LABEL ),
            'new_item'              => __( 'New Book', BOOK_INFO_LABEL ),
            'view_item'             => __( 'View Book', BOOK_INFO_LABEL ),
            'view_items'            => __( 'View Books', BOOK_INFO_LABEL ),
            'search_items'          => __( 'Search Books', BOOK_INFO_LABEL ),
            'not_found'             => __( 'No books found.', BOOK_INFO_LABEL ),
            'not_found_in_trash'    => __( 'No books found in Trash.', BOOK_INFO_LABEL ),
            'all_items'             => __( 'All Books', BOOK_INFO_LABEL ),
            'archives'              => __( 'Book Archives', BOOK_INFO_LABEL ),
            'attributes'            => __( 'Book Attributes', BOOK_INFO_LABEL ),
            'insert_into_item'      => __( 'Insert into book', BOOK_INFO_LABEL ),
            'uploaded_to_this_item' => __( 'Uploaded to this book', BOOK_INFO_LABEL ),
        ];

        // CPT: book
        register_post_type( 'book', [
            'labels'              => $book_labels,
            'public'              => true,
            'show_ui'             => true, // hide default UI (we’ll replace with WP_List_Table)
            'show_in_menu'        => false,  // menu will be registered manually later
            'supports'            => [ 'title', 'editor', 'thumbnail' ],
            'has_archive'         => true,
            'show_in_rest'        => true,
        ]);

        // Labels for Taxonomy: publisher
        $publisher_labels = [
            'name'              => __( 'Publishers', BOOK_INFO_LABEL ),
            'singular_name'     => __( 'Publisher', BOOK_INFO_LABEL ),
            'search_items'      => __( 'Search Publishers', BOOK_INFO_LABEL ),
            'all_items'         => __( 'All Publishers', BOOK_INFO_LABEL ),
            'edit_item'         => __( 'Edit Publisher', BOOK_INFO_LABEL ),
            'update_item'       => __( 'Update Publisher', BOOK_INFO_LABEL ),
            'add_new_item'      => __( 'Add New Publisher', BOOK_INFO_LABEL ),
            'new_item_name'     => __( 'New Publisher Name', BOOK_INFO_LABEL ),
            'menu_name'         => __( 'Publishers', BOOK_INFO_LABEL ),
        ];

        register_taxonomy( 'publisher', 'book', [
            'labels'       => $publisher_labels,
            'public'       => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ] );

        // Labels for Taxonomy: book_author
        $author_labels = [
            'name'              => __( 'Authors', BOOK_INFO_LABEL ),
            'singular_name'     => __( 'Author', BOOK_INFO_LABEL ),
            'search_items'      => __( 'Search Authors', BOOK_INFO_LABEL ),
            'all_items'         => __( 'All Authors', BOOK_INFO_LABEL ),
            'edit_item'         => __( 'Edit Author', BOOK_INFO_LABEL ),
            'update_item'       => __( 'Update Author', BOOK_INFO_LABEL ),
            'add_new_item'      => __( 'Add New Author', BOOK_INFO_LABEL ),
            'new_item_name'     => __( 'New Author Name', BOOK_INFO_LABEL ),
            'menu_name'         => __( 'Authors', BOOK_INFO_LABEL ),
        ];

        register_taxonomy( 'book_author', 'book', [
            'labels'       => $author_labels,
            'public'       => true,
            'hierarchical' => false,
            'show_in_rest' => true,
        ] );
    }
}
