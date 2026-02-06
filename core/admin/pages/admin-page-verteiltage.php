<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Für die Initialisierung des Backends für die Verteiltage
 */
final class SOLAWI_AdminPageVerteiltage extends SOLAWI_AbstractAdminPage {

	public function getTitel() : string {
		return "Verteiltage";
	}

	/**
	 * Gibt die Anzahl der Spalten für das Layout zurück
	 */
	public function getAnzahlSpalten() : int {
		return 3;
	}
	
	public function savePostdata( int $id, array $postData ) : SOLAWI_SavePostdataResult {
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::BAUER, SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			return new SOLAWI_SavePostdataResult( null, "Nicht authorisiert" );
		$tag = null;
		if ( isset( $postData[ "datum"] ) ) {
			$tag = SOLAWI_Verteiltag::createFromDatum( $postData[ "datum"] );
		} else {
			$tag = SOLAWI_Verteiltag::createFromId( $id );
		}
		$vorhanden = SOLAWI_Verteiltag::valueOf( $tag->getId() );
		if ( isset( $vorhanden ) )
			$tag = $vorhanden;
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$tag->setVerteilungVon( $bereich, isset( $postData[ "verteilung_" . $bereich->getDbName() ] ) );
			if ( isset( $postData[ "text_" . $bereich->getDbName() ] ))
				$tag->setText( $bereich, $postData[ "text_" . $bereich->getDbName() ] );
			else
				$tag->setText( $bereich, null );
			if ( isset( $postData[ "urlaubskisten_" . $bereich->getDbName() ] ) )
				$tag->setAnzahlUrlaubskisten( $bereich, intval( $postData[ "urlaubskisten_" . $bereich->getDbName() ] ) );
			else
				$tag->setAnzahlUrlaubskisten( $bereich, 0 );
		}
		$tag->setZeitGemueseErnten( $postData[ "startErnte" ], $postData[ "endeErnte" ] );
		$tag->setZeitKistenPacken( $postData[ "startPacken" ], $postData[ "endePacken" ] );
		$tag->setZeitVerteilung( $postData[ "startVerteilung" ], $postData[ "endeVerteilung" ] );

