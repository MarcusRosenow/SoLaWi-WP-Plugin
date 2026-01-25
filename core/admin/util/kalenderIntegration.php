<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Das Ergebnis des Speicherns der Daten von einem AJAX-Request
 */
class SOLAWI_KalenderIntegration {
    
	const KALENDER_TAXONOMY = 'tribe_events_cat';

	const KALENDER_POST_TYPE = 'tribe_events';

	private static $instance;

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
		if ( $this->isEventsCalendarAktiviert() && !term_exists( "verteiltage", self::KALENDER_TAXONOMY ) ) {
			wp_insert_term( "Verteiltage", self::KALENDER_TAXONOMY,	[ "slug" => "verteiltage" ] );
		}
    }

	/**
	 * Prüft, ob "The Events Calendar installiert ist
	 */
	private function isEventsCalendarAktiviert() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		return is_plugin_active( 'the-events-calendar/the-events-calendar.php' );
	}

    public function updateKalender( SOLAWI_Verteiltag $tag ) : array {
		if ( !$this->isEventsCalendarAktiviert() )
			return $this->ergebnis( "'The Events Calendar' ist nicht installiert" );

		// Grundlegende Pflichtfelder
		$title       = "Verteiltag für " . SOLAWI_arrayToString( $tag->getVerteilungen(), "getSimpleName" );
		$start_time  = SOLAWI_formatDatum( $tag->getDatum(), true ) . $tag->getStartVerteilung() . ":00";
		$end_time    = SOLAWI_formatDatum( $tag->getDatum(), true ) . $tag->getEndeVerteilung() . ":00";
		$description = $this->getKalenderEintrag( $tag );
		$post_name   = 'verteilung' . $tag->getId();

		// 1) Basierend auf dem Post-Typ ein neuer Beitrag
		$post_data = [
			'post_title'   => $title,
			'post_content' => $description,
			'post_status'  => 'publish',
			'post_type'    => self::KALENDER_POST_TYPE,
			'post_author'  => get_current_user_id(),
			'post_name'    => $post_name
		];

		
		$vorhandenerPost = get_posts( [ 'post_name__in' => [ $post_name ], 'post_type' => self::KALENDER_POST_TYPE ] );
		if ( count( $vorhandenerPost ) > 0 ) {
			if ( count( $vorhandenerPost ) > 1 )
				return $this->ergebnis( "Mehr als ein Kalendereintrag mit Name $post_name gefunden" );
			$post_data[ "ID" ] = is_integer( $vorhandenerPost[ 0 ] ) ? $vorhandenerPost[ 0 ] : $vorhandenerPost[ 0 ]->ID;
		}

		$event_id = 0;
		$mussPostAnlegen = count( $tag->getVerteilungen() ) > 0;
		if ( !$mussPostAnlegen && isset( $post_data[ "ID" ] ) ) {
			// Beitrag löschen
			wp_delete_post( $post_data[ "ID" ] );
		} else if ( $mussPostAnlegen && isset( $post_data[ "ID" ] ) ) {
			// Beitrag aktualisieren
			$event_id = wp_update_post( $post_data );
		} else if ( $mussPostAnlegen ) {
			// Beitrag erstellen
			$event_id = wp_insert_post( $post_data );
		}

		if ( is_wp_error( $event_id ) ) {
			return $this->ergebnis( $event_id->get_error_message() );
		}

		// Start und Ende setzen
		update_post_meta( $event_id, '_EventTimezone', 'Europe/Berlin' );
		update_post_meta( $event_id, '_EventStartDate', $start_time );
		update_post_meta( $event_id, '_EventStartDateUTC', $start_time );
		update_post_meta( $event_id, '_EventEndDate', $end_time );
		update_post_meta( $event_id, '_EventEndDateUTC', $end_time );

		// Kategorie setzen
		wp_set_object_terms( $event_id, [ "verteiltage" ], self::KALENDER_TAXONOMY );

		// Rückgabe
		return $this->ergebnis(null, $event_id );
	}

	/**
	 * Baut das Ergebnisobjekt zusammen
	 */
	private function ergebnis( string|null $errorMessage, int|null $eventId ) : array {
		return [
			"success" => $errorMessage == null,
			"error" => $errorMessage,
			'event_id' => $eventId
		];
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