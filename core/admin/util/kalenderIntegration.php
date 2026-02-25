<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Das Ergebnis des Speicherns der Daten von einem AJAX-Request
 */
class SOLAWI_KalenderIntegration {

	private static $instance;

	private WP_Term $term;

	/**
	 * die einzige Instanz
	 */
	public static function instance() : SOLAWI_KalenderIntegration {
		if ( !isset( self::$instance ) )
			self::$instance = new SOLAWI_KalenderIntegration();
		return self::$instance;
	}

	/**
	 * Privater Konstruktor.
	 * Legt gleich die notwendige Veranstaltungskategorie an.
	 */
    private function __construct() {
		if ( $this->isEventsCalendarAktiviert() ) {
			if ( !term_exists( "verteiltage", Tribe__Events__Main::TAXONOMY ) )
				wp_insert_term( "Verteiltage", Tribe__Events__Main::TAXONOMY,	[ "slug" => "verteiltage" ] );
			$this->term = get_term_by( 'slug', 'verteiltage', Tribe__Events__Main::TAXONOMY );
		}
    }

	/**
	 * Prüft, ob "The Events Calendar installiert ist
	 */
	private function isEventsCalendarAktiviert() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		return is_plugin_active( 'the-events-calendar/the-events-calendar.php' );
	}

	/**
	 * Aktualisiert den Kalender
	 * @return Gibt null bei Erfolg zurück, ansonsten die Fehlermeldung als String
	 */
    public function updateKalender( SOLAWI_Verteiltag $tag ) : null|string {
		if ( !$this->isEventsCalendarAktiviert() )
			return "'The Events Calendar' ist nicht installiert";

		$post_name   = 'verteilung' . $tag->getId();
		$post_data = [
			'title'        => "Verteiltag für " . SOLAWI_arrayToString( $tag->getVerteilungen(), "getSimpleName" ),
			'description'  => $this->getKalenderEintrag( $tag ),
			'status'       => 'publish',
			'author'       => get_current_user_id(),
			'slug'         => $post_name,
			'timezone'     => 'Europe/Berlin',
			'start_date'   => SOLAWI_formatDatum( $tag->getDatum(), true ) . $tag->getStartVerteilung() . ":00",
			'end_date'     => SOLAWI_formatDatum( $tag->getDatum(), true ) . $tag->getEndeVerteilung() . ":00",
			'category'     => [ $this->term->id ]
		];

		$vorhandenerPost = tribe_events()->where( 'name', $post_name )->per_page( -1 );
		$isPostVorhanden = $vorhandenerPost->count() > 0;
		$ergebnis = null;
		$mussPostAnlegen = count( $tag->getVerteilungen() ) > 0;
		if ( !$mussPostAnlegen && $isPostVorhanden ) {
			// Beitrag löschen
			$ergebnis = $vorhandenerPost->delete();
		} else if ( $mussPostAnlegen && $isPostVorhanden ) {
			// Beitrag aktualisieren
			$ergebnis = $vorhandenerPost->set_args( $post_data )->save();
		} else if ( $mussPostAnlegen && !$isPostVorhanden ) {
			// Beitrag erstellen
			$ergebnis = tribe_events()->set_args( $post_data )->create();
		}

		return is_wp_error( $ergebnis ) ? $ergebnis->get_error_message() : null;
	}

	/**
	 * Gibt den Inhalt für den Kalendereintrag zurück
	 */
	private function getKalenderEintrag( SOLAWI_Verteiltag $tag ) : string {
		return '<!-- wp:heading -->
<!-- /wp:heading -->
<!-- wp:tribe/event-datetime /-->

<!-- wp:paragraph {"placeholder":"Beschreibung hinzufügen..."} -->
<!-- wp:shortcode -->
[solawiform page=verteiltage verteiltagid=' . $tag->getId() . ']
<!-- /wp:shortcode -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:shortcode -->
[solawiform page=verteilgruppe verteiltagid=' . $tag->getId() . ']
<!-- /wp:shortcode -->
<!-- /wp:paragraph -->';
	}
}