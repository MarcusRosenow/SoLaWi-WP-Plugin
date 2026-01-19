<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Frontend-Ausgabe für das Bieterverfahren
 */
final class SOLAWI_WidgetErfassunggebote extends SOLAWI_AbstractWidget {

	private bool $isMitbauerAuswahl;
	
	private array $callbackSubmitButton;

	private SOLAWI_MITBAUER|null $mitbauer;

	/**
	 * Konstruktor
	 * @param $isMitbauerAuswahl ob die Auswahl der Mitbauern ermöglicht werden soll
	 * @param $callbackSubmitButton Array mit dem Objekt, auf dem die Funktion aufgerufen werden soll an Position 0
	 * 								und dem Namen der Funktion an Position 1 (Die Funktion erwartet die Rundennummer als Parameter).
	 * 								Die Funktion muss public sein
	 * @param $mitbauer wenn es die Mitbauerauswahl geben soll, kann der mitbauer null sein, ansonsten muss er übergeben werden
	 */
    public function __construct( bool $isMitbauerAuswahl, array $callbackSubmitButton, SOLAWI_Mitbauer|null $mitbauer = null ) {
		if ( !$isMitbauerAuswahl && $mitbauer == null )
			wp_die( "Interner Fehler!" );
		$this->isMitbauerAuswahl = $isMitbauerAuswahl;
		$this->callbackSubmitButton = $callbackSubmitButton;
		$this->mitbauer = $mitbauer;
	}

	public static function isPostdataVonDiesemWidget( array $data ) : bool {
		return isset( $data[ "widget" ] ) && "SOLAWI_WidgetErfassunggebote" === $data[ "widget" ];
	}

