<?php


class BookManager
{

    private $application;

    /**
     * ExamplePluginInit constructor.
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
            $this->application->addServiceProvider(RedirectServiceProvider::class);
            $this->application->addServiceProvider(DatabaseServiceProvider::class);
            $this->application->addServiceProvider(TemplatesServiceProvider::class);
            $this->application->addServiceProvider(LoggerServiceProvider::class);
            // Load your own service providers here...


            /**
             * Activation hooks
             */
            $this->application->onActivation(function () {
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
            },
            {
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
            },
            {
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
            },
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
            },
            {
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
        
            );



            /**
             * Deactivation hooks
             */
            $this->application->onDeactivation(function () {
                // Clear events, cache or something else
            });

            $this->application->boot(function (Plugin $plugin) {
                $plugin->loadPluginTextDomain();

                // load template
                $this->application->template('plugin-template.php', ['foo' => 'bar']);

                ///...

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
