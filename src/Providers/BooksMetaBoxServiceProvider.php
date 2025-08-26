<?php
namespace BookInfoPlugin\Providers;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use BookInfoPlugin\Support\IsbnValidator;

class BooksMetaBoxServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    protected $provides = [];

    public function provides( string $id ): bool {
        return in_array( $id, [ IsbnValidator::class ] );
    }

    public function register(): void
    {
        $this->getContainer()->add( IsbnValidator::class );
    }

    public function boot(): void
    {
        add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ]);
        add_action( 'save_post_book', [ $this, 'save_isbn'], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_ajax_check_isbn_unique', [ $this, 'check_isbn_unique' ] );

    }

    public function register_meta_box(): void
    {
        add_meta_box(
            'book_isbn_box',
            __( 'Book ISBN', BOOK_INFO_LABEL ),
            [ $this, 'render_meta_box' ],
            'book',
            'side',
            'default'
        );
    }

    public function render_meta_box(\WP_Post $post): void
    {
        wp_nonce_field('book_isbn_nonce', 'book_isbn_nonce');

        // Current value to display
        global $wpdb;
        $table = $wpdb->prefix . 'books_info';
        $isbn  = $wpdb->get_var( $wpdb->prepare( "SELECT isbn FROM {$table} WHERE post_id=%d", $post->ID ) );

        echo '<label for="book_isbn_field" class="screen-reader-text">' . esc_html__( 'ISBN', BOOK_INFO_LABEL ) . '</label>';
        echo '<input type="text" id="book_isbn_field" name="book_isbn_field" class="widefat" value="' . esc_attr( $isbn ) . '" />';
        echo '<p class="description">' . esc_html__( 'ISBN-10 or ISBN-13', BOOK_INFO_LABEL ) . '</p>';
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if ( ! in_array($hook, [ 'post.php','post-new.php' ], true) ) return;
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || $screen->post_type !== 'book' ) return;

        $msg_required = __( 'ISBN is required and must be valid.', BOOK_INFO_LABEL );

        global $post;

        // Only on the book post edit/create page
        if ( $hook === 'post.php' || $hook === 'post-new.php' ) {

            if ( isset( $post ) && $post->post_type === 'book' ) {
                $handle = 'book-isbn-validation';
                $src    = BOOK_INFO_URL . 'assets/admin/js/book-isbn-validate.js';
                wp_enqueue_script( $handle, $src, [], '1.0.0', true );

                wp_localize_script( $handle, 'BookISBNValidation', [
                    'ajax_url'      => admin_url( 'admin-ajax.php' ),
                    'nonce'         => wp_create_nonce( 'book_isbn_nonce' ),
                    'msg_required'  => __( 'The ISBN entered is not valid.', BOOK_INFO_LABEL ),
                    'msg_duplicate' => __( 'This ISBN is already registered.', BOOK_INFO_LABEL ),
                ]);
            }
        }
    }

    public function save_isbn( int $post_id, \WP_Post $post ): void
    {
        // Basic security
        if ( ! isset( $_POST[ 'book_isbn_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'book_isbn_nonce' ], 'book_isbn_nonce' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $raw  = isset( $_POST[ 'book_isbn_field' ] ) ? wp_unslash( $_POST[ 'book_isbn_field' ] ) : '';
        $isbn = sanitize_text_field( $raw );

        /** @var IsbnValidator $validator */
        $validator = $this->getContainer()->get(IsbnValidator::class);

        // Do not update the custom table if it is not valid.
        if ( $isbn === '' || ! $validator->isValid( $isbn ) ) {
            return;
        }

        global $wpdb;
        $table   = $wpdb->prefix . 'books_info';
        $exists  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$table} WHERE post_id=%d", $post_id ) );

        if ($exists) {
            $wpdb->update( $table, [ 'isbn' => $isbn ], [ 'ID' => $exists ], [ '%s' ], [ '%d' ]);
        } else {
            $wpdb->insert( $table, [ 'post_id' => $post_id, 'isbn' => $isbn ], [ '%d','%s' ] );
        }
    }

    private function json($value): string
    {
        return wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    function check_isbn_unique() {
        $nonce = isset( $_POST[ 'nonce' ] ) ? sanitize_text_field( $_POST[ 'nonce' ] ) : '';
        if ( ! wp_verify_nonce( $nonce, 'book_isbn_nonce' ) ) {
            wp_send_json( [
                'success' => false,
                'data'    => ['message' => __('Invalid security token.', BOOK_INFO_LABEL )]
            ] );
        }

        // Capability check
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json( [
                'success' => false,
                'data'    => [ 'message' => __( 'You are not allowed to perform this action.', BOOK_INFO_LABEL ) ]
            ]);
        }

        $isbn    = isset( $_POST[ 'isbn' ]) ? sanitize_text_field( $_POST[ 'isbn' ] ) : '';
        $post_id = isset( $_POST[ 'post_id' ] ) ? intval( $_POST[ 'post_id' ] ) : 0;

        if (!$isbn) {
            wp_send_json( [
                'success' => false,
                'data'    => [ 'message' => __( 'ISBN is required.', BOOK_INFO_LABEL ) ]
            ]);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'books_info';

        $existing = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE isbn=%s LIMIT 1", $isbn )
        );

        if ($existing) {
            error_log($post_id . $existing->post_id);
            // If updating the same post, it's ok
            if ( $post_id && intval( $existing->post_id ) === $post_id ) {
                wp_send_json( [ 'success' => true, 'data' => [ 'unique' => true ] ] );
            } else {
                wp_send_json( [
                    'success' => true,
                    'data'    => [
                        'unique'  => false,
                        'message' => __( 'This ISBN already exists for another book.', BOOK_INFO_LABEL )
                    ]
                ] );
            }
        } else {
            wp_send_json( [ 'success' => true, 'data' => [ 'unique' => true ] ] );
        }

        wp_die();
    }
}
