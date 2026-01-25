<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Ein Verteiltag bei der SoLaWi hat ein bestimmtes Datum und Angaben dazu, welche Bereiche verteilt werden
 */
final class SOLAWI_Verteiltag {
	
    private static array|null $values;

	private int $id;

	private DateTime $datum;

	private array $zuVerteilendeBereiche;

	private array $textJeBereich;
	
	private string $startGemueseErnten;

	private string $endeGemueseErnten;
	
	private string $startKistenPacken;
	
	private string $endeKistenPacken;
	
	private string $startVerteilung;
	
	private string $endeVerteilung;

	/**
	 * Konstruktor
	 */
	private function __construct( int $id, DateTime $datum ) {
		$this->id = $id;
		$datum->setTime( 0, 0, 0 );
        $this->datum = $datum;
		$this->zuVerteilendeBereiche = array();
		$this->textJeBereich = array();

		$this->startGemueseErnten = "08:00";
		$this->endeGemueseErnten = "11:00";
		$this->startKistenPacken = "11:00";
		$this->endeKistenPacken = "13:00";
		$this->startVerteilung = "13:00";
		$this->endeVerteilung = "18:00";
    }

	/**
	 * Gibt alle Verteiltage zurück
	 * 
	 * @param anzahlTage optional, es werden nur so viele Verteiltage zurückgegeben, wie hier angegeben
	 */
	public static function values( int|null $anzahlTage = null ) : array {
		if ( !isset( self::$values ) ) {
			self::$values = SOLAWI_Repository::instance()->getVerteiltage();
		}
		if ( isset( $anzahlTage ) ) {
			$retVal = array_filter( self::$values, function( SOLAWI_Verteiltag $v ) { return $v->hasVerteilungen(); } );
			return array_slice( $retVal, 0, $anzahlTage );
		}
		return self::$values;
	}
	
	/**
	 * Setzt alle Verteiltage zurück
	 */
	public static function clearCache() {
		self::$values = null;
	}
	
	/**
	 * Gibt den Verteiltag mit der übergebenen ID zurück.
	 * Gibt null zurück, wenn der Verteiltag nicht gefunden wird.
	 */
	public static function valueOf( int $id ) : SOLAWI_Verteiltag|null {
		return SOLAWI_findEntityById( self::values(), $id );
	}

	/**
	 * Erstellt einen Verteiltag zum heutigen Datum
	 */
	public static function create() : SOLAWI_Verteiltag {
		return self::createFromDatum( new DateTime() );
	}

	/**
	 * Erstellt einen Verteiltag entweder aus einem DateTime oder einem String im Format "2025-12-31".
	 */
	public static function createFromDatum( DateTime|string $datum ) : SOLAWI_Verteiltag {
		$id = 0;
		$datetime = null;
		if ( is_string( $datum ) ) {
			$id = intval( preg_replace( "/-/", "", $datum ) );
			$datetime = DateTime::createFromFormat( "Ymd", $id );
		} else {
			$id = intval( SOLAWI_formatDatum( $datum, false ) );
			$datetime = $datum;
		}
		return new SOLAWI_Verteiltag( $id, $datetime );
	}

	/**
	 * Erzeugt einen Verteiltag anhand der ID (die das Datum darstellt)
	 */
	public static function createFromId( int $id ) : SOLAWI_Verteiltag {
		return new SOLAWI_Verteiltag( $id, DateTime::createFromFormat( "Ymd", $id ) );
	}
	
	/**
	 * Gibt die ID zurück
	 */
	public function getId() : int {
		return $this->id;
	}

	/**
	 * Gibt das Datum zurück
	 */
	public function getDatum() : DateTime {
		return $this->datum;
	}

	/**
	 * Gibt zurück, ob an diesem Verteiltag der übergebene Bereich verteilt wird
	 */
	public function isVerteilungVon( SOLAWI_Bereich $bereich ) : bool {
		return in_array( $bereich, $this->getVerteilungen() );
	}

	/**
	 * Ob an diesem Tag zu verteilende Bereiche angegeben sind
	 */
	public function hasVerteilungen() : bool {
		return count( $this->getVerteilungen() ) > 0;
	}

	/**
	 * Gibt die Bereiche zurück, die an diesem Tag verteilt werden
	 */
	public function getVerteilungen() : array {
		return $this->zuVerteilendeBereiche;
	}

	/**
	 * Setzt den übergebenen Bereich als "zu verteilen" bzw. "nicht zu verteilen"
	 */
	public function setVerteilungVon( SOLAWI_Bereich $bereich, bool $isVerteilung = TRUE ) : void {
		if ( $this->isVerteilungVon( $bereich ) ) {
			if ( !$isVerteilung )
				$this->zuVerteilendeBereiche = SOLAWI_arrayRemove( $this->zuVerteilendeBereiche, $bereich );
		} else {
			if ( $isVerteilung )
				$this->zuVerteilendeBereiche[] = $bereich;
		}
	}

	/**
	 * Gibt den Text für den übergebenen Bereich zurück.
	 */
	public function getText( SOLAWI_Bereich $bereich ) : string|null {
		if ( isset( $this->textJeBereich[ $bereich->getDbName() ] ) )
			return $this->textJeBereich[ $bereich->getDbName() ];
		return null;
	}

	/**
	 * Setzt den Text für den übergebenen Bereich.
	 */
	public function setText( SOLAWI_Bereich $bereich, string|null $text ) : void {
		$this->textJeBereich[ $bereich->getDbName() ] = $text;
	}

	/**
	 * Gibt den Wochentag zurück
	 */
	public function getWochentag() : string {
		$wochentage = [ "Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag" ];
		return $wochentage[ intval( $this->datum->format( "w" ) ) ];
	}

	public function getStartGemueseErnten() : string {
		return $this->startGemueseErnten;
	}

	public function getEndeGemueseErnten() : string {
		return $this->endeGemueseErnten;
	}

	public function setZeitGemueseErnten( string $start, string $ende ) : void {
		$this->startGemueseErnten = $start;
		$this->endeGemueseErnten = $ende;
	}

	public function getStartKistenPacken() : string {
		return $this->startKistenPacken;
	}

	public function getEndeKistenPacken() : string {
		return $this->endeKistenPacken;
	}

	public function setZeitKistenPacken( string $start, string $ende ) : void {
		$this->startKistenPacken = $start;
		$this->endeKistenPacken = $ende;
	}

	public function getStartVerteilung() : string {
		return $this->startVerteilung;
	}

	public function getEndeVerteilung() : string {
		return $this->endeVerteilung;
	}

	public function setZeitVerteilung( string $start, string $ende ) : void {
		$this->startVerteilung = $start;
		$this->endeVerteilung = $ende;
	}

}