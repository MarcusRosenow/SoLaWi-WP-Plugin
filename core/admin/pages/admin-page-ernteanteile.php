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
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER ) )
			return new SOLAWI_SavePostdataResult( null, "Nicht authorisiert! Nicht gespeichert!" );
		$mitbauer = SOLAWI_Mitbauer::valueOf( $id );
		// jetzt müssen wir die Ernteanteile durchgehen ...
		$ernteanteile = $mitbauer->getErnteAnteile();
		foreach( $ernteanteile as $ernteanteil ) {
			$result = $this->updateErnteAnteil( $ernteanteil, $postData, SOLAWI_formatDatum( $ernteanteil->getGueltigAb(), false ) );
			if ( $result != null )
				return $result;
		}
		// jetzt wurde evtl. noch ein zukünftiger Ernteanteil erfasst
		$neu = null;
		if ( isset( $postData[ "anteilNeuDatum" ] ) && $postData[ "anteilNeuDatum" ] != "" ) {
			$neu = new SOLAWI_MitbauerErnteanteil( new DateTime( $postData[ "anteilNeuDatum" ] ) );
			$result = $this->updateErnteAnteil( $neu, $postData, "neu");
			if ( $result != null )
				return $result;
			$mitbauer->addErnteAnteil( $neu );
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
		SOLAWI_Repository::instance()->save( $mitbauer );
		return new SOLAWI_SavePostdataResult( "Erfolgreich gespeichert!", null, isset( $neu ) );
	}

	/**
	 * Aktualisiert den übergebenen Ernteanteil aus den übergebenen Postdaten mit dem übergebenen Schlüssel.
	 * 
	 * @return null, wenn alles ok ist, ansonsten ein kleiner Fehlertext.
	 */
	private function updateErnteAnteil( SOLAWI_MitbauerErnteanteil &$ernteanteil, array $postData, string $schluessel ) : SOLAWI_SavePostdataResult|null {
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
		$mitbauern = SOLAWI_Mitbauer::values();
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
	
	/**
	 * Gibt den Block für die Zusammenfassung aus
	 */
	private function baueZusammenfassungBlock( array $mitbauern, DateTime $stichtag ) : string {
		$verteiltag = SOLAWI_Verteiltag::createFromDatum( $stichtag );
		$anzahlen = [];
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$anzahlen[ $bereich->getDbName() ] = 0;
			foreach ( $mitbauern as $mitbauer ) {
				$anzahlen[ $bereich->getDbName() ] += $mitbauer->getErnteAnteil( $bereich, $stichtag );
			}
		}
		$anzahlAktive = 0;
		foreach ( $mitbauern as $mitbauer ) {
			$anzahlAktive += $mitbauer->hasErnteAnteile( null, $stichtag ) ? 1 : 0;
		}
		$result = "";
		if ( SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER ) )
			$result .= $this->getLinkFuerDrucken( $verteiltag );
		$result .= "<p>$anzahlAktive aktive Mitbauern</p><p>";
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$result .= "<p>" . $anzahlen[ $bereich->getDbName() ] . "x " . $bereich->getName();
			$result .= "&nbsp;(" . SOLAWI_Mitbauer::getHtmlSummierteAnteile( $mitbauern, $verteiltag, $bereich, ", " ) . ")</p>";
		}
		$result .= "</p>";
		return $result;
	}

	/**
	 * Ermittelt die Stichtage für die Zusammenfassung.
	 * null steht für heute.
	 */
	private function ermittleStichtage( array $mitbauern ) : array {
		$result = array( 0 => null );
		foreach ( $mitbauern as $mitbauer ) {
			$result = array_merge( $result, $mitbauer->getZukuenftigeStichtageFuerErnteAnteile() );
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
		$result .= "<h2>" . $mitbauer->getName() . "</h2>";
		$result .= "<p>" . $mitbauer->getEmailAsHtmlString() . "</p>";
		$nurLesen = !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER );
		$result .= $this->getHtmlFuerVerteilstation( $mitbauer, $nurLesen );
		$result .= $this->getHtmlFuerErnteAnteile( $mitbauer, $nurLesen );
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
	private function getHtmlFuerErnteAnteile( SOLAWI_Mitbauer $mitbauer, bool $nurLesen ) : string {
		$id = $mitbauer->getId();
		$widget = new SOLAWI_WidgetRegisterkarten( strval( $id ) );
		$widget->setStyle( SOLAWI_WidgetRegisterkarten::STYLE_BACKGROUND );
		$ernteanteile = $mitbauer->getErnteAnteile();
		foreach( $ernteanteile as $ernteanteil ) {
			$title = $ernteanteile[0] == $ernteanteil ? "Aktuell" : ( "Ab " . SOLAWI_formatDatum( $ernteanteil->getGueltigAb() ) );
			$body = $this->getHtmlFuerErnteAnteil( $mitbauer, $ernteanteil, $nurLesen );
			$widget->add( "regkarte-$id-" . SOLAWI_formatDatum( $ernteanteil->getGueltigAb() ), $title, $body );
		}
		if ( !$nurLesen )
			$widget->add( "regkarte-$id-neu", "+", $this->getHtmlFuerErnteAnteil( $mitbauer, null, false ) );
		return $widget->getHtml();
	}

	/**
	 * Baut den Block für die Eingabe/Ausgabe der Ernteanteile und Preise für einen konkreten Ernteanteil
	 * 
	 * @param ernteanteil null für einen neu-Block
	 */
	private function getHtmlFuerErnteAnteil( SOLAWI_Mitbauer $mitbauer, SOLAWI_MitbauerErnteanteil|null $ernteanteil, bool $nurLesen ) : string {
		// Beim neu-Block muss das Datum angegeben werden
		$result = "";
		if ( $ernteanteil == null && !$nurLesen ) {
			$onInput = $this->getOnInputEventHtml( $mitbauer->getId() );
			$result .= "Gültig ab: <input type='date' name='anteilNeuDatum' $onInput/><br>";
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
				$layout->add( null, $bereich->getName() );
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
