<?php
namespace BookInfoPlugin\Admin;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Books_List_Table extends \WP_List_Table {
    private $book_info_table;
    public $data;


    function __construct ()
    {
        global $wpdb;
        $this->book_info_table = $wpdb->prefix . 'books_info';
        //Set parent defaults
        parent::__construct( array(
            'singular' => __( 'Book', BOOK_INFO_LABEL ), //singular name of the listed records
            'plural'   => __( 'Books', BOOK_INFO_LABEL ), //plural name of the listed records
            'ajax'     => false        //does this table support ajax?
        ) );

        $this->data = $wpdb->get_results( "
                                        SELECT bi.id, p.ID, p.post_title, p.post_date, bi.isbn
            FROM {$this->book_info_table} bi
            INNER JOIN {$wpdb->posts} p ON p.ID = bi.post_id
            WHERE p.post_type = 'book' 
            ORDER BY bi.id DESC
            " , ARRAY_A );
    }

    function get_columns ()
    {
        return [
            'cb'         => '<input type="checkbox" />',
            'id'         => __( 'ID', BOOK_INFO_LABEL ),
            'title'      => __( 'Title', BOOK_INFO_LABEL ),
            'isbn'       => __( 'ISBN', BOOK_INFO_LABEL ),
            'publisher'  => __( 'Publisher', BOOK_INFO_LABEL ),
            'authors'    => __( 'Authors', BOOK_INFO_LABEL ),
            'date'       => __( 'Date', BOOK_INFO_LABEL ),
        ];
    }

    function prepare_items ()
    {
        /* First, lets decide how many records per page to show */
        $per_page = 20;

        /* REQUIRED. Now we need to define our column headers. This includes a complete
        * array of columns to be displayed (slugs & titles), a list of columns
        * to keep hidden, and a list of columns that are sortable. Each of these
        * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        /* REQUIRED. Finally, we build an array to be used by the class for column
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array( $columns, $hidden, $sortable );

        /* Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();

        /* Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example
         * package slightly different than one you might build on your own. In
         * this example, we'll be using array manipulation to sort and paginate
        * our data. In a real-world implementation, you will probably want to
        * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */

        $data = $this->data;

        /* This checks for sorting input and sorts the data in our array accordingly.
         *
         * In a real-world situation involving a database, you would probably want
         * to handle sorting by passing the 'orderby' and 'order' values directly
         * to a custom query. The returned data will be pre-sorted, and this array
         * sorting technique would be unnecessary.
         */
        function usort_reorder ( $a, $b )
        {
            $orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'id';//If no sort, default to id
            $order   = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'DESC';//If no order, default to desc
            $result  = strnatcmp( $a[ $orderby ], $b[ $orderby ] );//Determine sort order

            return ( $order === 'asc' ) ? $result : - $result;//Send final sort direction to usort
        }

        if ( ! empty( $data ) )
            usort( $data, [ $this, 'usort_reorder' ] );

        /* REQUIRED for pagination. Let\'s figure out what page the user is currently
         * looking at. We\'ll need this later, so you should always include it in
         * your own package classes.
         */
        $current_page = $this->get_pagenum();

        /* REQUIRED for pagination. Let\'s check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We\'ll need this later, so you should always include it
         * in your own package classes.
         */
        $total_items = count( $data );

        /* The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to
         */
        if ( is_array( $data ) )
            $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        /* REQUIRED. Now we can add our *sorted* data to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $data;

        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page, //WE have to determine how many items to show on a page
            'total_pages' => ceil( $total_items / $per_page )//WE have to calculate the total number of pages
        ) );
    }

    public function column_default ( $item, $column_name )
    {
        switch ( $column_name ) {
            case 'title':
                return $item[ 'post_title' ];
            case 'date':
                return esc_html(mysql2date(get_option('date_format'), $item['post_date']));
            case 'id':
                $actions = [
                    'view'   => sprintf( '<a href="%s" target="_blank">%s</a>', get_permalink( $item[ 'ID' ] ), __( 'View', BOOK_INFO_LABEL ) ),
                    'edit'   => sprintf( '<a href="%s">%s</a>', get_edit_post_link( $item[ 'ID' ] ), __( 'Edit', BOOK_INFO_LABEL ) ),
                    'delete' => sprintf( '<a onclick="return confirm(\'' . __( 'Are you sure you want to delete this book?', BOOK_INFO_LABEL ) .'\')" href="?page=%s&action=delete&id=%s">%s</a>', esc_attr( $_REQUEST[ 'page' ] ), $item[ 'ID' ], __( 'Delete', BOOK_INFO_LABEL ) ),
                ];
                return sprintf( '%1$s %2$s', $item[ 'id' ], $this->row_actions( $actions ) );
            case 'isbn':
                return $item[ $column_name ] ?? '';
            case 'publisher':
                $terms = wp_get_post_terms($item['ID'], 'publisher', ['fields' => 'names']);
                return !empty($terms) ? implode(', ', $terms) : '-';
            case 'authors':
                $terms = wp_get_post_terms($item['ID'], 'book_author', ['fields' => 'names']);
                return !empty($terms) ? implode(', ', $terms) : '-';

            default:
                return '';
        }
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    function get_sortable_columns ()
    {
        return [
            'title' => [ 'post_title', true ],
            'isbn'  => [ 'isbn', false ],
            'date'  => [ 'post_date', false ],
        ];
    }

    /**
     * Get value for checkbox column.
     *
     * @param object $item A row's data.
     *
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_cb ( $item )
    {
        return sprintf( '<label class="screen-reader-text" for="' . $item['id'] . '">' . sprintf( __( 'Select %s' ), $item['id'] ) . '</label>' . "<input type='checkbox' name='book_ids[]' id='{$item['id']}' value='{$item['ID']}' />" );
    }

    function get_bulk_actions (): array
    {
        $actions = array(
            'bulk_delete' => __( 'delete', BOOK_INFO_LABEL ),
        );

        return $actions;
    }

    function process_bulk_action ()
    {
        global $wpdb;
        $action = $this->current_action();
        error_log($action);

        if ( ! empty( $_REQUEST['s'] ) )
        {
            $search     = $_REQUEST['s'];
            $query      = "SELECT p.ID, p.post_title, p.post_date, p.post_content, bi.isbn, bi.ID id
            FROM {$this->book_info_table} bi
            INNER JOIN {$wpdb->posts} p ON p.ID = bi.post_id 
							WHERE p.post_title LIKE '%%{$search}%' OR bi.isbn LIKE '%%{$search}%' OR p.post_content LIKE '%%{$search}%' 
							ORDER BY bi.id DESC";

            $this->data = $wpdb->get_results( $query, ARRAY_A );
        }

        if ( isset( $_GET['id'] ) || isset( $_GET[ 'book_ids' ] ) )
        {

            switch ( $action )
            {
                case 'delete':
                    $id = $_GET['id'];
                    $wpdb->delete("{$this->book_info_table}", ['post_id' => $id], ['%d']);
                    error_log($wpdb->last_error);
                    wp_delete_post( $id, true);
                    $this->data = $wpdb->get_results( "SELECT bi.id, p.ID, p.post_title, p.post_date, bi.isbn
                                                                FROM {$this->book_info_table} bi
                                                                INNER JOIN {$wpdb->posts} p ON p.ID = bi.post_id
                                                                ORDER BY bi.id DESC", ARRAY_A );
                    echo '<div class="updated notice is-dismissible below-h2"><p>' . __( 'The desired book was deleted.', BOOK_INFO_LABEL ) . '</p></div>';
                    break;

                case 'bulk_delete':
                    $ids = isset($_REQUEST['book_ids']) ? array_map('intval', $_REQUEST['book_ids']) : [];

                    foreach ( $ids as $id )
                    {
                        $wpdb->delete("{$this->book_info_table}", ['post_id' => $id], ['%d']);
                        wp_delete_post( $id, true);
                    }

                    $this->data = $wpdb->get_results( "SELECT bi.id, p.ID, p.post_title, p.post_date, bi.isbn
                                                                FROM {$this->book_info_table} bi
                                                                INNER JOIN {$wpdb->posts} p ON p.ID = bi.post_id
                                                                ORDER BY bi.id DESC", ARRAY_A );
                    echo '<div class="updated notice is-dismissible below-h2"><p>' . __( 'Book(s) removed.', BOOK_INFO_LABEL ) . '</p></div>';
                    break;

                default:
                    break;
            }

            $this->data = $wpdb->get_results( "SELECT bi.id, p.ID, p.post_title, p.post_date, bi.isbn
                                                                FROM {$this->book_info_table} bi
                                                                INNER JOIN {$wpdb->posts} p ON p.ID = bi.post_id
                                                                ORDER BY bi.id DESC", ARRAY_A );
        }
        return;
    }
}
