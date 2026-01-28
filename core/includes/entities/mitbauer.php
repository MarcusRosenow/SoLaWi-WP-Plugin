<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 */
final class SOLAWI_Mitbauer {
	
    private static array|null $values;

	private int $userID;
	
	private string $name;
	
	private string $email;
	
	/**
	 * Ein Array mit SOLAWI_MitbauerErnteanteil
	 */
	private array $ernteanteile;
	
	private SOLAWI_Verteilstation $verteilstation;
	
	public function __construct( int $userID, string $name, string $email ) {
        $this->userID = $userID;
        $this->name = $name;
		$this->email = $email;
		$this->ernteanteile = array();
		$this->verteilstation = SOLAWI_Verteilstation::values()[0];
    }
	
	/**
	 * Gibt alle Mitbauern zurück
	 */
	public static function values( SOLAWI_Verteilstation|null $station = null ) : array {
		if ( !isset( self::$values ) ) {
			self::$values = SOLAWI_Repository::instance()->getMitbauern();
		}
		if ( isset( $station ) ) {
			return array_filter( self::$values, function( SOLAWI_Mitbauer $mitbauer ) use ( $station ) { return $mitbauer->getVerteilstation() === $station; } );
		}
		return self::$values;
	}
	
	/**
	 * Setzt alle Mitbauern zurück
	 */
	public static function clearCache() {
		self::$values = null;
	}
	
	/**
	 * Gibt den Mitbauern zur übergebenen ID zurück.
	 * Oder null, falls der Mitbauer nicht gefunden wurde.
	 */
	public static function valueOf( int $id ) : SOLAWI_Mitbauer|null {
		return SOLAWI_findEntityById( self::values(), $id );
	}

	/**
	 * Gibt ein kurzes HTML-Fragment zurück, welches die Anteile der übergebenen Mitbauern für den übergebenen Bereich summiert.
	 * Das Sieht dann ungefähr so aus:
	 * "3x ganz<br>2x halb"
	 * 
	 * @param $einzelpositionen ein Array von SOLAWI_Mitbauer
	 * @param $bereich der Bereich, um den es geht
	 * @param $trenner der Trenner zwischen den Summen
	 */
	public static function getHtmlSummierteAnteile( array $mitbauern, SOLAWI_Verteiltag $tag, SOLAWI_Bereich $bereich, string $trenner = "<br>", bool $urlaubskistenAusgeben = false ) : string {
		$anzahlen = [];
		foreach ( $mitbauern as $mitbauer ) {
			$anteil = " " . strval( $mitbauer->getErnteAnteil( $bereich, $tag->getDatum() ) );
			if ( $anteil !== "0" ) {
				if ( isset( $anzahlen[ $anteil ] ) )
					$anzahlen[ $anteil ] += 1;
				else
					$anzahlen[ $anteil ] = 1;
			}
		}
		// Urlaubskisten sind immer ganze Kisten
		if (  $urlaubskistenAusgeben && $tag->getAnzahlUrlaubskisten( $bereich ) > 0 ) {
			if ( isset( $anzahlen[ " 1" ] ) )
				$anzahlen[ " 1" ] += $tag->getAnzahlUrlaubskisten( $bereich );
			else
				$anzahlen[ " 1" ] = $tag->getAnzahlUrlaubskisten( $bereich );
		}
		$result = "";
		if ( isset( $anzahlen[ " 0.5" ] ) ) {
			$result .= $trenner . SOLAWI_formatAnzahl( $anzahlen[ " 0.5" ] ) . "x halb";
		}
		if ( isset( $anzahlen[ " 1" ] ) ) {
			$result .= $trenner . SOLAWI_formatAnzahl( $anzahlen[ " 1" ] ) . "x&nbsp;voll";
		}
		if ( isset( $anzahlen[ " 2" ] ) ) {
			$result .= $trenner . SOLAWI_formatAnzahl( $anzahlen[ " 2" ] ). "x&nbsp;doppelt";
		}
		foreach ( $anzahlen as $anteil => $anzahl ) {
			if ( $anteil !== " 0" && $anteil !== " 0.5" && $anteil !== " 1" && $anteil !== " 2" )
				$result .= $trenner . SOLAWI_formatAnzahl( $anzahl ) . "x&nbsp;" . $anteil . "fach";
		}
		if ( strlen( $result ) > 0 )
			$result = substr( $result, strlen( $trenner ) );
		if ( count( $anzahlen ) == 0 )
			$result .= "&#8709;";
		return $result;
	}

