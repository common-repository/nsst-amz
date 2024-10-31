<?php
class MyNSSTSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    public $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'nsst_amz_add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'nsst_amz_page_init' ) );
    }

    /**
     * Add options page
     */
    public function nsst_amz_add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'NSST AMAZON LINK', 
            'NSST AMAZON', 
            'manage_options', 
            'my-nsst-setting-admin', 
            array( $this, 'nsst_amz_create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function nsst_amz_create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'my_nsst_option_name' );
        ?>
        <div class="wrap">
            <h2>Cấu hình NSST </h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'my_option_group' );   
                do_settings_sections( 'my-nsst-setting-admin' );
                submit_button(); 
            ?>
            </form>
        <?php

            $this->nsst_amz_log();
        ?>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function nsst_amz_page_init()
    {        
        register_setting(
            'my_option_group', // Option group
            'my_nsst_option_name', // Option name
            array( $this, 'nsst_amz_sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            ' Settings', // Title
            array( $this, 'nsst_amz_print_section_info' ), // Callback
            'my-nsst-setting-admin' // Page
        );  

        add_settings_field(
            'nsst_public_key', // ID
            'Public KEY Amazon API', // Title 
            array( $this, 'nsst_amz_public_key' ), // Callback
            'my-nsst-setting-admin', // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            'nsst_private_key', 
            'Private KEY Amazon API', 
            array( $this, 'nsst_amz_private_key' ), 
            'my-nsst-setting-admin', 
            'setting_section_id'
        );      
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function nsst_amz_sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['nsst_public_key'] ) )
            $new_input['nsst_public_key'] = sanitize_text_field( $input['nsst_public_key'] );

        if( isset( $input['nsst_private_key'] ) )
            $new_input['nsst_private_key'] = sanitize_text_field( $input['nsst_private_key'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function nsst_amz_print_section_info()
    {
        print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function nsst_amz_public_key()
    {
        printf(
            '<input type="text" id="nsst_public_key" name="my_nsst_option_name[nsst_public_key]" value="%s" />',
            isset( $this->options['nsst_public_key'] ) ? esc_attr( $this->options['nsst_public_key']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function nsst_amz_private_key()
    {
        printf(
            '<input type="text" id="nsst_private_key" name="my_nsst_option_name[nsst_private_key]" value="%s" />',
            isset( $this->options['nsst_private_key'] ) ? esc_attr( $this->options['nsst_private_key']) : ''
        );
    }

    public function nsst_amz_log() {
        global $wpdb;

        $table_name = $wpdb->prefix . "nsstlink_log"; 
        $data_asin_db = $wpdb->get_results("SELECT * FROM " . $table_name . ' ORDER BY ID DESC', ARRAY_A);
        echo '<h2>LOG</h2>';
        foreach($data_asin_db as $value) {
            echo ($value['time'] . ' - ' . $value['log'] . '<br/>');
        }
    }
}

if( is_admin() )
    $my_nsst_settings_page = new MyNSSTSettingsPage();