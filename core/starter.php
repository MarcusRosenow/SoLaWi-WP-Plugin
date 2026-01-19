<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;
		
/**
 * Main Solawi Class.
 * Wird bei jedem Abrufen der Seite aufgerufen
 */
final class SOLAWI_Starter {

	private static SOLAWI_Starter $instance;

	public static function instance() {
		if ( !isset( self::$instance ) ) {
			self::$instance = new SOLAWI_Starter();
			self::$instance->initFrontend();
			self::$instance->initBackend();
			do_action( 'SOLAWI/plugin_loaded' );
		}

		return self::$instance;
	}

	private function initBackend() {
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/admin-initializer.php';
		
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/pages/abstract-admin-page.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/pages/admin-page-ernteanteile.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/pages/admin-page-verteiltage.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/pages/admin-page-verteiltag2mitbauer.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/pages/admin-page-bieterverfahren.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/pages/admin-page-nutzer.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/pages/admin-page-verteilstationen.php';

		require_once SOLAWI_PLUGIN_DIR . 'core/admin/util/savePostdataResult.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/util/kalenderIntegration.php';

		require_once SOLAWI_PLUGIN_DIR . 'core/admin/print/abstract-admin-print.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/print/admin-print-bieterverfahren.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/print/admin-print-verteiltag.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/admin/print/admin-print-ernteanteile.php';

		new SOLAWI_AdminInitializer();
		add_action( 'admin_post_print_solawi', array( $this, 'adminPrint' ) );
	}

	public function adminPrint() {
        if ( !isset( $_GET['clazz'] ) ) {
            wp_die('Class falsch');
        }
		$printer = new $_GET['clazz'];
		if ( $printer instanceof SOLAWI_AbstractAdminPrint )
			$printer->print();
	}
	
	private function initFrontend() {
		require_once SOLAWI_PLUGIN_DIR . 'core/public/abstract-shortcode.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/public/init-shortcode.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/public/shortcode-bieterverfahren.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/public/shortcode-ernteanteile.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/public/shortcode-verteilgruppe.php';
		require_once SOLAWI_PLUGIN_DIR . 'core/public/shortcode-verteiltage.php';
		new SOLAWI_InitShortcode();
	}
}