		SOLAWI_Repository::instance()->save( $tag );
		$erg = SOLAWI_KalenderIntegration::instance()->updateKalender( $tag );
		if ( !$erg[ "success" ])
			new SOLAWI_SavePostdataResult( null, "Eintrag gespeichert, Kalender konnte aber nicht aktualisiert werden: " . $erg["error"] );
		return new SOLAWI_SavePostdataResult( "Erfolgreich gespeichert!", null, isset( $postData[ "datum"] ) );
	}
	
	/**
	 * Gibt das HTML für diese Seite aus
	 */
	protected function initInhalt() : void {
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::BAUER, SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			return;
		$tage = SOLAWI_Verteiltag::values();
		$maxDatum = new DateTime();
		$result = "\n";
		foreach ( $tage as $tag ) {
			$this->initKachel( $tag );
			$maxDatum = $tag->getDatum();
		}
		$maxDatum = $maxDatum->add( DateInterval::createFromDateString( "7 days") );
		$neuerTag = SOLAWI_Verteiltag::createFromDatum( $maxDatum );
		$this->initKachel( $neuerTag, true );
	}
	
	/**
	 * Gibt eine Zeile für einen Verteiltag aus.
	 */
	private function initKachel( SOLAWI_Verteiltag $tag, bool $datumBearbeitbar = false ) : void {
		$id = $tag->getId();
		$onInput = $this->getOnInputEventHtml( $id );
		$name = SOLAWI_formatDatum( $tag->getDatum() ) . " " . SOLAWI_arrayToString( $tag->getVerteilungen(), "getName" );
		$result = "<form method='POST' id='form_$id'>";
		// Überschrift
		if ( $datumBearbeitbar )
			$result .= "<h2>Neuer Tag: <input type='date' name='datum' $onInput value='" . SOLAWI_formatDatum( $tag->getDatum(), true ) . "'/></h2>";
		else
			$result .= "<h2>" . $tag->getWochentag() . " " . SOLAWI_formatDatum( $tag->getDatum() ) . "</h2>";

		$result .= $this->getHtmlArbeitszeiten( $tag );

		// Bereiche
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$result .= $this->getHtmlFuerBereich( $tag, $bereich );
		}
		$result .= $this->getSubmitButtonHtml( $id );
		$result .= "</form>";
		if ( !$datumBearbeitbar ) {
			$result .= "<p>Übersicht ausgeben: ";
			foreach ( $tag->getVerteilungen() as $bereich )
				$result .= $this->getLinkFuerUebersicht( $tag, $bereich ) . "&nbsp;";
			$result .= "</p>";
		}
		$this->addKachel( $name, $result, !$datumBearbeitbar );
	}

	/**
	 * Baut das HTML für einen Bereich an einem Verteiltag
	 */
	private function getHtmlFuerBereich( SOLAWI_Verteiltag $tag, SOLAWI_Bereich $bereich ) {
		$id = $tag->getId();
		$onInput = $this->getOnInputEventHtml( $id );
		$onClick = "onclick='solawi_show_textareas( $id )'";
		$selected = $tag->isVerteilungVon( $bereich ) ? " checked" : "";
		$bereichName = $bereich->getDbName();
		$hidden = !$tag->isVerteilungVon( $bereich ) ? " hidden" : "";
		$result = "<input type='checkbox' name='verteilung_$bereichName'$selected $onInput $onClick>&nbsp;" . $bereich->getName() ."<br>";
		$result .= "<div id='input_$bereichName$id' style='text-align:right'$hidden>";
		$result .= "<textarea name='text_$bereichName' $onInput style='width:100%;'>{$tag->getText( $bereich )}</textarea><br>";
		// TODO Urlaubskisten
		$result .= "Urlaubskisten:&nbsp;<input type='number' min='0' max='99' name='urlaubskisten_$bereichName' value='{$tag->getAnzahlUrlaubskisten( $bereich )}' $onInput/>";
		$result .= "</div>";
		return $result;
	}

	private function getLinkFuerUebersicht( SOLAWI_Verteiltag $tag, SOLAWI_Bereich $bereich ) : string {
		$url = ( new SOLAWI_AdminPrintVerteiltag() )->getUrl( array(
			"datum" => $tag->getId(),
			"bereich" => $bereich->getDbName()
		) );
		return "<a href='$url' target='_blank'>" . $bereich->getName() . "</a>";
	}

	private function getHtmlArbeitszeiten( SOLAWI_Verteiltag $tag ) : string {
		$id = $tag->getId();
		$onInput = $this->getOnInputEventHtml( $id );
		$grid = new SOLAWI_WidgetKachellayout( 4 );
		$grid->setWidth( 30 );

		$grid->add( null, "" );
		$grid->add( null, "Start" );
		$grid->add( null, "Ende" );
		$grid->add( null, "" );

		$grid->add( null, "Ernten&nbsp;" . SOLAWI_Bereich::GEMUESE->getShortName() );
		$grid->add( null, "<input type='time' id='startErnte' name='startErnte' value='{$tag->getStartGemueseErnten()}' $onInput>" );
		$grid->add( null, "<input type='time' id='endeErnte' name='endeErnte' value='{$tag->getEndeGemueseErnten()}' $onInput>" );
		$anzahlHelfer = $this->getAnzahlHelfer( $tag, true );
		$grid->add( null, $anzahlHelfer == 0 ? "" : "&nbsp;$anzahlHelfer&nbsp;Helfer&nbsp;angemeldet" );

		$grid->add( null, "Packen&nbsp;" . SOLAWI_Bereich::GEMUESE->getShortName() );
		$grid->add( null, "<input type='time' id='startPacken' name='startPacken' value='{$tag->getStartKistenPacken()}' $onInput>" );
		$grid->add( null, "<input type='time' id='endePacken' name='endePacken' value='{$tag->getEndeKistenPacken()}' $onInput>" );
		$anzahlHelfer = $this->getAnzahlHelfer( $tag, false );
		$grid->add( null, $anzahlHelfer == 0 ? "" : "&nbsp;$anzahlHelfer&nbsp;Helfer&nbsp;angemeldet" );

		$grid->add( null, "Verteilen" );
		$grid->add( null, "<input type='time' id='startVerteilung' name='startVerteilung' value='{$tag->getStartVerteilung()}' $onInput>" );
		$grid->add( null, "<input type='time' id='endeVerteilung' name='endeVerteilung' value='{$tag->getEndeVerteilung()}' $onInput>" );
		$grid->add( null, "" );

		return $grid->getHtml();
	}

	private function getAnzahlHelfer( SOLAWI_Verteiltag $tag, bool $zumErnten ) : int {
		$result = 0;
		foreach( SOLAWI_Verteiltag2Mitbauer::values( $tag ) as $v2m ) {
			if ( $zumErnten && $v2m->isGemueseErnten() )
				$result += 1;
			elseif ( !$zumErnten && $v2m->isKistenPacken() )
				$result += 1;
		}
		return $result;
	}
}
