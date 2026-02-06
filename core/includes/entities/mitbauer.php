<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 */
final class SOLAWI_Mitbauer {
	
	const META_KEY_VERTEILSTATION = 'solawi_verteilstation';

	const META_KEY_ERNTEANTEILE = 'solawi_ernteanteile';

	const META_KEY_TELEFON = 'solawi_telefon';

	const META_KEY_STRASSE = 'solawi_strasse';
	
	const META_KEY_ORT = 'solawi_ort';

	const META_KEY_BEMERKUNG = 'solawi_bemerkung';

	const META_KEY_ROLLEN = 'solawi_rollen';

    private static array|null $values;

	private int $userID;
	
	public function __construct( int $userID ) {
        $this->userID = $userID;
    }
	
	/**
	 * Gibt alle Mitbauern zurück
	 */
	public static function values( SOLAWI_Verteilstation|null $station = null, bool $nurEchteMitbauern = false ) : array {
		if ( !isset( self::$values ) ) {
			self::$values = [];
			foreach( get_users( [ 'fields' => [ 'ID' ] ] ) as $userAttributes ) {	
				$userId = $userAttributes->ID;
				self::$values[] = new SOLAWI_Mitbauer( $userId );
			}
		}
		if ( isset( $station ) ) {
			return array_filter( self::$values, function( SOLAWI_Mitbauer $mitbauer ) use ( $station ) { return $mitbauer->getVerteilstation() === $station; } );
		}
		if ( $nurEchteMitbauern ) {
			return array_filter( self::$values, function( SOLAWI_Mitbauer $mitbauer ) { return $mitbauer->hasRolle( SOLAWI_Rolle::MITBAUER ); } );
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
	 * Gibt den Mitbauern zur übergebenen E-Mail zurück.
	 * Oder null, falls der Mitbauer nicht gefunden wurde.
	 */
	public static function valueOfEmail( string $email ) : SOLAWI_Mitbauer|null {
		foreach ( self::values() as $mitbauer ) {
			if ( $mitbauer->getEmail() == $email )
				return $mitbauer;
		}
		return null;
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
			$anteil = " " . strval( $mitbauer->getErnteanteil( $bereich, $tag->getDatum() ) );
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
	public function getHtmlTableErnteanteile( bool $mitPreisen = false ) : string {
		$anteile = $this->getErnteanteile();
		$result = "<table class='solawi'><tr class='stark'><td></td>";
		foreach ( $anteile as $anteil ) {
			$titel =  $anteil->getGueltigAb() < new DateTime() ? "Aktuell" : ( "Ab dem<br>" . SOLAWI_formatDatum( $anteil->getGueltigAb() ) );
			$result .= "<td class='right'>$titel</td>";
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
	 * null, wenn der Nutzer nicht angemeldet ist
	 */
	public static function getAktuellenMitbauer() : SOLAWI_Mitbauer|null {
		$userid = get_current_user_id();
		return $userid > 0 ? self::valueOf( $userid ) : null;
	}
	
	public function getId() : int {
		return $this->userID;
	}

	public function getName() : string {
		return get_userdata( $this->getId() )->get( 'display_name' );
	}
	
	public function getEmail() : string {
		return get_userdata( $this->getId() )->get( 'user_email' );
	}
	
	public function getEmailAsHtmlString( bool $icon = false ) : string {
		$mail = $this->getEmail();
		if ( $icon )
			return "<a href='mailto:$mail'>&#x1F4E7;</a>";
		return "<a href='mailto:$mail'>$mail</a>";
	}
	
	/**
	 * Gibt Metaangaben mit dem übergebenen Key zurück.
	 * Wird der Key nicht gefunden, wird $defaultValue zurückgegeben.
	 */
	private function getMeta( string $key, mixed $defaultValue ) : mixed {
		$value = get_user_meta( $this->userID, $key, true );
		if ( $value === false || $value === '' )
			return $defaultValue;
		return $value;
	}

	/**
	 * Setzt den übergebenen Meta-Key auf den übergebenen Wert
	 */
	private function setMeta( string $key, mixed $value ) : void {
		if ( $value !== false && $value !== null && $value !== '' )
			update_user_meta( $this->userID, $key, $value );
		else
			delete_user_meta( $this->userID, $key );
	}

	/**
	 * Gibt die Ernteanteile für diesen Mitbauern zurück.
	 * Kann auch ein leeres Array sein.
	 */
	public function getErnteanteile() : array {
		return $this->getMeta( self::META_KEY_ERNTEANTEILE, [] );
	}

	/**
	 * Fügt ein Ernteanteil-Objekt hinzu und räumt dabei gleichzeitig auf.
	 */
	public function addErnteanteil( SOLAWI_MitbauerErnteanteil $ernteanteil ) : void {
		$ernteanteile = $this->getErnteanteile();
		$ernteanteile[] = $ernteanteil;
		sort( $ernteanteile );

		// Elemente entfernen, die zu weit in der Vergangenheit liegen
		$letzterEintrag = null;
		foreach ( $ernteanteile as $aktEintrag ) {
			if ( $letzterEintrag === null )
				$letzterEintrag = $aktEintrag;
			else if ( $letzterEintrag !== null && $aktEintrag->getGueltigAb() <= new DateTime() ) {
				$ernteanteile = array_values( array_diff( $ernteanteile, [ $letzterEintrag ] ) );
				$letzterEintrag = $aktEintrag;
			} else {
				break;
			}
		}
		$this->setMeta( self::META_KEY_ERNTEANTEILE, $ernteanteile );
	}

	/**
	 * Updated das übergebene Objekt und speichert es in der Datenbank.
	 */
	public function updateErnteanteil( SOLAWI_MitbauerErnteanteil $ernteanteil ) : void {
		$ernteanteile = $this->getErnteanteile();
		foreach ( $ernteanteile as $aktEintrag ) {
			if ( $aktEintrag->getGueltigAb() == $ernteanteil->getGueltigAb() ) {
				$ernteanteile = array_values( array_diff( $ernteanteile, [ $aktEintrag ] ) );
				$ernteanteile[] = $ernteanteil;
				sort( $ernteanteile );
				break;
			}
		}
		$this->setMeta( self::META_KEY_ERNTEANTEILE, $ernteanteile );
	}

	/**
	 * Entfernt alle Ernteanteile
	 */
	public function removeErnteanteile() : void {
		$this->setMeta( self::META_KEY_ERNTEANTEILE, null );
	}

	/**
	 * Ermittelt den SOLAWI_MitbauerErnteanteil zum übergebenen Zeitpunkt.
	 * 
	 * Wenn $passend true ist, werden nur Ernteanteile gesucht, die genau am übergebenen Zeitpunkt ihre Gültigkeit starten.
	 * Ansonsten wird geprüft, ob ein Ernteanteil, der ab einem früheren Zeitpunkt galt, immer noch gilt.
	 * 
	 * Wird kein passender/letzter Ernteanteil gefunden, wird ein leeres Ernteanteil-Objekt in diesen Mitbauern gespeichert und zurückgegeben.
	 */
	public function getErnteanteilIntern( DateTime $zeitpunkt, bool $passend = false ) : SOLAWI_MitbauerErnteanteil|null {
		$letzterAnteil = null;
		// Die Anteile sind von alt zu neu sortiert, deshalb kann das einfach so mit der Schleife gemacht werden
		// Der letzte Eintrag, der die Bedingung erfüllt, ist der richtige
		foreach ( $this->getErnteanteile() as $aktAnteil )
			if ( (!$passend && $aktAnteil->getGueltigAb() <= $zeitpunkt)
					|| ($passend && $aktAnteil->getGueltigAb() == $zeitpunkt) )
				$letzterAnteil = $aktAnteil;
		return $letzterAnteil;
	}

	/**
	 * Gibt die Ernteanteile für den übergebenen Bereich zurück.
	 * @return 0, 0.5, 1 ...
	 */
	public function getErnteanteil( SOLAWI_Bereich $bereich, DateTime $zeitpunkt ) : float {
		$ea = $this->getErnteanteilIntern( $zeitpunkt, false, false );
		return isset( $ea ) ? $ea->getAnzahl( $bereich ) : 0;
	}

	/**
	 * Gibt zurück, ob der Mitbauer Ernteanteile hat
	 * @param $bereiche entweder ein SOLAWI_Bereich, oder ein Array von Bereichen
	 */
	public function hasErnteanteile( SOLAWI_Bereich|array $bereiche = null, DateTime $zeitpunkt ) : bool {
		if ( $bereiche === null )
			$bereiche = SOLAWI_Bereich::values();
		else if ( $bereiche instanceof SOLAWI_Bereich )
			$bereiche = array( $bereiche );
		foreach( $bereiche as $bereich )
			if ( $this->getErnteanteil( $bereich, $zeitpunkt ) > 0 )
				return true;
		return false;
	}

	/**
	 * Gibt alle Stichtage zurück, die in der Zukunft liegen
	 * und an denen dieser Mitbauer seinen Ernteanteil ändern wird.
	 */
	public function getZukuenftigeStichtageFuerErnteanteile() : array {
		$result = array();
		foreach( $this->getErnteanteile() as $ernteanteil ) {
			if ( $ernteanteil->getGueltigAb() > new DateTime() )
				$result[] = $ernteanteil->getGueltigAb();
		}
		return $result;
	}
	
	public function getVerteilstation() : SOLAWI_Verteilstation {
		$stationID = $this->getMeta( self::META_KEY_VERTEILSTATION, '0' );
		return SOLAWI_Verteilstation::valueOf( intval( $stationID ) );
	}
	
	public function setVerteilstation( SOLAWI_Verteilstation $verteilstation ) {
		$this->setMeta( self::META_KEY_VERTEILSTATION, $verteilstation->getId() );
	}

	public function getTelefonnummer() : string|null {
		return $this->getMeta( self::META_KEY_TELEFON, null );
	}
	
	public function setTelefonnummer( string|null $telefonnummer ) {
		$this->setMeta( self::META_KEY_TELEFON, $telefonnummer );
	}

	public function getTelefonnummerAsHtmlString( bool $icon = false ) : string {
		$tel = $this->getTelefonnummer();
		if ( $tel == null )
			return "";
		if ( $icon )
			return "<a href='tel:$tel'>&#x1F4DE;</a>";
		return "<a href='tel:$tel'>$tel</a>";
	}

	public function getStrasse() : string|null {
		return $this->getMeta( self::META_KEY_STRASSE, null );
	}
	
	public function setStrasse( string|null $strasse ) {
		$this->setMeta( self::META_KEY_STRASSE, $strasse );
	}

	public function getOrt() : string|null {
		return $this->getMeta( self::META_KEY_ORT, null );
	}
	
	public function setOrt( string|null $ort ) {
		$this->setMeta( self::META_KEY_ORT, $ort );
	}

	public function getBemerkung() : string|null {
		return $this->getMeta( self::META_KEY_BEMERKUNG, null );
	}
	
	public function setBemerkung( string|null $bemerkung ) {
		$this->setMeta( self::META_KEY_BEMERKUNG, $bemerkung );
	}
	
	/**
	 * Gibt zurück, ob der Nutzer bereits initialisiert ist
	 */
	public function isRolleInitialisiert() : int {
		$value = get_user_meta( $this->getId(), self::META_KEY_ROLLEN, true );
		return $value !== false && $value !== '';
	}

	/**
	 * Gibt das Objekt zum übergebenen Nutzer zurück. ungleich null
	 */
	private function getRollen() : int {
		$value = $this->getMeta( self::META_KEY_ROLLEN, '0' );
		return intval( $value );
	}

	public function setRollen( array $rollen ) : void {
		$value = 0;
		foreach( $rollen as $rolle ) {
			$value = $value | $rolle->getId();
		}
		$this->setMeta( self::META_KEY_ROLLEN, $value );
	}

	/**
	 * Gibt zurück, ob der übergebene Nutzer die übergebene Rolle hat
	 */
	public function hasRolle( SOLAWI_Rolle $rolle ) : bool {
		return ( $this->getRollen() & $rolle->getId() ) > 0;
	}
}