	/**
	 * Baut eine kleine HTML-Tabelle mit den Ernteanteilen dieses Mitbauers
	 */
	public function getHtmlTableErnteAnteile( bool $mitPreisen = false ) : string {
		$anteile = $this->getErnteAnteile();
		$result = "<table class='solawi'><tr class='stark'><td></td>";
		foreach ( $anteile as $anteil ) {
			$titel = $anteile[0] === $anteil ? "Aktuell" : ("Ab dem<br>" .SOLAWI_formatDatum( $anteil->getGueltigAb() ) );
			$result .= "<td class='center'>$titel</td>";
		}
		$result .= "</tr>";
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$result .= "<tr><td class='left'>" . $bereich->getName() . "</td>";
			foreach ( $anteile as $anteil ) {
				$result .= "<td class='right'>" . $anteil->getAsHtmlString( $bereich, $mitPreisen ) . "</td>";
			}
			$result .= "</tr>";
		}
        $result .= "</table>";
		return $result;
	}

	/**
	 * Gibt den aktuell angemeldeten Mitbauer zurück.
	 */
	public static function getAktuellenMitbauer() : SOLAWI_Mitbauer|null {
		$userid = get_current_user_id();
		return $userid > 0 && SOLAWI_hasRolle( SOLAWI_Rolle::MITBAUER ) ? self::valueOf( $userid ) : null;
	}
	
	public function getId() : int {
		return $this->userID;
	}

	public function getName() : string {
		return $this->name;
	}
	
	public function getEmail() : string {
		return $this->email;
	}
	
	public function getEmailAsHtmlString() : string {
		return "<a href='mailto:" . $this->getEmail() . "'>" . $this->getEmail() . "</a>";
	}
	
	/**
	 * Gibt die Ernteanteile für diesen Mitbauern zurück.
	 * Wurden bisher noch keine Ernteanteile hinzugefügt, wird ein Array mit einem Element mit aktuellem Tagesdatum geliefert.
	 */
	public function getErnteAnteile() : array {
		return empty( $this->ernteanteile )
					? array( $this->getErnteAnteilIntern( new DateTime(), true ) )
					: $this->ernteanteile;
	}

	/**
	 * Fügt ein Ernteanteil-Objekt hinzu und räumt dabei gleichzeitig auf.
	 */
	public function addErnteAnteil( SOLAWI_MitbauerErnteanteil $ernteanteil ) : void {
		$this->ernteanteile[] = $ernteanteil;
		sort( $this->ernteanteile );

		// Elemente entfernen, die zu weit in der Vergangenheit liegen
		$letzterEintrag = null;
		foreach ( $this->ernteanteile as $aktEintrag ) {
			if ( $letzterEintrag === null )
				$letzterEintrag = $aktEintrag;
			else if ( $letzterEintrag !== null && $aktEintrag->getGueltigAb() <= new DateTime() ) {
				$this->ernteanteile = array_values( array_diff( $this->ernteanteile, [ $letzterEintrag ] ) );
				$letzterEintrag = $aktEintrag;
			} else {
				break;
			}
		}
	}

	/**
	 * Ermittelt den SOLAWI_MitbauerErnteanteil zum übergebenen Zeitpunkt.
	 * 
	 * Wenn $passend true ist, werden nur Ernteanteile gesucht, die genau am übergebenen Zeitpunkt ihre Gültigkeit starten.
	 * Ansonsten wird geprüft, ob ein Ernteanteil, der ab einem früheren Zeitpunkt galt, immer noch gilt.
	 * 
	 * Wird kein passender/letzter Ernteanteil gefunden, wird ein leeres Ernteanteil-Objekt in diesen Mitbauern gespeichert und zurückgegeben.
	 */
	public function getErnteAnteilIntern( DateTime $zeitpunkt, bool $passend = false ) : SOLAWI_MitbauerErnteanteil {
		$letzterAnteil = null;
		// Die Anteile sind von alt zu neu sortiert, deshalb kann das einfach so mit der Schleife gemacht werden
		// Der letzte Eintrag, der die Bedingung erfüllt, ist der richtige
		foreach ( $this->ernteanteile as $aktAnteil )
			if ( (!$passend && $aktAnteil->getGueltigAb() <= $zeitpunkt)
					|| ($passend && $aktAnteil->getGueltigAb() == $zeitpunkt) )
				$letzterAnteil = $aktAnteil;
		if ( $letzterAnteil === null ) {
			$letzterAnteil = new SOLAWI_MitbauerErnteanteil( $zeitpunkt );
			$this->addErnteAnteil( $letzterAnteil );
		}
		return $letzterAnteil;
	}

	/**
	 * Gibt die Ernteanteile für den übergebenen Bereich zurück.
	 * @return 0, 0.5, 1 ...
	 */
	public function getErnteAnteil( SOLAWI_Bereich $bereich, DateTime $zeitpunkt ) : float {
		return $this->getErnteAnteilIntern( $zeitpunkt )->getAnzahl( $bereich );
	}

	/**
	 * Gibt zurück, ob der Mitbauer Ernteanteile hat
	 * @param $bereiche entweder ein SOLAWI_Bereich, oder ein Array von Bereichen
	 */
	public function hasErnteAnteile( SOLAWI_Bereich|array $bereiche = null, DateTime $zeitpunkt ) : bool {
		if ( $bereiche === null )
			$bereiche = SOLAWI_Bereich::values();
		else if ( $bereiche instanceof SOLAWI_Bereich )
			$bereiche = array( $bereiche );
		foreach( $bereiche as $bereich )
			if ( $this->getErnteAnteil( $bereich, $zeitpunkt ) > 0 )
				return true;
		return false;
	}
	
	public function getVerteilstation() : SOLAWI_Verteilstation {
		return $this->verteilstation;
	}
	
	public function setVerteilstation( SOLAWI_Verteilstation $verteilstation ) {
		$this->verteilstation = $verteilstation;
	}

	/**
	 * Gibt alle Stichtage zurück, die in der Zukunft liegen
	 * und an denen dieser Mitbauer seinen Ernteanteil ändern wird.
	 */
	public function getZukuenftigeStichtageFuerErnteAnteile() : array {
		$result = array();
		foreach( $this->ernteanteile as $ernteanteil ) {
			if ( $ernteanteil->getGueltigAb() > new DateTime() )
				$result[] = $ernteanteil->getGueltigAb();
		}
		return $result;
	}
}