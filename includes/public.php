<?php
class BookManager
{
    private static $instance;

    public function __construct()
    {
        // Register activation and deactivation hooks.
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Load the text domain for internationalization.
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Register custom post type and taxonomies.
        add_action('init', array($this, 'register_custom_post_type'));
        add_action('init', array($this, 'register_taxonomies'));

        // Add meta box for the custom post type.
        add_action('add_meta_boxes', array($this, 'add_meta_box'));

        // Save meta box data.
        add_action('save_post', array($this, 'save_meta_box_data'));

        // Add admin menu page for displaying the table.
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Enqueue admin scripts and styles.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts_styles'));
    }

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function activate()
    {
        // Create the books_info table on plugin activation.
        global $wpdb;
        $table_name = $wpdb->prefix . 'books_info';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            isbn varchar(255) NOT NULL,
            PRIMARY KEY (ID)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function deactivate()
    {
        // Perform cleanup tasks on plugin deactivation.
        // (e.g. delete the books_info table if needed)
    }

    public function load_textdomain()
    {
        // Load the plugin's text domain for internationalization.
        load_plugin_textdomain('bookmanager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function register_custom_post_type()
    {
        // Register the 'Book' custom post type.
        $labels = array(
            'name' => __('Books', 'bookmanager'),
            'singular_name' => __('Book', 'bookmanager'),
            // Add more labels as needed.
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            // Add more arguments as needed.
        );

        register_post_type('book', $args);
    }

    public function register_taxonomies()
    {
        // Register the 'Publisher' and 'Authors' taxonomies for the 'Book' post type.
        $args = array(
            'label' => __('Publisher', 'bookmanager'),
            'rewrite' => array('slug' => 'publisher'),
            // Add more arguments as needed.
        );

        register_taxonomy('publisher', 'book', $args);

        $args = array(
            'label' => __('Authors', 'bookmanager'),
            'rewrite' => array('slug' => 'authors'),
            // Add more arguments as needed.
        );

        register_taxonomy('authors', 'book', $args);
    }

    public function add_meta_box($post_type)
    {
        // Add meta box for the 'Book' post type.
        if ($post_type === 'book') {
            add_meta_box(
                'isbn_meta_box',
                __('ISBN Number', 'bookmanager'),
                array($this, 'render_meta_box'),
                $post_type,
                'normal',
                'default'
            );
        }
    }

    public function render_meta_box($post)
    {
        // Render the meta box content.
        $isbn = get_post_meta($post->ID, 'isbn', true);

        ?>
        <label for="isbn"><?php _e('ISBN Number:', 'bookmanager'); ?></label>
        <input type="text" id="isbn" name="isbn" value="<?php echo esc_attr($isbn); ?>">
        <?php
    }

    public function save_meta_box_data($post_id)
    {
        // Save the meta box data when the post is saved.
        if (isset($_POST['isbn'])) {
            $isbn = sanitize_text_field($_POST['isbn']);
            update_post_meta($post_id, 'isbn', $isbn);

            // Save the ISBN to the books_info table.
            global $wpdb;
            $table_name = $wpdb->prefix . 'books_info';

            $wpdb->insert($table_name, array(
                'post_id' => $post_id,
                'isbn' => $isbn,
            ));
        }
    }

    public function add_admin_menu()
    {
        // Add admin menu page to display the books_info table.
        add_menu_page(
            __('Books Info', 'bookmanager'),
            __('Books Info', 'bookmanager'),
            'manage_options',
            'books_info',
            array($this, 'display_books_info_table'),
            'dashicons-book',
            25
        );
    }

    public function display_books_info_table() {
    // Display the contents of the books_info table.
    // Consider using the WP_List_Table class for displaying the table.
    global $wpdb;
    $table_name = $wpdb->prefix . 'books_info';
    $query = "
        SELECT b.post_id, b.isbn, p.post_title, GROUP_CONCAT(DISTINCT pub.name ORDER BY pub.name ASC SEPARATOR ', ') AS publishers, GROUP_CONCAT(DISTINCT aut.name ORDER BY aut.name ASC SEPARATOR ', ') AS authors
        FROM $table_name AS b
        INNER JOIN $wpdb->posts AS p ON b.post_id = p.ID
        LEFT JOIN $wpdb->term_relationships AS tr_pub ON b.post_id = tr_pub.object_id
        LEFT JOIN $wpdb->term_relationships AS tr_aut ON b.post_id = tr_aut.object_id
        LEFT JOIN $wpdb->term_taxonomy AS tt_pub ON tr_pub.term_taxonomy_id = tt_pub.term_taxonomy_id AND tt_pub.taxonomy = 'publisher'
        LEFT JOIN $wpdb->term_taxonomy AS tt_aut ON tr_aut.term_taxonomy_id = tt_aut.term_taxonomy_id AND tt_aut.taxonomy = 'authors'
        LEFT JOIN $wpdb->terms AS pub ON tt_pub.term_id = pub.term_id
        LEFT JOIN $wpdb->terms AS aut ON tt_aut.term_id = aut.term_id
        GROUP BY b.post_id";
    $results = $wpdb->get_results($query);
    
    echo '<div class="wrap">';
    echo '<h1>' . __('Books Info', 'bookmanager') . '</h1>';
    
    if ($results) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Post ID', 'bookmanager') . '</th>';
        echo '<th>' . __('Title', 'bookmanager') . '</th>';
        echo '<th>' . __('ISBN', 'bookmanager') . '</th>';
        echo '<th>' . __('Publishers', 'bookmanager') . '</th>';
        echo '<th>' . __('Authors', 'bookmanager') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($results as $result) {
            echo '<tr>';
            echo '<td>' . $result->post_id . '</td>';
            echo '<td>' . $result->post_title . '</td>';
            echo '<td>' . $result->isbn . '</td>';
            echo '<td>' . $result->publishers . '</td>';
            echo '<td>' . $result->authors . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>' . __('No books found.', 'bookmanager') . '</p>';
    }
    
    echo '</div>';
}
    

    public function enqueue_admin_scripts_styles($hook)
    {
        // Enqueue admin scripts and styles only on the plugin's admin pages.
        if ($hook === 'toplevel_page_books_info') {
            // Enqueue admin scripts and styles here.
        }
    }

    public function run()
    {
        // Plugin initialization.
        // Add any additional setup tasks here.
    }
}

// Initialize the plugin.
BookManager::get_instance();
