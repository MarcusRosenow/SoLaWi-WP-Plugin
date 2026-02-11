<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Für die Initialisierung des Backends für die Ernteanteile
 */
final class SOLAWI_AdminPageErnteanteile extends SOLAWI_AbstractAdminPage {
	
	public function getTitel() : string {
		return "Ernteanteile";
	}
	
	public function savePostdata( int $id, array $postData ) : SOLAWI_SavePostdataResult {
		if ( $id == 99999 && isset( $postData[ "import" ] ) && isset( $postData[ "start" ] ) ) {
			// importieren von Ernteanteilen
			if ( $postData[ "start" ] == "" )
				return new SOLAWI_SavePostdataResult( null, "Startdatum fehlt!", false );
			$result = $this->import( new DateTime( $postData[ "start" ] ), $postData[ "import" ] );
			return new SOLAWI_SavePostdataResult( $result == null ? "Erfolgreich importiert!" : null, $result, $result == null );
		}

		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER ) )
			return new SOLAWI_SavePostdataResult( null, "Nicht authorisiert! Nicht gespeichert!" );
		$mitbauer = SOLAWI_Mitbauer::valueOf( $id );
		// jetzt müssen wir die Ernteanteile durchgehen ...
		$ernteanteile = $mitbauer->getErnteanteile();
		foreach( $ernteanteile as $ernteanteil ) {
			$result = $this->updateErnteanteil( $ernteanteil, $postData, SOLAWI_formatDatum( $ernteanteil->getGueltigAb(), false ) );
			if ( $result != null )
				return $result;
			$mitbauer->updateErnteanteil( $ernteanteil );
		}
		// jetzt wurde evtl. noch ein zukünftiger Ernteanteil erfasst
		$neu = null;
		if ( isset( $postData[ "anteilNeuDatum" ] ) && $postData[ "anteilNeuDatum" ] != "" ) {
			$neu = new SOLAWI_MitbauerErnteanteil( new DateTime( $postData[ "anteilNeuDatum" ] ) );
			$result = $this->updateErnteanteil( $neu, $postData, "neu");
			if ( $result != null )
				return $result;
			$mitbauer->addErnteanteil( $neu );
		} else {
			// Kein neues Datum angegeben, aber vielleicht neue Ernteanteile eingetragen?
			foreach ( SOLAWI_Bereich::values() as $bereich ) {
				if ( $postData[ "anteil" . $bereich->getDbName() . "neu" ] > 0 ) {
					return new SOLAWI_SavePostdataResult( null, "Kein 'Gültig ab' für die neuen Ernteanteile eingegeben. Nicht gespeichert!" );
				}
			}
		}

		$station = SOLAWI_Verteilstation::valueOf( intval( $postData[ "verteilstation" ] ) );
		$mitbauer->setVerteilstation( $station );
		return new SOLAWI_SavePostdataResult( "Erfolgreich gespeichert!", null, isset( $neu ) );
	}

	/**
	 * Importiert die Ernteanteile als csv
	 */
	private function import( DateTime $start, string $import ) : string|null {
		$result = "";
		$i = 0;
		foreach ( explode( "\n", $import ) as $zeile ) {
			$i++;
			$zellen = explode( ';', $zeile );
			if ( count( $zellen ) != 7) {
				$result .= "Zeile $i: 6 Semikolons erwartet, aber nicht gefunden<br>";
				continue;
			}
			$matches = null;
    		preg_match_all('/[,\" \t\'€]/i', $zeile, $matches);
			if ( count($matches[0]) > 0 ) {
				$result .= "Zeile $i: [, \"'€] sind verboten!<br>";
				continue;
			}
			if ( !SOLAWI_isGueltigerAnteil( $zellen[1] ) || !SOLAWI_isGueltigerAnteil( $zellen[3] ) || !SOLAWI_isGueltigerAnteil( $zellen[5] )
				|| !SOLAWI_isGueltigerPreis( $zellen[2] ) || !SOLAWI_isGueltigerPreis( $zellen[4] ) || !SOLAWI_isGueltigerPreis( $zellen[6] ) ) {
				$result .= "Zeile $i: Ernteanteile syntaktisch falsch<br>";
				continue;
			}
			$email = $zellen[0];
			$anzahlFleisch = floatval( $zellen[1] );
			$preisFleisch = floatval( $zellen[2] );
			$anzahlMoPro = floatval( $zellen[3] );
			$preisMoPro = floatval( $zellen[4] );
			$anzahlGemuese = floatval( $zellen[5] );
			$preisGemuese = floatval( $zellen[6] );

			$mitbauer = SOLAWI_Mitbauer::valueOfEmail( $email );
			if ( $mitbauer == null ) {
				$result .= "Zeile $i: Kein Mitbauer zu E-Mail $email gefunden<br>";
				continue;
			}
			if ( $anzahlFleisch == 0 && $preisFleisch != 0 || $anzahlFleisch != 0 && $preisFleisch == 0
					|| $anzahlMoPro == 0 && $preisMoPro != 0 || $anzahlMoPro != 0 && $preisMoPro == 0
					|| $anzahlGemuese == 0 && $preisGemuese != 0 || $anzahlGemuese != 0 && $preisGemuese == 0 ) {
				$result .= "Zeile $i: Ernteanteile unplausibel<br>";
				continue;
			}
			
			$ernteanteil = $mitbauer->getErnteanteilIntern( $start, true );
			$neuerAnteil = $ernteanteil == null;
			if ( $neuerAnteil )
				$ernteanteil = new SOLAWI_MitbauerErnteanteil( $start );
			$ernteanteil->setAnzahl( SOLAWI_Bereich::FLEISCH, $anzahlFleisch );
			$ernteanteil->setPreis( SOLAWI_Bereich::FLEISCH, $preisFleisch );
			$ernteanteil->setAnzahl( SOLAWI_Bereich::MOPRO, $anzahlMoPro );
			$ernteanteil->setPreis( SOLAWI_Bereich::MOPRO, $preisMoPro );
			$ernteanteil->setAnzahl( SOLAWI_Bereich::GEMUESE, $anzahlGemuese );
			$ernteanteil->setPreis( SOLAWI_Bereich::GEMUESE, $preisGemuese );
			if ( $neuerAnteil )
				$mitbauer->addErnteanteil( $ernteanteil );
			else
				$mitbauer->updateErnteanteil( $ernteanteil );
		}
		if ( $result == "" )
			return null;
		return $result;
	}

	/**
	 * Aktualisiert den übergebenen Ernteanteil aus den übergebenen Postdaten mit dem übergebenen Schlüssel.
	 * 
	 * @return null, wenn alles ok ist, ansonsten ein kleiner Fehlertext.
	 */
	private function updateErnteanteil( SOLAWI_MitbauerErnteanteil &$ernteanteil, array $postData, string $schluessel ) : SOLAWI_SavePostdataResult|null {
		$neu = new SOLAWI_MitbauerErnteanteil( new DateTime( $postData[ "anteilNeuDatum" ] ) );
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$bereich_anteil = $postData[ "anteil" . $bereich->getDbName() . $schluessel ];
			$bereich_preis = $postData[ "preis" . $bereich->getDbName() . $schluessel ];
			if ( !SOLAWI_isGueltigerAnteil( $bereich_anteil ) ) {
				return new SOLAWI_SavePostdataResult( null, "Ungültigen Anteil eingegeben: '$bereich_anteil'. Nicht gespeichert!" );
			}
			if ( !SOLAWI_isGueltigerPreis( $bereich_preis ) ) {
				return new SOLAWI_SavePostdataResult( null, "Ungültigen Preis eingegeben: '$bereich_preis'. Nicht gespeichert!" );
			}
			$ernteanteil->setAnzahl( $bereich, floatval( $bereich_anteil ) );
			$ernteanteil->setPreis( $bereich, floatval( $bereich_preis ) );
		}
		return null;
	}
	
	/**
	 * Gibt die Liste der Ernteanteile für alle Mitbauern aus.
	 */
	protected function initInhalt() : void {
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			return;
		$mitbauern = SOLAWI_Mitbauer::values( null, true );
		$stichtage = $this->ermittleStichtage( $mitbauern );
		$widget = new SOLAWI_WidgetRegisterkarten( "Zusammenfassung" );
		$widget->setStyle( SOLAWI_WidgetRegisterkarten::STYLE_BACKGROUND );
		foreach( $stichtage as $stichtag ) {
			$stichtagFormatiert = $stichtag == null ? "Aktuell" : "Ab " . SOLAWI_formatDatum( $stichtag );
			$stichtag = $stichtag == null ? new DateTime() : $stichtag;
			$widget->add( "regkarte-zusammenfassung-" . $stichtagFormatiert, $stichtagFormatiert, $this->baueZusammenfassungBlock( $mitbauern, $stichtag ) );
		}
		$zusammenfassungsKachel = "<h2>Zusammenfassung</h2>";
		$zusammenfassungsKachel .= $widget->getHtml();
		$this->addKachel( "Zusammenfassung", $zusammenfassungsKachel , false );
		foreach ( $mitbauern as $mitbauer )
			$this->addKachel( $mitbauer->getName() . " " . $mitbauer->getEmail(), $this->baueMitbauerBlock( $mitbauer ) );
	}
	
	protected function getEndeHtml() : string {
		if ( SOLAWI_hasRolle( SOLAWI_Rolle::ADMINISTRATOR ) ) {
			$result = "Daten importieren:<br>(Email;Anz.Fleisch;Preis Fleisch;Anz. MoPro;Preis MoPro;Anz. Gemüse;Preis Gemüse)";
			$result .= "<form method='POST'>Gültig ab: <input type='date' name='start'/><br>";
			$result .= "Daten: <textarea name='import'></textarea><br>" . $this->getSubmitButtonHtml( 99999, true, "Importieren", false ) . "</form>";
			return $result;
		}
		return "";
	}

	/**
	 * Gibt den Block für die Zusammenfassung aus
	 */
	private function baueZusammenfassungBlock( array $mitbauern, DateTime $stichtag ) : string {
		$verteiltag = SOLAWI_Verteiltag::createFromDatum( $stichtag );
		$anzahlen = [];
		$budget = [];
		$budget[ "gesamt" ] = 0;
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$anzahlen[ $bereich->getDbName() ] = 0;
			$budget[ $bereich->getDbName() ] = 0;
			foreach ( $mitbauern as $mitbauer ) {
				$ea = $mitbauer->getErnteanteilIntern( $stichtag );
				if ( $ea != null ) {
					$anzahlen[ $bereich->getDbName() ] += $ea->getAnzahl( $bereich );
					$budget[ $bereich->getDbName() ] += $ea->getPreis( $bereich );
					$budget[ "gesamt" ] += $ea->getPreis( $bereich );
				}
			}
		}
		$anzahlAktive = 0;
		foreach ( $mitbauern as $mitbauer ) {
			$anzahlAktive += $mitbauer->hasErnteanteile( null, $stichtag ) ? 1 : 0;
		}
		$result = "";
		if ( SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER ) )
			$result .= $this->getLinkFuerDrucken( $verteiltag );
		$result .= "<p>$anzahlAktive aktive Mitbauern";
		$result .= "<br>" . SOLAWI_formatWaehrung( $budget[ "gesamt" ], true ) . "/Monat";
		$result .= "&nbsp;=&nbsp;" . SOLAWI_formatWaehrung( $budget[ "gesamt" ] * 12, true ) . "/Jahr";
		$result .= "</p>";
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$result .= "<p>" . $anzahlen[ $bereich->getDbName() ] . "x&nbsp;" . $bereich->getName();
			$summiert = SOLAWI_Mitbauer::getHtmlSummierteAnteile( $mitbauern, $verteiltag, $bereich, ", " );
			if ( $summiert != '' )
				$result .= "&nbsp;($summiert)";
			$result .= "<br>" . SOLAWI_formatWaehrung( $budget[ $bereich->getDbName() ], true ) . "/Monat";
			$result .= "&nbsp;=&nbsp;" . SOLAWI_formatWaehrung( $budget[ $bereich->getDbName() ] * 12, true ) . "/Jahr";
			$result .= "</p>";
		}
		return $result;
	}

	/**
	 * Ermittelt die Stichtage für die Zusammenfassung.
	 * null steht für heute.
	 */
	private function ermittleStichtage( array $mitbauern ) : array {
		$result = array( 0 => null );
		foreach ( $mitbauern as $mitbauer ) {
			$result = array_merge( $result, $mitbauer->getZukuenftigeStichtageFuerErnteanteile() );
		}
		$result = array_unique( $result, SORT_REGULAR );
		sort( $result );
		return $result;
	}

	/**
	 * Gibt eine Zeile für einen Mitbauern aus.
	 */
	private function baueMitbauerBlock( SOLAWI_Mitbauer $mitbauer ) : string {
		$result = "<form method='POST'>";
		$mail = $mitbauer->getEmailAsHtmlString( true );
		$phone = $mitbauer->getTelefonnummerAsHtmlString( true );
		$result .= "<h2>" . $mitbauer->getName() . " $mail $phone</h2>";
		$nurLesen = !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER );
		$result .= $this->getHtmlFuerVerteilstation( $mitbauer, $nurLesen );
		$result .= $this->getHtmlFuerErnteanteile( $mitbauer, $nurLesen );
		if ( !$nurLesen )
			$result .= $this->getSubmitButtonHtml( $mitbauer->getId() );
		$result .= "</form>";
		return $result;
	}

	private function getHtmlFuerVerteilstation( SOLAWI_Mitbauer $mitbauer, bool $nurLesen ) : string {
		$result = "<p>Verteilstation: ";
		if ( $nurLesen ) {
			$result .= $mitbauer->getVerteilstation()->getName();
		} else {
			$onInput = $this->getOnInputEventHtml( $mitbauer->getId() );
			$result = "<p>Verteilstation: <select name='verteilstation' $onInput>";
			foreach ( SOLAWI_Verteilstation::values() as $station ) {
				$selected = "";
				if ( $station === $mitbauer->getVerteilstation() )
					$selected = " selected";
				$result .= "<option value='{$station->getId()}'$selected>{$station->getName()}</option>";
			}
			$result .= "</select>";
		}
		return $result . "</p>";
	}

	/**
	 * Gibt die Registerkarten mit den Ernteanteilen aus
	 */
	private function getHtmlFuerErnteanteile( SOLAWI_Mitbauer $mitbauer, bool $nurLesen ) : string {
		$id = $mitbauer->getId();
		$widget = new SOLAWI_WidgetRegisterkarten( strval( $id ) );
		$widget->setStyle( SOLAWI_WidgetRegisterkarten::STYLE_BACKGROUND );
		foreach( $mitbauer->getErnteanteile() as $ernteanteil ) {
			$title =  $ernteanteil->getGueltigAb() < new DateTime() ? "Aktuell" : ( "Ab " . SOLAWI_formatDatum( $ernteanteil->getGueltigAb() ) );
			$body = $this->getHtmlFuerErnteanteil( $mitbauer, $ernteanteil, $nurLesen );
			$widget->add( "regkarte-$id-" . SOLAWI_formatDatum( $ernteanteil->getGueltigAb() ), $title, $body );
		}
		if ( !$nurLesen )
			$widget->add( "regkarte-$id-neu", "+", $this->getHtmlFuerErnteanteil( $mitbauer, null, false ) );
		return $widget->getHtml();
	}

	/**
	 * Baut den Block für die Eingabe/Ausgabe der Ernteanteile und Preise für einen konkreten Ernteanteil
	 * 
	 * @param ernteanteil null für einen neu-Block
	 */
	private function getHtmlFuerErnteanteil( SOLAWI_Mitbauer $mitbauer, SOLAWI_MitbauerErnteanteil|null $ernteanteil, bool $nurLesen ) : string {
		// Beim neu-Block muss das Datum angegeben werden
		$result = "";
		if ( $ernteanteil == null && !$nurLesen ) {
			$onInput = $this->getOnInputEventHtml( $mitbauer->getId() );
			$result .= "Ab: <input type='date' name='anteilNeuDatum' $onInput/><br>";
		}
		$key = $ernteanteil == null ? "neu" : SOLAWI_formatDatum( $ernteanteil->getGueltigAb(), false );
		$layout = new SOLAWI_WidgetKachellayout( $nurLesen ? 1 : 3 );
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$bereich_anteil = $ernteanteil == null ? 0 : $ernteanteil->getAnzahl( $bereich );
			// Der Anteil
			if ( $nurLesen ) {
				$layout->add( null, $bereich_anteil . "&nbsp;" . $bereich->getName() );
			} else {
				$bereich_slug = $bereich->getDbName();
				$bereich_preis = $ernteanteil == null ? 0 : $ernteanteil->getPreis( $bereich );
				// damit beim JavaScript alles läuft
				$htmlFieldId = "anteil" . $mitbauer->getId() . $bereich_slug . $key;
				$onInput = $this->getOnInputEventHtml( $mitbauer->getId(), "anteilChanged(\"$htmlFieldId\")" );
				// damit beim Senden der Daten alles läuft
				$htmlFieldName = "anteil$bereich_slug" . $key;
				$layout->add( null, "<input type='number' id='$htmlFieldId' name='$htmlFieldName' step='0.5' min='0' max='10' value='$bereich_anteil' $onInput/>" );
				$layout->add( null, $bereich->getShortName() );
				// Der Preis
				$htmlFieldName = "preis$bereich_slug" . $key;
				$onInput = $this->getOnInputEventHtml( $mitbauer->getId() );
				$layout->add( null, "<input type='text' name='$htmlFieldName' value='$bereich_preis' $onInput style='width:50px'/>&nbsp;&euro;" );
			}
		}
		return $result . $layout->getHtml();
	}

	/**
	 * Baut den Link für das Drucken der Ernteanteile zusammen
	 */
	private function getLinkFuerDrucken( SOLAWI_Verteiltag $stichtag ) : string {
		$url = ( new SOLAWI_AdminPrintErnteanteile() )->getUrl( array(
			"datum" => $stichtag->getId()
		) );
		return "<a href='$url' target='_blank'>Druckversion</a>";
	}
}
