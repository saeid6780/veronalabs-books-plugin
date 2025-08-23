<?php
namespace BookInfoPlugin\Providers;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class BooksSchemaServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * The provided array is a way to let the container
     * know that a service is provided by this service
     * provider. Every service that is registered via
     * this service provider must have an alias added
     * to this array or it will be ignored.
     *
     * @var array
     */
    protected $provides = [
        'booksschema',
    ];

    public function register() {
    }

    public function boot() {
        try {
            add_action( 'bookinfo/activate', [ $this, 'create_table' ] );
        } catch ( \Throwable $e ) {
            error_log( 'BooksSchemaServiceProvider boot error: ' . $e->getMessage() );
        }
    }

    public function create_table(): void {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $table_name      = $wpdb->prefix . "books_info";
        $charset_collate = $wpdb->get_charset_collate();
        $sql             = "CREATE TABLE IF NOT EXISTS $table_name (
            ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            isbn varchar(32) NOT NULL,
            PRIMARY KEY  (ID),
            KEY post_id (post_id)
        ) $charset_collate;";

        dbDelta( $sql );
    }
}