	/**
	 * Gibt das SOLAWI_BieterverfahrenGebot aus den POST-Daten zurück, oder eine Fehlermeldung als string.
	 * Die Konvertierung klappt nur, wenn es ein offenes Bieterverfahren gibt und die Runde noch offen ist.
	 */
	public static function konvertierePostdataZuGebot( array $data ) : SOLAWI_BieterverfahrenGebot|string {
		if ( !self::isPostdataVonDiesemWidget( $data ) ) {
			return "Postdata nicht von diesem Widget";
		}
		$verfahren = SOLAWI_Bieterverfahren::getAktuellesVerfahren();
        if ( !isset( $data["runde"] )
			|| intval( $data["runde"] ) > $verfahren->getAnzahlRunden() ) {
			return "Runde nicht korrekt.";
		}
        $runde = intval( $data[ "runde" ] );
        if ( $runde < $verfahren->getAktuelleRunde() ) {
			return "Die Runde $runde ist schon geschlossen.<br>Es können keine Gebote mehr für diese Runde abgegeben werden.";
        }
        if ( !isset( $data["mitbauer"] )
			|| ( SOLAWI_Mitbauer::getAktuellenMitbauer()->getId() !== intval( $data["mitbauer"] )
				&& !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) ) ) {
			return "Mitbauer nicht korrekt!";
        }
		$mitbauer = SOLAWI_MITBAUER::valueOf( intval( $data["mitbauer"] ) );
        $gebot = SOLAWI_BieterverfahrenGebot::values( $verfahren, $runde, $mitbauer );
        $gebot = count( $gebot ) == 0 ? new SOLAWI_BieterverfahrenGebot() : $gebot[0];
        $gebot->setBieterverfahren( $verfahren );
        $gebot->setMitbauer( $mitbauer );
        $gebot->setRunde( $runde );
        foreach( SOLAWI_Bereich::values() as $bereich ) {
            $anzahl = $data[ "anzahl$runde" . $bereich->getDbName() ];
            $preis = $data[ "preis$runde" . $bereich->getDbName() ];
            if ( !SOLAWI_isGueltigerAnteil( $anzahl ) || !SOLAWI_isGueltigerPreis( $preis ) ) {
                return "Eingegebene Daten nicht korrekt.<br>Bitte erneut versuchen.";
            }
            $gebot->setAnzahl( $bereich, $anzahl );
            $gebot->setPreis( $bereich, $preis );
        }
        return $gebot;
	}

	
    public function getHtml() : string {
        $verfahren = SOLAWI_Bieterverfahren::getAktuellesVerfahren();
        if ( $verfahren === null )
            return "Zurzeit läuft kein Bieterverfahren!";
        $registerkarten = new SOLAWI_WidgetRegisterkarten( "verfahren" . $verfahren->getId() );
        for( $i=1; $i <= $verfahren->getAnzahlRunden(); $i++ )
            $registerkarten->add( "v" . $verfahren->getId() . "r" . $i, "Runde $i", $this->getHtmlFuerRunde( $verfahren, $i ), $i == $verfahren->getAktuelleRunde() );

        return $registerkarten->getHtml();
    }

    /**
     * Baut das HTML für eine Runde zusammen
     */
    private function getHtmlFuerRunde( SOLAWI_Bieterverfahren $verfahren, int $runde ) : string {
        $gebot = $this->getGebot( $verfahren, $runde );
        $bearbeitbar = $runde >= $verfahren->getAktuelleRunde();

        $result = "<form method='POST'>";
        $result .= "<input type='hidden' name='widget' value='" . get_class( $this ) . "'>";
        $result .= "<input type='hidden' name='runde' value='$runde'>";
		$result .= $this->getHtmlMitbauerAuswahl();
        $result .= "<table align='center' width='100%'><tr><th>Bereich</th><th>Anzahl<br>Anteile</th><th>Richtwert</th><th>Dein Gebot</th></tr>";

        $gesamtpreis = 0;
        foreach( SOLAWI_Bereich::values() as $bereich ) {
            $anzahl = $gebot->getAnzahl( $bereich );
            $richtwert = $anzahl * $verfahren->getRichtwert( $bereich );
            $preis = $gebot->getPreis( $bereich );
            $gesamtpreis += $preis;
            $result .= "<tr>";
            $result .= "<td>" . $bereich->getName() . "</td>";
            if ( $bearbeitbar ) {
                $id = "anzahl$runde" . $bereich->getDbName();
                $result .= "<td align='center'><select name='$id' id='$id' oninput='berechneRichtwert( $runde, \"" . $bereich->getDbName() . "\", " . $verfahren->getRichtwert( $bereich ) . " )'>";
                $result .= $this->getOptionHtml( 0, $anzahl );
                $result .= $this->getOptionHtml( 0.5, $anzahl );
                $result .= $this->getOptionHtml( 1, $anzahl );
                $result .= $this->getOptionHtml( 2, $anzahl );
                $result .= $this->getOptionHtml( 3, $anzahl );
                $result .= "<select></td>";
                
            } else {
                $result .= "<td align='right'>" . SOLAWI_formatAnzahl( $anzahl ) . "</td>";
            }
            if ( $bearbeitbar ) {
                $id = "richtwert$runde" . $bereich->getDbName();
                $result .= "<td align='center'><input type='text' style='width:60px' id='$id' value='" . SOLAWI_formatWaehrung( $richtwert, true ) . "' disabled></td>";
            } else {
                $result .= "<td align='right'>" . SOLAWI_formatWaehrung( $richtwert, true ) . "</td>";
            }
            if ( $bearbeitbar ) {
                $id = "preis$runde" . $bereich->getDbName();
                $result .= "<td align='center'><input type='number' step='1' min='0' max='500' id='$id' name='$id' value='$preis' oninput='berechneGesamtwert( $runde )'></td>";
            } else {
                $result .= "<td align='right'>" . SOLAWI_formatWaehrung( $preis, true ) . "</td>";
            }

            $result .= "</tr>";
        }
        $id = "gesamt$runde";
        $result .= "<tr><td colspan='3'><b>GESAMT</b></td>";
        if ( $bearbeitbar ) {
            $result .= "<td align='center'><input type='text' style='width:60px' id='$id' value='" . SOLAWI_formatWaehrung( $gesamtpreis, true ) . "' disabled></td>";
        } else {
            $result .= "<td align='right'>" . SOLAWI_formatWaehrung( $gesamtpreis, true ) . "</td>";
        }
        $result .= "</tr><tr><td colspan='4' align='center'><br>";
        if ( $bearbeitbar )
            $result .= call_user_func( $this->callbackSubmitButton, $runde );
        $result .= "</td></tr></table>";
        $result .= "</form>";
        return $result;
    }

	private function getHtmlMitbauerAuswahl() : string {
		if ( $this->isMitbauerAuswahl ) {
			$result = "Mitbauer:&nbsp;<select name='mitbauer' oninput='setzeMitbauerInWidgetErfassungGebote(this.value)'>";
			$result .= "<option value='-1'>Bitte wählen ...</option>";
			foreach ( SOLAWI_Mitbauer::values() as $mitbauer ) {
				$selected = $mitbauer === $this->mitbauer ? " selected" : "";
				$result .= "<option value='{$mitbauer->getId()}'$selected>{$mitbauer->getName()}</option>";
			}
			$result .= "</select><br>";
			return $result;
		}
		return "<input type='hidden' name='mitbauer' value='" . $this->mitbauer->getId() . "'>";
	}

    private function getOptionHtml( float $value, float $selectedValue ) : string {
        $selected = $value == $selectedValue ? " selected" : "";
        return "<option value='$value'$selected>" . SOLAWI_formatAnzahl( $value ) . "</option>";
    }

    /**
     * Gibt das Gebot für die übergebenen Parameter zurück
     */
    private function getGebot( SOLAWI_Bieterverfahren $verfahren, int $runde ) : SOLAWI_BieterverfahrenGebot {
		if ( $this->mitbauer == null )
			return new SOLAWI_BieterverfahrenGebot();
        // Prüfen, ob es Gebote in dieser oder den Vorrunden gibt
        for( $i=$runde; $i > 0; $i-- ) {
            $gebot = SOLAWI_BieterverfahrenGebot::values( $verfahren, $i, $this->mitbauer );
            if ( count( $gebot ) > 0 )
                return $gebot[0];
        }
        // Wenn es keine Gebote gibt, die aktuellen Ernteanteile und Preise ermitteln
        $ernteanteil = $this->mitbauer->getErnteanteilIntern( $verfahren->getStartDerSaison() );
        $gebot = new SOLAWI_BieterverfahrenGebot();
        foreach( SOLAWI_Bereich::values() as $bereich ) {
            $gebot->setAnzahl( $bereich, $ernteanteil->getAnzahl( $bereich ) );
            $gebot->setPreis( $bereich, $ernteanteil->getPreis( $bereich ) );
        }
        return $gebot;
    }
}