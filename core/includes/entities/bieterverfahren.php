<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Ein Bieterverfahren besteht aus mehreren Runden
 */
final class SOLAWI_Bieterverfahren {
	
    private static array|null $values;

	private int|null $id;

	private float $gesamtbudget;

	private int $anzahlRunden;

	private DateTime $startDerSaison;

	private int $aktuelleRunde;

	private int $letzteRunde;

	private DateTime $gueltigAb;

	private null|DateTime $gueltigBis;

	private array $richtwerte;

	private array $planVergebeneAnteile;
	
	/**
	 * Konstruktor
	 * 
	 * @param id die ID
	 * @param gueltigAb das Gültig-Ab-Datum
	 */
	public function __construct( int|null $id = null, DateTime|null $gueltigAb = null ) {
		$this->id = $id;
        $this->gesamtbudget = 0;
		$this->anzahlRunden = 3;
		$this->startDerSaison = new DateTime();
		$this->startDerSaison->modify('April 1st');
		$this->startDerSaison->modify('00:00:00');
		$this->aktuelleRunde = 1;
		$this->letzteRunde = 0;
		$this->gueltigAb = $gueltigAb == null ? new DateTime() : $gueltigAb;
		$this->gueltigBis = null;
		$this->richtwerte = array();
		$this->planVergebeneAnteile = array();
        foreach( SOLAWI_Bereich::values() as $bereich ) {
			$this->setRichtwert( $bereich, 0.00 );
			$this->setPlanVergebeneAnteile( $bereich, 0.0 );
		}
    }

