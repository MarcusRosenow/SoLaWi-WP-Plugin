<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Für die Initialisierung des Backends für das Bieterverfahren
 */
final class SOLAWI_AdminPageBieterverfahren extends SOLAWI_AbstractAdminPage {

	const NEUES_BIETERVERFAHREN_ID = 999998;

	const NEUES_GEBOT_ID = 999999;

	const ACTION_RUNDE_SCHLIESSEN = "rundeSchliessen";

	const ACTION_VERFAHREN_SCHLIESSEN = "verfahrenSchliessen";

	const ACTION_GEBOTE_UEBERTRAGEN = "geboteUebertragen";

	public function getTitel() : string {
		return "Bieterverfahren";
	}

	public function getAnzahlSpalten() : int {
		return 2;
	}
	
	public function savePostdata( int $id, array $postData ) : SOLAWI_SavePostdataResult {
		if ( SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
		// Neues Bieterverfahren
		if ( $id == self::NEUES_BIETERVERFAHREN_ID ) {
			if ( SOLAWI_Bieterverfahren::isBieterverfahrenAktiv() )
				return new SOLAWI_SavePostdataResult( null, "Ein neues Verfahren kann nur gestartet werden, wenn alle Verfahren beendet sind." );
			if ( !SOLAWI_isGueltigerPreis( $postData[ "gesamtBudget" ] ) )
				return new SOLAWI_SavePostdataResult( null, "Kein gültiges Budget angegeben" );
			if ( !isset( $postData[ "startDerSaison" ] ) || $postData[ "startDerSaison" ] == "" )
				return new SOLAWI_SavePostdataResult( null, "Kein gültiges Datum für den Start der Saison angegeben." );
			$entity = new SOLAWI_Bieterverfahren();
			$entity->setGesamtbudget( floatval( $postData[ "gesamtBudget" ] ) );
			$entity->setAnzahlRunden( intval( $postData[ "anzahlRunden" ] ) );
			$entity->setStartDerSaison( new DateTime( $postData[ "startDerSaison" ] ) );
			foreach( SOLAWI_Bereich::values() as $bereich ) {
				if ( !SOLAWI_isGueltigerAnteil( $postData[ "planAnteile" . $bereich->getDbName() ] ) )
					return new SOLAWI_SavePostdataResult( null, "Kein gültiger Planwert für Anteile " . $bereich->getName() );
				if ( !SOLAWI_isGueltigerPreis( $postData[ "richtwert" . $bereich->getDbName() ] ) )
					return new SOLAWI_SavePostdataResult( null, "Kein gültiger Richtwert für " . $bereich->getName() );
				$entity->setPlanVergebeneAnteile( $bereich, floatval( $postData[ "planAnteile" . $bereich->getDbName() ] ) );
				$entity->setRichtwert( $bereich, floatval( $postData[ "richtwert" . $bereich->getDbName() ] ) );
			}
			SOLAWI_Repository::instance()->save( $entity );
			return new SOLAWI_SavePostdataResult( null, null, true );
		
		// Neues Gebot
		} else if ( SOLAWI_WidgetErfassungGebote::isPostdataVonDiesemWidget( $postData ) ) {
			$gebot = SOLAWI_WidgetErfassunggebote::konvertierePostdataZuGebot( $postData );
        	if ( is_string( $gebot ) ) {
				return new SOLAWI_SavePostdataResult( null, $gebot, false ); 
    	    } else {
        	    SOLAWI_Repository::instance()->save( $gebot );
				return new SOLAWI_SavePostdataResult( "Erfolgreich gespeichert.", null, false ); 
        	}

		} else {
			// Bestehendes Bieterverfahren
			$verfahren = SOLAWI_Bieterverfahren::valueOf( $id );
			if ( !isset( $postData[ "aktion" ] ) )
				return new SOLAWI_SavePostdataResult( null, "Keine Aktion angegeben." );

			if ( $postData[ "aktion" ] == self::ACTION_RUNDE_SCHLIESSEN ) {
				if ( $verfahren->getGueltigBis() !== null )
					return new SOLAWI_SavePostdataResult( null, "Kein aktives Bieterverfahren mit der übergebenen ID." );
				if ( $verfahren->getAktuelleRunde() == 0 )
					return new SOLAWI_SavePostdataResult( null, "Runde bereits geschlossen.", true );
				$verfahren->setLetzteRunde( $verfahren->getAktuelleRunde() );
				if ( $verfahren->getAktuelleRunde() < $verfahren->getAnzahlRunden() )
					$verfahren->setAktuelleRunde( $verfahren->getAktuelleRunde() + 1 );
				else
					$verfahren->setAktuelleRunde( 0 );
				SOLAWI_Repository::instance()->save( $verfahren );
				return new SOLAWI_SavePostdataResult( "Runde geschlossen!", null, true );

			} elseif ( $postData[ "aktion" ] == self::ACTION_VERFAHREN_SCHLIESSEN ) {
				if ( $verfahren->getGueltigBis() !== null )
					return new SOLAWI_SavePostdataResult( null, "Kein aktives Bieterverfahren mit der übergebenen ID." );
				$verfahren->setLetzteRunde( $verfahren->getAktuelleRunde() );
				$verfahren->setAktuelleRunde( 0 );
				$verfahren->setGueltigBis( new DateTime() );
				SOLAWI_Repository::instance()->save( $verfahren );
				return new SOLAWI_SavePostdataResult( "Verfahren beendet", null, true );

			} elseif ( $postData[ "aktion" ] == self::ACTION_GEBOTE_UEBERTRAGEN ) {
				if ( $verfahren->getGueltigBis() == null )
					return new SOLAWI_SavePostdataResult( null, "Erst das Verfahren schließen!", true );
				if ( $verfahren->getStartDerSaison() < new DateTime() )
					return new SOLAWI_SavePostdataResult( null, "Kann nicht mehr übertragen werden.", true );
				$runde = $verfahren->getLetzteRunde();
				$datum = $verfahren->getStartDerSaison();
				foreach( SOLAWI_Mitbauer::values() as $mitbauer ) {
					$gebot = $verfahren->getGebot( $runde, $mitbauer );
					$ernteanteileBisher = $mitbauer->getErnteanteilIntern( $datum, false );
					$ernteanteileNeu = $mitbauer->getErnteanteilIntern( $datum, true );
					foreach( SOLAWI_Bereich::values() as $bereich ) {
						if ( $gebot != null ) {
							$ernteanteileNeu->setAnzahl( $bereich, $gebot->getAnzahl( $bereich ) );
							$ernteanteileNeu->setPreis( $bereich, $gebot->getPreis( $bereich ) );
						} else {
							$anzahl = $ernteanteileBisher->getAnzahl( $bereich );
							$alterPreis = $ernteanteileBisher->getPreis( $bereich );
							$neuerPreis = $anzahl * $verfahren->getDurchschnittsgebot( $runde, $bereich );
							$ernteanteileNeu->setAnzahl( $bereich, $anzahl );
							$ernteanteileNeu->setPreis( $bereich, max( $alterPreis, $neuerPreis ) );
						}
					}
					SOLAWI_Repository::instance()->save( $mitbauer );
				}
				return new SOLAWI_SavePostdataResult( "Anteile erfolgreich übertragen!", null, false );

			} else {
				return new SOLAWI_SavePostdataResult( null, "Aktion " . $postData[ "aktion" ] . " nicht unterstützt");
			}
		}
	}

	/**
	 * Beim Bieterverfahren gibt es keinen Filter.
	 */
	protected function getFilterHtml() : string {
		return "";
	}
	
	/**
	 * Initialisiert den Inhalt der Seite
	 */
	protected function initInhalt() : void {
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			return;
		$verfahren = SOLAWI_Bieterverfahren::values();
		if ( !SOLAWI_Bieterverfahren::isBieterverfahrenAktiv() )
			$this->addKachel( "Neues Bieterverfahren", $this->getHtmlNeuesVerfahren(), false );
		foreach( $verfahren as $v ) {
			$this->addKachel( $v->getId(), $this->getHtmlVerfahren( $v ) );
		}
	}

	/**
	 * Baut das HTML für ein neues Bieterverfahren zusammen.
	 */
	private function getHtmlNeuesVerfahren() : string {
		$vorherigesVerfahren = SOLAWI_Bieterverfahren::values();
		if ( count( $vorherigesVerfahren ) > 0 ) {
			$vorherigesVerfahren = $vorherigesVerfahren[0];
		} else {
			$vorherigesVerfahren = new SOLAWI_Bieterverfahren();
		}
		$result = "<h2>Neues Bieterverfahren starten</h2>";
		$result .= "<form action='POST'>";
		$result .= "Durch die Mitbauern zu erreichendes Gesamtbudget: <input name='gesamtBudget' type='number' step='1000' min='1000' max='1000000' value='" . $vorherigesVerfahren->getGesamtbudget() . "'/><br>";
		$result .= "Maximale Anzahl Runden: <input name='anzahlRunden' type='number' step='1' min='1' max='5' value='" . $vorherigesVerfahren->getAnzahlRunden() . "'/><br>";
		$start = $vorherigesVerfahren->getStartDerSaison();
		$result .= "Start des Wirtschaftsjahres: <input name='startDerSaison' type='date' value='" . SOLAWI_formatDatum( $start < new DateTime() ? $start->add( new DateInterval( "P1Y" ) ) : $start, true ) . "'/><br>";
		$kacheln = new SOLAWI_WidgetKachellayout( count( SOLAWI_Bereich::values() ) + 1 );
		$kacheln->add( null, "" );
		foreach( SOLAWI_Bereich::values() as $bereich ) {
			$kacheln->add( null, "<h3>" . $bereich->getName() . "</h3>" );
		}
		$kacheln->add( null, "Geplante Anteile für dieses Jahr:" );
		foreach( SOLAWI_Bereich::values() as $bereich ) {
			$kacheln->add( null, "<input name='planAnteile" . $bereich->getDbName() . "' type='number' step='1' min='1' max='999' value='" . $vorherigesVerfahren->getPlanVergebeneAnteile( $bereich ) . "'/>" );
		}
		$kacheln->add( null, "Richtwert pro Ernteanteil:" );
		foreach( SOLAWI_Bereich::values() as $bereich ) {
			$kacheln->add( null, "<input name='richtwert" . $bereich->getDbName() . "' type='number' min='1' max='999' value='" . $vorherigesVerfahren->getRichtwert( $bereich ) . "'/>" );
		}
		$result .= $kacheln->getHtml();
		$result .= "<br><br>";
		$result .= $this->getSubmitButtonHtml( self::NEUES_BIETERVERFAHREN_ID, true, "Bieterverfahren eröffnen", false );
		$result .= "</form>";
		return $result;
	}

	/**
	 * Baut das HTML für ein Bieterverfahren zusammen.
	 */
	private function getHtmlVerfahren( SOLAWI_Bieterverfahren $verfahren ) : string {
		$isOffen = $verfahren->getGueltigBis() == null;
		$result = "<h2>" . ( $isOffen ? "Aktuelles " : "" ) . "Bieterverfahren vom " . SOLAWI_formatDatum( $verfahren->getGueltigAb() ) . ( !$isOffen ? " bis " . SOLAWI_formatDatum( $verfahren->getGueltigBis() ) : "" ) . "</h2>";
		$result .= "<b>Gesamtbudget: " . SOLAWI_formatWaehrung( $verfahren->getGesamtbudget(), true ) . "</b><br>";
		$result .= "Start des Wirtschaftsjahres: " . SOLAWI_formatDatum( $verfahren->getStartDerSaison() ) . "<br><br>";

		$registerkarten = new SOLAWI_WidgetRegisterkarten( $verfahren->getId() );
		$registerkarten->setStyle( SOLAWI_WidgetRegisterkarten::STYLE_BACKGROUND );

		$selektierteRunde = $verfahren->getAktuelleRunde() == 0 ? $verfahren->getLetzteRunde() : $verfahren->getAktuelleRunde();
		$anzahlRunden = $isOffen ? $verfahren->getAnzahlRunden() : $verfahren->getLetzteRunde();
		if ( $isOffen )
			$registerkarten->add( $verfahren->getId() . "_0", "Gebot erfassen", $this->getHtmlGebotErfassen( $selektierteRunde ) );
		for( $i=1; $i <= $anzahlRunden; $i++ ) {
			$registerkarten->add( $verfahren->getId() . "_" . $i, "Runde $i", $this->getHtmlTabelle( $verfahren, $i ), $i == $selektierteRunde );
		}
		$result .= $registerkarten->getHtml();
		$id = $verfahren->getId();
		$onInput = "oninput='solawi_changed($id)'";
		$result .= "<form action='POST'>";
		$result .= "Aktion durchführen:<select name='aktion' $onInput><option>Bitte wählen ...</option>";
		if ( $isOffen ) {
			if ( $verfahren->getAktuelleRunde() < $verfahren->getAnzahlRunden() )
				$result .= "<option value='" . self::ACTION_RUNDE_SCHLIESSEN . "'>Bieterrunde " . $verfahren->getAktuelleRunde() . " schließen</option>";
			$result .= "<option value='" . self::ACTION_VERFAHREN_SCHLIESSEN . "'>Bieterverfahren abschließen</option>";
		} else if ( $verfahren->getStartDerSaison() >= new DateTime() && SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER ) ) {
			$result .= "<option value='" . self::ACTION_GEBOTE_UEBERTRAGEN . "'>Gebote der Runde " . $verfahren->getLetzteRunde() . " auf die Ernteanteile ab " . SOLAWI_formatDatum( $verfahren->getStartDerSaison() ) . " übertragen</option>";
		}
		$result .= "</select>";
		$result .= $this->getSubmitButtonHtml( $verfahren->getId(), false, "Aktion ausführen", false );
		$result .= "</form>";
		return $result;
	}

