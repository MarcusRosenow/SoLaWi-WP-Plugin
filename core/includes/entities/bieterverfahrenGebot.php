<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Ein SOLAWI_BieterverfahrenGebot ist ein Gebot eines Mitbauern in einer bestimmten Runde eine Bieterverfahrens
 */
final class SOLAWI_BieterverfahrenGebot {
	
    private static array|null $values;

	private int|null $id;

	private SOLAWI_Bieterverfahren $bieterverfahren;

	private SOLAWI_MITBAUER $mitbauer;

	private int $runde;

	private array $anzahl;

	private array $preis;
	
	/**
	 * Konstruktor
	 * 
	 * @param id die ID
	 */
	public function __construct( int|null $id = null ) {
		$this->id = $id;
        $this->runde = 0;
		$this->anzahl = array();
		$this->preis = array();
        foreach( SOLAWI_Bereich::values() as $bereich ) {
			$this->setAnzahl( $bereich, 0.0 );
			$this->setPreis( $bereich, 0.00 );
		}
    }

	/**
	 * Gibt alle Bieterverfahren zurück
	 */
	public static function values( SOLAWI_Bieterverfahren|null $bieterverfahren = null, int|null $runde = null, SOLAWI_MITBAUER|null $mitbauer = null ) : array {
		if ( !isset( self::$values ) ) {
			self::$values = SOLAWI_Repository::instance()->getBieterverfahrenGebote();
		}
		$result = self::$values;
		if ( isset( $bieterverfahren ) ) {
			$result = array_filter( $result, function( SOLAWI_BieterverfahrenGebot $gebot ) use ( $bieterverfahren )
									{ return $gebot->getBieterverfahren() === $bieterverfahren; } );
		}
		if ( isset( $runde ) ) {
			$result = array_filter( $result, function( SOLAWI_BieterverfahrenGebot $gebot ) use ( $runde )
									{ return $gebot->getRunde() === $runde; } );
		}
		if ( isset( $mitbauer ) ) {
			$result = array_filter( $result, function( SOLAWI_BieterverfahrenGebot $gebot ) use ( $mitbauer )
									{ return $gebot->getMitbauer() === $mitbauer; } );
		}
		return array_values( $result );
	}
	
	/**
	 * Setzt alle Verteiltage zurück
	 */
	public static function clearCache() {
		self::$values = null;
	}
	
	/**
	 * Gibt die Entity mit der übergebenen ID zurück.
	 * Achtung, hier kommt es zum Fehler, wenn das Objekt noch nicht persistent ist.
	 */
	public static function valueOf( int $id ) : SOLAWI_BieterverfahrenGebot {
		return SOLAWI_findEntityById( self::values(), $id );
	}
	
	/**
	 * Gibt die ID zurück
	 */
	public function getId() : int|null {
		return $this->id;
	}
	
	/**
	 * Gibt das Bieterverfahren zurück, zu dem dieses Gebot gehört
	 */
	public function getBieterverfahren() : SOLAWI_Bieterverfahren {
		return $this->bieterverfahren;
	}

	public function setBieterverfahren( SOLAWI_Bieterverfahren $bieterverfahren ) : void {
		$this->bieterverfahren = $bieterverfahren;
	}

	/**
	 * Gibt die Runde zurück, für welche dieses Gebot abgegeben wurde
	 * 
	 * @return >= 1
	 */
	public function getRunde() : int {
		return $this->runde;
	}

	public function setRunde( int $runde ) : void {
		$this->runde = $runde;
	}

	/**
	 * Gibt den Mitbauern zurück, zu dem dieses Gebot gehört
	 */
	public function getMitbauer() : SOLAWI_Mitbauer {
		return $this->mitbauer;
	}

	public function setMitbauer( SOLAWI_Mitbauer $mitbauer ) : void {
		$this->mitbauer = $mitbauer;
	}

	/**
	 * Gibt die Anzahl der Ernteanteile für den übergebenen Bereich zurück
	 * 
	 * @return >= 0
	 */
	public function getAnzahl( SOLAWI_Bereich $bereich ) : float {
		return $this->anzahl[ $bereich->getDbName() ];
	}

	public function setAnzahl( SOLAWI_Bereich $bereich, float $wert ) : void {
		$this->anzahl[ $bereich->getDbName() ] = $wert;
	}

	/**
	 * Gibt die Preis der Ernteanteile für den übergebenen Bereich zurück, den der Mitbauer geboten hat.
	 * 
	 * @return >= 0
	 */
	public function getPreis( SOLAWI_Bereich $bereich ) : float {
		return $this->preis[ $bereich->getDbName() ];
	}

	public function setPreis( SOLAWI_Bereich $bereich, float $wert ) : void {
		$this->preis[ $bereich->getDbName() ] = $wert;
	}
}