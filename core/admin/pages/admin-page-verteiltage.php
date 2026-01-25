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
		$onClick = "onclick='solawi_show_textareas($id)'";
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
			$selected = $tag->isVerteilungVon( $bereich ) ? " checked" : "";
			$result .= "<p><input type='checkbox' name='verteilung_{$bereich->getDbName()}'$selected $onInput $onClick>&nbsp;" . $bereich->getName() ."<br>";
			$hidden = !$tag->isVerteilungVon( $bereich ) ? " hidden" : "";
			$result .= "<textarea name='text_{$bereich->getDbName()}' $onInput style='width:100%;'$hidden>{$tag->getText( $bereich )}</textarea></p>";
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
		$grid = new SOLAWI_WidgetKachellayout( 3 );
		$grid->setWidth( 30 );

		$grid->add( null, "" );
		$grid->add( null, "Start" );
		$grid->add( null, "Ende" );

		$grid->add( null, "Ernte&nbsp;(Gemüse)" );
		$grid->add( null, "<input type='time' id='startErnte' name='startErnte' value='{$tag->getStartGemueseErnten()}' $onInput>" );
		$grid->add( null, "<input type='time' id='endeErnte' name='endeErnte' value='{$tag->getEndeGemueseErnten()}' $onInput>" );

		$grid->add( null, "Packen&nbsp;(Gemüse)" );
		$grid->add( null, "<input type='time' id='startPacken' name='startPacken' value='{$tag->getStartKistenPacken()}' $onInput>" );
		$grid->add( null, "<input type='time' id='endePacken' name='endePacken' value='{$tag->getEndeKistenPacken()}' $onInput>" );

		$grid->add( null, "Verteilung" );
		$grid->add( null, "<input type='time' id='startVerteilung' name='startVerteilung' value='{$tag->getStartVerteilung()}' $onInput>" );
		$grid->add( null, "<input type='time' id='endeVerteilung' name='endeVerteilung' value='{$tag->getEndeVerteilung()}' $onInput>" );

		return $grid->getHtml();
	}
}