	/**
	 * Gibt alle Bieterverfahren zurück
	 */
	public static function values() : array {
		if ( !isset( self::$values ) ) {
			self::$values = SOLAWI_Repository::instance()->getBieterverfahren();
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
	 * Gibt die Entity mit der übergebenen ID zurück.
	 * Achtung, hier kommt es zum Fehler, wenn das Objekt noch nicht persistent ist.
	 */
	public static function valueOf( int $id ) : SOLAWI_Bieterverfahren {
		return SOLAWI_findEntityById( self::values(), $id );
	}

	/**
	 * Gibt zurück, ob gerade ein Bieterverfahren läuft
	 */
	public static function isBieterverfahrenAktiv() : bool {
		return self::getAktuellesVerfahren() !== null;
	}

	/**
	 * Gibt das aktuell laufende Bieterverfahren zurück ... oder null
	 */
	public static function getAktuellesVerfahren() : SOLAWI_Bieterverfahren|null {
		foreach( self::values() as $verfahren )
			if ( $verfahren->getGueltigBis() === null )
				return $verfahren;
		return null;
	}

	 /**
     * Gibt die Gebote für die übergebenen Parameter zurück
	 * 
	 * @return array von SOLAWI_BieterverfahrenGebot
     */
	public function getGebote( int|null $runde = null ) : array {
		$gebote = array();
       	foreach( SOLAWI_Mitbauer::values() as $mitbauer ) {
			$gebot = $this->getGebot( $runde, $mitbauer );
			if ( $gebot != null )
				$gebote[] = $gebot;
		}
        return $gebote;
	}

    /**
     * Gibt das Gebot für die übergebenen Parameter zurück
     */
    public function getGebot( int $runde, SOLAWI_Mitbauer $mitbauer ) : SOLAWI_BieterverfahrenGebot|null {
        // Prüfen, ob es Gebote in dieser oder den Vorrunden gibt
        for( $i=$runde; $i > 0; $i-- ) {
            $gebot = SOLAWI_BieterverfahrenGebot::values( $this, $i, $mitbauer );
            if ( count( $gebot ) > 0 )
                return $gebot[0];
        }
        return null;
    }

    /**
     * Gibt das Gebot für die übergebenen Parameter zurück
     */
    public function getDurchschnittsGebot( int $runde, SOLAWI_Bereich $bereich ) : float {
		$anzahl = 0;
		$gesamtpreis = 0;
		foreach( $this->getGebote( $runde ) as $gebot ) {
			$anzahl += $gebot->getAnzahl( $bereich );
			$gesamtpreis += $gebot->getPreis( $bereich );
		}
        return $anzahl == 0
			? $this->getRichtwert( $bereich )
			: ceil( $gesamtpreis / $anzahl );
    }

	/**
	 * Gibt die ID zurück
	 */
	public function getId() : int|null {
		return $this->id;
	}
	
	/**
	 * Gibt das Budget zurück, welches durch die Gemeinschaft erwirtschaftet werden muss.
	 * Das sind alle Ausgaben, abzüglich der Einnahmen aus Fördermitteln und Direktvermarktung.
	 */
	public function getGesamtbudget() : float {
		return $this->gesamtbudget;
	}

	public function setGesamtBudget( float $gesamtbudget ) : void {
		$this->gesamtbudget = $gesamtbudget;
	}

	/**
	 * Gibt die Anzahl der Runden zurück, die dieses Bieterverfahren laufen soll.
	 * 
	 * @return >= 0
	 */
	public function getAnzahlRunden() : int {
		return $this->anzahlRunden;
	}

	public function setAnzahlRunden( int $anzahlRunden ) : void {
		$this->anzahlRunden = $anzahlRunden;
	}

	/**
	 * Gibt das Ende dieses Bieterverfahrens zurück (wenn es abgeschlossen sein sollte).
	 */
	public function getStartDerSaison() : DateTime {
		return $this->startDerSaison;
	}

	public function setStartDerSaison( DateTime $startDerSaison ) : void {
		$this->startDerSaison = $startDerSaison;
	}

	/**
	 * Gibt die Nummer der aktuellen Runde zurück
	 * 
	 * @return 0 <= aktuelleRunde <= anzahlRunden; 0=Keine Runde aktiv
	 * 
	 */
	public function getAktuelleRunde() : int {
		return $this->aktuelleRunde;
	}

	public function setAktuelleRunde( int $aktuelleRunde ) : void {
		$this->aktuelleRunde = $aktuelleRunde;
	}

	/**
	 * Gibt die Nummer der letzten abgeschlossenen Runde zurück
	 * 
	 * @return 0 <= letzteRunde <= anzahlRunden; 0=Noch keine Runde abgeschlossen
	 * 
	 */
	public function getLetzteRunde() : int {
		return $this->letzteRunde;
	}

	public function setLetzteRunde( int $letzteRunde ) : void {
		$this->letzteRunde = $letzteRunde;
	}

	/**
	 * Gibt das Ende dieses Bieterverfahrens zurück (wenn es abgeschlossen sein sollte).
	 */
	public function getGueltigAb() : DateTime {
		return $this->gueltigAb;
	}

	/**
	 * Gibt das Ende dieses Bieterverfahrens zurück (wenn es abgeschlossen sein sollte).
	 */
	public function getGueltigBis() : DateTime|null {
		return $this->gueltigBis;
	}

	public function setGueltigBis( DateTime|null $gueltigBis ) : void {
		$this->gueltigBis = $gueltigBis;
	}

	/**
	 * Gibt den Richtwert für den übergebenen Bereich zurück
	 * 
	 * @return >= 0
	 */
	public function getRichtwert( SOLAWI_Bereich $bereich ) : float {
		return $this->richtwerte[ $bereich->getDbName() ];
	}

	public function setRichtwert( SOLAWI_Bereich $bereich, float $wert ) : void {
		$this->richtwerte[ $bereich->getDbName() ] = $wert;
	}

	/**
	 * Gibt den Plan für die vergebenen Anteil im folgenden Jahr zurück.
	 * Wenn keine 100% Auslastung erfolgt, müssen diejenigen, die in der SoLaWi Mitbauern sind 100% des Gesamtbudgets erbringen.
	 * Mit diesem Plan lässt sich dieses Problem der Nicht-Vollauslastung überbrücken.
	 * 
	 * @return >= 0
	 */
	public function getPlanVergebeneAnteile( SOLAWI_Bereich $bereich ) : float {
		return $this->planVergebeneAnteile[ $bereich->getDbName() ];
	}

	public function setPlanVergebeneAnteile( SOLAWI_Bereich $bereich, float $wert ) : void {
		$this->planVergebeneAnteile[ $bereich->getDbName() ] = $wert;
	}
}