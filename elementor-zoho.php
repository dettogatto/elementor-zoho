<?php

/**
* Plugin Name: Elementor-Zoho
* Description: Gives Elementor forms connectivity with Zoho
* Plugin URI:  https://cosmo.cat
* Version:     0.1
* Author:      Nicola Cavallazzi
* Author URI:  https://cosmo.cat/
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);




final class Elementor_Zoho_Integration {

    private $zoho;

    function __construct(){
        // Load form actions
        add_action( 'elementor_pro/init', array( $this, 'load_form_actions' ) );
        // admin page
        add_action( 'admin_menu', array( $this, 'setup_menu' ) );
        // add ajax hook for tokens registration
        add_action( 'wp_ajax_setup_zoho', array( $this, 'ajax_setup_zoho' ) );
        // Add Settings
        add_action( 'admin_init', array( $this, 'setup_init' ) );
        // Instantiate Zoho handler
        require_once( __DIR__ . '/zoho-connection.php' );
        $this->zoho = new Zoho_Connection_By_Gatto();
    }


    public function load_form_actions(){
        // Here its safe to include our action class file
        include_once( __DIR__ . '/form-actions/zoho/action-after-submit.php' );

        // // Instantiate the action class
        $zoho_action = new Zoho_Action_After_Submit();

        // Register the action with form widget
        \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $zoho_action->get_name(), $zoho_action );
    }

    public function setup_menu(){
        add_options_page( 'Elementor-Zoho', 'Elementor-Zoho', 'manage_options', 'elementor_zoho', array($this, 'the_page') );
    }

    public function the_page(){
        ?>
        <br><br>
        <?php
        if($this->zoho->check_connection()){
            echo("<h3>Connessione a Zoho riuscita!</h3>");
        } else {
            ?>
            <p>
                Per collegarti alla API di Zoho vai a questo indirizzo: <a target="_blank" href="https://accounts.zoho.eu/developerconsole">https://accounts.zoho.eu/developerconsole</a>
                <br><br>
                Registra un utente usando:<br>
                <strong>Nome Client</strong>: a piacere<br>
                <strong>Dominio client</strong>: <?php echo($this->get_host()); ?><br>
                <strong>URI di reindirizzamento autorizzato</strong>: <?php echo($this->zoho->get_redirect_uri()); ?>
            </p>
            <p>
                Poi inserisci
            </p>
        <?php } ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('elementor_zoho');
            do_settings_sections('elementor_zoho');
            submit_button();
            ?>
        </form>
        <?php
        if(!$this->zoho->check_connection() && get_option("elementor_zoho_client_id") && get_option("elementor_zoho_client_id") != ""){
            $link = 'https://accounts.zoho.eu/oauth/v2/auth?scope=ZohoCRM.users.ALL,ZohoCRM.modules.ALL,ZohoCRM.org.ALL,ZohoCRM.bulk.ALL&client_id='.get_option("elementor_zoho_client_id").'&response_type=code&access_type=offline&redirect_uri='.$this->zoho->get_redirect_uri();
            ?>
            <p>
                Clicca questo link per procedere con l'attivazione:<br>
                <a href="<?php echo($link); ?>"><?php echo($link); ?></a>
            </p>
            <?php
        }
        $this->zoho->list_module_fields();
    }

    private function get_host(){
        $url_parts = parse_url( get_site_url() );
        if ( $url_parts && isset( $url_parts['host'] ) ) {
            return $url_parts['host'];
        }
        return null;
    }


    public function ajax_setup_zoho(){
        $grant = $_GET["code"];
        if($this->zoho->get_tokens_from_grant($grant)){
            echo("L'attivazione è andata a buon fine!<br><br>");
            echo('<a href="'.get_site_url().'/wp-admin/options-general.php?page=elementor_zoho'.'">Torna alle impostazioni.</a>');
        }
        wp_die();
    }

    public function setup_init(){
        add_settings_section(
            "sezione-zoho",
            "",
            null,
            'elementor_zoho'
        );

        register_setting('elementor_zoho', 'elementor_zoho_client_id');
        add_settings_field(
            'elementor_zoho_client_id',
            'ID client: ',
            array( $this, 'field_callback' ),
            'elementor_zoho', "sezione-zoho",
            array("id" => 'elementor_zoho_client_id')
        );

        register_setting('elementor_zoho', 'elementor_zoho_client_secret');
        add_settings_field(
            'elementor_zoho_client_secret',
            'Segreto client: ',
            array( $this, 'field_callback' ),
            'elementor_zoho', "sezione-zoho",
            array("id" => 'elementor_zoho_client_secret')
        );

        register_setting('elementor_zoho', 'elementor_zoho_client_email');
        add_settings_field(
            'elementor_zoho_client_email',
            'Email Client: ',
            array( $this, 'field_callback' ),
            'elementor_zoho', "sezione-zoho",
            array("id" => 'elementor_zoho_client_email', "p" => "Questa è la mail che usi per accedere a Zoho CRM")
        );
    }

    public function field_callback ( $arguments ) {

        $id = $arguments["id"];
        echo '<input name="' . $id . '" id="' . $id . '" type="text" value="' .get_option($id). '" />';
        if(isset($arguments["p"])){
            echo('<p>' . $arguments["p"] . '</p>');
        }
    }



}

$elementor_zoho = new Elementor_Zoho_Integration();
