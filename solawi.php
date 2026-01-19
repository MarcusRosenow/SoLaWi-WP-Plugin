<?php
/**
 * SoLaWi Verteiltage
 *
 * @package       SOLAWI
 * @author        Marcus Rosenow
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   SoLaWi
 * Plugin URI:    https://www.solawi-trebbow.de/
 * Description:   Verwaltung der SoLaWi
 * Version:       1.0.0
 * Author:        Marcus Rosenow
 * Author URI:    https://www.solawi-trebbow.de/
 * Text Domain:   solawi
 * Domain Path:   /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Plugin name
define( 'SOLAWI_NAME',			'SoLaWi Verteiltage' );

// Plugin version
define( 'SOLAWI_VERSION',		'1.0.0' );

// Plugin Root File
define( 'SOLAWI_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'SOLAWI_PLUGIN_BASE',	plugin_basename( SOLAWI_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'SOLAWI_PLUGIN_DIR',	plugin_dir_path( SOLAWI_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'SOLAWI_PLUGIN_URL',	plugin_dir_url( SOLAWI_PLUGIN_FILE ) );

require_once SOLAWI_PLUGIN_DIR . 'core/includes/basic_functions.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/entities/bereich.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/entities/bieterverfahren.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/entities/bieterverfahrenGebot.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/entities/mitbauer.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/entities/mitbauer_ernteanteil.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/entities/user2rolle.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/entities/verteilstation.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/entities/verteiltag.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/entities/verteiltag2mitbauer.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/entities/verteiltag2mitbauer.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/rolle.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/repository.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/widgets/abstract_widget.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/widgets/erfassunggebote.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/widgets/registerkarten.php';
require_once SOLAWI_PLUGIN_DIR . 'core/includes/widgets/kachellayout.php';
require_once SOLAWI_PLUGIN_DIR . 'core/user_initializer.php';

/**
 * Aktivieren des Plugins (einmalig)
 */
function SOLAWI_ACTIVATE() {
	require_once SOLAWI_PLUGIN_DIR . 'core/activator.php';
	new SOLAWI_Activator();
}
register_activation_hook( __FILE__, 'SOLAWI_ACTIVATE' );

/**
 * Deaktivieren des Plugins (einmalig)
 */
function SOLAWI_DEACTIVATE() {
	require_once SOLAWI_PLUGIN_DIR . 'core/deactivator.php';
	new SOLAWI_Deactivator();
}
register_deactivation_hook( __FILE__, 'SOLAWI_DEACTIVATE' );

/**
 * Löschen des Plugins (endgültig)
 */
function SOLAWI_UNINSTALL() {
	require_once SOLAWI_PLUGIN_DIR . 'core/uninstaller.php';
	new SOLAWI_Uninstaller();
}
register_uninstall_hook( __FILE__, 'SOLAWI_UNINSTALL' );

/**
 * Initialisiert einen neuen Nutzer
 */
function SOLAWI_INIT_USER( int $userId ) {
	SOLAWI_UserInitializer::initUser( $userId );
}
add_action( 'user_register', 'SOLAWI_INIT_USER' );

/**
 * Skripte und Styles einfügen
 */
function SOLAWI_ENQUEUE_SCRIPTS() {
	wp_enqueue_style( 'solawi-style', SOLAWI_PLUGIN_URL . "styles/solawi.css" );
	wp_enqueue_script( 'solawi-script', SOLAWI_PLUGIN_URL . 'scripts/solawi.js' );
}
add_action( 'wp_enqueue_scripts', 'SOLAWI_ENQUEUE_SCRIPTS' );
add_action( 'admin_enqueue_scripts', 'SOLAWI_ENQUEUE_SCRIPTS' );

/**
 * Load the main class for the core functionality
 */
require_once SOLAWI_PLUGIN_DIR . 'core/starter.php';
SOLAWI_Starter::instance();
