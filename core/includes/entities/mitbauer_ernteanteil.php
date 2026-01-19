<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Die Ernteanteile eines Mitbauern zusammen mit den Angaben zu gültig ab/bis
 */
final class SOLAWI_MitbauerErnteanteil {

	private DateTime $gueltigAb;

	private array $ernteanteile;

	private array $preise;
	
	public function __construct( DateTime $gueltigAb ) {
		$gueltigAb->setTime( 0, 0, 0 );
		$this->gueltigAb = $gueltigAb;
		$this->ernteanteile = array();
		$this->preise = array();
        foreach( SOLAWI_Bereich::values() as $bereich ) {
			$this->setAnzahl( $bereich, 0.0 );
			$this->setPreis( $bereich, 0.00 );
		}
    }

	/**
	 * Wird benötigt, um auf Arrays array_diff aufrufen zu können
	 */
	public function __toString() : string {
		return "SOLAWI_MitbauerErnteanteil( " . SOLAWI_formatDatum( $this->getGueltigAb() ) . " " . var_export( $this->ernteanteile, true ) . " )";
	}

	/**
	 * Gibt das DateTime zurück, ab wann diese Ernteanteile-Konfiguration gültig ist.
	 */
	public function getGueltigAb() : DateTime {
		return $this->gueltigAb;
	}

	/**
	 * Gibt nur die Anzahl der Ernteanteile aus, nicht den Preis
	 */
	public function getAsHtmlString( SOLAWI_Bereich $bereich, bool $mitPreisen = false ) : string {
		$anteil = $this->getAnzahl( $bereich );
		$preis = $this->getPreis( $bereich );
		$result = "<b>";
		if ( $anteil == 0 )
			return "-";
		else
			$result .= SOLAWI_formatAnzahl( $anteil );
		$result .= "</b>";
		if ( $mitPreisen )
			$result .= "<br><i>" . SOLAWI_formatWaehrung( $preis, true ) . "</i>";
		return $result;
	}
	
	/**
	 * Gibt die Ernteanteile für den übergebenen Bereich zurück.
	 * @return 0, 0.5, 1 ...
	 */
	public function getAnzahl( SOLAWI_Bereich $bereich ) : float {
		return $this->ernteanteile[ $bereich->getDbName() ];
	}

	/**
	 * Gibt den Preis für den übergebenen Bereich zurück.
	 * Das ist nicht der Preis pro einem Ernteanteil,
	 * sondern der Gesamtpreis.
	 * 
	 * @return 0.00 ... 9999.00
	 */
	public function getPreis( SOLAWI_Bereich $bereich ) : float {
		return $this->preise[ $bereich->getDbName() ];
	}
	
	/**
	 * Setzt die Anzahl der Ernteanteile in einem bestimmten Bereich.
	 */
	public function setAnzahl( SOLAWI_Bereich $bereich, float $anzahl ) {
		$this->ernteanteile[ $bereich->getDbName() ] = $anzahl;
	}
	
	/**
	 * Setzt den Preis. Das ist nicht der Preis pro einem Ernteanteil,
	 * sondern der Gesamtpreis.
	 */
	public function setPreis( SOLAWI_Bereich $bereich, float $anzahl ) {
		$this->preise[ $bereich->getDbName() ] = $anzahl;
	}
}