	/**
	 * Gibt das HTML für das Erfassen eines Gebotes im Backend zurück.
	 */
	private function getHtmlGebotErfassen( int $runde ) : string {
		$widget = new SOLAWI_WidgetErfassunggebote( true, array( $this, "getSubmitButton" ) );
        return $widget->getHtml();
	}

    public function getSubmitButton( int $runde ) : string {
        return $this->getSubmitButtonHtml( self::NEUES_GEBOT_ID . $runde, true, "Gebot abgeben", true );
    }

	/**
	 * Gibt die große Tabelle mit den Geboten aus.
	 */
	private function getHtmlTabelle( SOLAWI_Bieterverfahren $verfahren, int $runde ) : string {
		$gebote = $verfahren->getGebote( $runde );
		$result = "<table cellspacing='5' cellpadding='5'>";
        $result .= "<tr>
		      <th>Bereich</th>
		      <th>Geplante<br>Anteile</th>
		      <th>Richtwert</th>
		      <th>Gebotene<br>Anteile</th>
			  <th>Durchschnittl.<br>Gebot</th>
			  <th>Erträge gesamt<br>pro Jahr</th>
			  </tr>";
		$summeGesamt = 0;
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$vergebeneAnteile = 0;
			$geboteGesamt = 0;
			foreach( $gebote as $gebot ) {
				$vergebeneAnteile += $gebot->getAnzahl( $bereich );
				$geboteGesamt += $gebot->getPreis( $bereich );
			}
			$richtwertBereich = $verfahren->getRichtwert( $bereich );
			$geboteDurchschnitt = $verfahren->getDurchschnittsgebot( $runde, $bereich );

			$planVergebeneAnteileBereich = $verfahren->getPlanVergebeneAnteile( $bereich );
			$vergebeneAnteile = round( $vergebeneAnteile, 1 );
			$restlicheAnteile = $planVergebeneAnteileBereich-$vergebeneAnteile;
			$summeGesamt += ($geboteGesamt + $geboteDurchschnitt * $restlicheAnteile) * 12;
			$result .= "<tr>
			<td>{$bereich->getName()}</td>
			<td align='right'>$planVergebeneAnteileBereich</td>
			<td align='right'>".SOLAWI_formatWaehrung($richtwertBereich,true)."</td>
			<td align='right'>$vergebeneAnteile</td>
			<td align='right'>".SOLAWI_formatWaehrung($geboteDurchschnitt,true)."</td>
			<td align='right'>".SOLAWI_formatWaehrung(($geboteGesamt + $geboteDurchschnitt * $restlicheAnteile)*12,true)."</td>
			</tr>";
		}
		$differenz = $summeGesamt - $verfahren->getGesamtbudget();
		$differenz = "<span style=\"color:".($differenz<0?"red":"green")."\">".($differenz>0?'+':'').SOLAWI_formatWaehrung($differenz,true)."</span>";
		$result .= "<tr class=\"Summe\">
			  <td align='right' colspan='6'><b>".SOLAWI_formatWaehrung($summeGesamt,true)."<br>($differenz)</b></td>
			  </tr>";
		$result .= "</table>";
		$result .= "<b><a href='javascript:location.reload()'>Seite aktualisieren</a></b><br>";
		if ( SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER ) )
			$result .= $this->getLinkFuerUebersicht( $verfahren, $runde );
		return $result;
    }

	private function getLinkFuerUebersicht( SOLAWI_Bieterverfahren $verfahren, int $runde ) : string {
		$url = ( new SOLAWI_AdminPrintBieterverfahren() )->getUrl( array(
			"verfahren" => $verfahren->getId(),
			"runde" => $runde
		) );
		return "<a href='$url' target='_blank'>Einzelgebote Runde $runde</a>";
	}
}
