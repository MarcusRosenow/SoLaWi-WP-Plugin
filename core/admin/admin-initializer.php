<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Für die Initialisierung des Admin Backends
 */
class SOLAWI_AdminInitializer {

	private SOLAWI_AbstractAdminPage $main_page;

	private bool $current_screen_action_triggered;

	function __construct(){
		$this->current_screen_action_triggered = false;
		$this->initMenu();
		$this->initAjax();
		add_action( 'admin_enqueue_scripts', array( $this, 'initAjaxJs' ) );
	}

	/**
	 * Initialisiert das Menü
	 */
	private function initMenu() {
		if ( is_admin() ) {
			add_action('admin_menu', array( $this, 'admin_seiten_hinzufuegen' ) );
			add_action('current_screen', array( $this, 'updateSubMenuTitle' ) );
		}
		add_action('admin_bar_menu', array( $this, 'admin_bar_link_hinzufuegen' ), 100 );
	}

	/**
	 * Initialisiert den Umgang mit gesendet Formulardaten
	 */
	private function initAjax() {
		add_action('wp_ajax_solawi_form_submit', [$this, 'savePostdata']);
        add_action('wp_ajax_nopriv_solawi_form_submit', [$this, 'savePostdata']);
	}

	/**
	 * Initialisiert den Umgang mit gesendet Formulardaten
	 */
	public function initAjaxJs() {
		wp_enqueue_script( 'solawi-ajax-form', SOLAWI_PLUGIN_URL . 'scripts/solawi_ajax.js' );
		wp_localize_script('solawi-ajax-form', 'SolawiAjaxForm', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('solawi_admin_form_nonce'),
        ]);
	}

	/**
	 * Speichert die Formulardaten, die per asynchronem JavaScript-Request vom Client gesendet wurden
	 */
	public function savePostdata() {
		check_ajax_referer('solawi_admin_form_nonce', 'security');
		
		// Fehler prüfen
		$ergebnis = new SOLAWI_SavePostdataResult();
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::BAUER, SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			$ergebnis->addErrorText( "Keine Schreibberechtigung!" );
		if ( !isset( $_POST["clazz"] ) )
			$ergebnis->addErrorText( "CLAZZ fehlt" );
		if ( !isset( $_POST["id"] ) )
			$ergebnis->addErrorText( "ID fehlt" );
		if ( $ergebnis->isError() ) {
            wp_send_json_error( $ergebnis->getJsonResponse() );
        } else {
			$id = $_POST["id"];
			$seite = new $_POST["clazz"];
			$ergebnis = $seite->savePostdata( $_POST["id"], $_POST );
			if ( $ergebnis->isError() )
    	        wp_send_json_error( $ergebnis->getJsonResponse() );
			else
				wp_send_json_success( $ergebnis->getJsonResponse() );
		}
	}

	/**
	 * Initialisiert die Admin Seite
	 */
	public function admin_seiten_hinzufuegen() {
        if ( !SOLAWI_hasRolle( SOLAWI_Rolle::BAUER, SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) ) {
			return;
		}
		$page0 = new SOLAWI_AdminPageVerteiltag2Mitbauer();
		$this->main_page = $page0;
		$otherPages = [ new SOLAWI_AdminPageVerteiltage() ];
		if ( SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) ) {
			$otherPages[] = new SOLAWI_AdminPageErnteanteile();
			$otherPages[] = new SOLAWI_AdminPageBieterverfahren();
			$otherPages[] = new SOLAWI_AdminPageNutzer();
			$otherPages[] = new SOLAWI_AdminPageVerteilstationen();
		}
		add_menu_page(
			$page0->getTitel(),			// Seitentitel
			'SoLaWi',					// Menüpunkt-Name
			'manage_options',			// Fähigkeit
			$page0->getSlug(),			// Slug
			array( $page0, 'print' ),	// Callback-Funktion
			'dashicons-feedback',		// Icon   https://azuliadesigns.com/wordpress/wordpress-dashicons-cheat-sheet/
			3							// Position
		);
		foreach ( $otherPages as $page )
			add_submenu_page(
				$page0->getSlug(),			// Parent-Slug
				$page->getTitel(),			// Seitentitel
				$page->getTitel(),			// Menüpunkt-Name
				'manage_options',			// Fähigkeit
				$page->getSlug(),			// Slug
				array( $page, 'print' )		// Callback-Funktion
		);
		$this->updateSubMenuTitle( null );
	}

	/**
	 * Ändert den Titel der Oberseite, wenn das Menü geöffnet ist.
	 * Normalerweise würde dort SoLaWi stehen, aber die Startseite sind ja die Ernteanteile
	 */
	public function updateSubMenuTitle( $screen ) {
		$this->current_screen_action_triggered |= ( $screen != null );
		if ( !isset( $this->main_page ) )
			return;
		// Dieser Code darf erst ausgeführt werden, wenn die action current_screen gefeuert
		// und das Menu schon geladen wurde
		global $submenu;
		$meinSubMenu = &$submenu[ $this->main_page->getSlug() ];
		foreach ($meinSubMenu as $key => $item) {
			if ( $item[2] === $this->main_page->getSlug() ) {
				$meinSubMenu[$key][0] = $this->main_page->getTitel();
				break;
			}
		}
	}

	public function admin_bar_link_hinzufuegen( $wp_admin_bar ) {
    	$url = admin_url( 'admin.php?page=solawi_admin_abholungjemitbauer' );
    	$wp_admin_bar->add_node([
	        'id'    => 'solawi',
    	    'title' => '&#128046; SoLaWi-Admin &#128046;',
        	'href'  => $url
    	]);
	}
}
