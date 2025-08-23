<?php

# Check get_plugin_data function exist
if ( ! function_exists( 'get_plugin_data') ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

# Set Plugin path and url defines.
define( 'BOOK_INFO_URL', plugin_dir_url( dirname( __FILE__ ) ) );
define( 'BOOK_INFO_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
define( 'BOOK_INFO_LABEL', 'vlab-book-info' );
