<?php
/**
 * Plugin Name:     Book Info
 * Plugin URI:      https://www.veronalabs.com
 * Plugin Prefix:   VBI
 * Description:     Book Info WordPress Plugin Based on Rabbit Framework!
 * Author:          saeid6780
 * Author URI:      https://veronalabs.com
 * Text Domain:     vlab-book-info
 * Domain Path:     /languages
 * Version:         1.0
 */


use Rabbit\Application;
use Rabbit\Redirects\RedirectServiceProvider;
use Rabbit\Database\DatabaseServiceProvider;
use Rabbit\Logger\LoggerServiceProvider;
use Rabbit\Plugin;
use Rabbit\Redirects\AdminNotice;
use Rabbit\Templates\TemplatesServiceProvider;
use Rabbit\Utils\Singleton;
use League\Container\Container;
use BookInfoPlugin\Providers\BooksSchemaServiceProvider;
use BookInfoPlugin\Providers\BooksCPTServiceProvider;
use BookInfoPlugin\Providers\BooksMenuServiceProvider;
use BookInfoPlugin\Providers\BooksMetaBoxServiceProvider;

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require dirname(__FILE__) . '/vendor/autoload.php';
}
require_once plugin_dir_path(__FILE__) . 'includes' . '/defines.php';
/**
 * Class BookInfoPluginInit
 * @package BookInfoPluginInit
 */
class BookInfoPluginInit extends Singleton
{
    /**
     * @var Container
     */
    private $application;

    /**
     * BookInfoPluginInit constructor.
     */
    public function __construct()
    {
        $this->application = Application::get()->loadPlugin(__DIR__, __FILE__, 'config');
        $this->init();
    }

    public function init()
    {
        try {
            /**
             * Load service providers
             */
            $this->application->addServiceProvider( RedirectServiceProvider::class );
            $this->application->addServiceProvider( DatabaseServiceProvider::class );
            $this->application->addServiceProvider( TemplatesServiceProvider::class );
            $this->application->addServiceProvider( LoggerServiceProvider::class );
            // Load your own service providers here...
            $this->application->addServiceProvider( BooksSchemaServiceProvider::class );
            $this->application->addServiceProvider( BooksCPTServiceProvider::class );
            $this->application->addServiceProvider( BooksMenuServiceProvider::class );
            $this->application->addServiceProvider( BooksMetaBoxServiceProvider::class );

            /**
             * Activation hooks
             */
            $this->application->onActivation(function () {
                // Create tables or something else
                do_action( 'bookinfo/activate' );

            });

            /**
             * Deactivation hooks
             */
            $this->application->onDeactivation(function () {
                do_action( 'bookinfo/deactivate' ); // Clear events, cache or something else
            });

            $this->application->boot(function ( Plugin $plugin ) {
                $plugin->loadPluginTextDomain();

                // load template
                //$this->application->template('plugin-template.php', ['foo' => 'bar']);

            });

        } catch (Exception $e) {
            /**
             * Print the exception message to admin notice area
             */
            add_action('admin_notices', function () use ($e) {
                AdminNotice::permanent(['type' => 'error', 'message' => $e->getMessage()]);
            });

            /**
             * Log the exception to file
             */
            add_action('init', function () use ($e) {
                if ($this->application->has('logger')) {
                    $this->application->get('logger')->warning($e->getMessage());
                }
            });
        }
    }

    /**
     * @return Container
     */
    public function getApplication()
    {
        return $this->application;
    }
}

/**
 * Returns the main instance of BookInfoPluginInit.
 *
 * @return BookInfoPluginInit
 */
function bookInfoPlugin()
{
    return BookInfoPluginInit::get();
}

bookInfoPlugin();