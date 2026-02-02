<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Für die Initialisierung des Backends für die Verteiltag2Mitbauer-Objekte
 */
final class SOLAWI_AdminPageVerteiltag2Mitbauer extends SOLAWI_AbstractAdminPage {

	private SOLAWI_Mitbauer|null $mitbauer;

	function __construct(){
		if ( isset( $_POST[ "id" ] ) && $_POST[ "id" ] === "-1" && isset( $_POST[ "mitbauer" ] ) )
			$this->mitbauer = SOLAWI_Mitbauer::valueOf( $_POST[ "mitbauer" ] );
		else
			$this->mitbauer = null;
	}

	public function getTitel() : string {
		return "Abholung je Mitbauer";
	}
	
	public function savePostdata( int $id, array $postData ) : SOLAWI_SavePostdataResult {
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::BAUER, SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			return new SOLAWI_SavePostdataResult( null, "Nicht authorisiert" );
        $stationId = intval( $postData[ "station" ] );
        $vt2mb = SOLAWI_Verteiltag2Mitbauer::valueById( $id );
    	$vt2mb->setVerteilstation( $stationId >= 0 ? SOLAWI_Verteilstation::valueOf( $stationId ) : null );
    	SOLAWI_Repository::instance()->save( $vt2mb );
		$this->mitbauer = $vt2mb->getMitbauer();
		return new SOLAWI_SavePostdataResult();
	}

	/**
	 * Gibt das HTML für diese Seite aus
	 */
	protected function initInhalt() : void {
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::BAUER, SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			return;
		if ( $this->mitbauer !== null ) {
			$this->addKachel( "Zusammenfassung", $this->getHtmlFuerMitbauerInfo(), false );
			foreach( SOLAWI_Verteiltag::values() as $tag ) {
				if ( !$this->mitbauer->hasErnteanteile( $tag->getVerteilungen(), $tag->getDatum() ) )
					continue;
				$this->addKachel( null, $this->getHtmlFuerBearbeitung( $tag ) );
			}
		}
	}

	protected function getFilterHtml() : string {
		$id = -1;
		$onInput = "oninput='solawi_changed($id)'";
		$result = "<p><form method='post'>";
		$result .= "Zeige Daten für:&nbsp;<select name='mitbauer' $onInput>";
		foreach ( SOLAWI_Mitbauer::values( null, true ) as $mitbauer ) {
			$selected = $mitbauer === $this->mitbauer ? " selected" : "";
			$result .= "<option value='{$mitbauer->getId()}'$selected>{$mitbauer->getName()}</option>";
		}
		$result .= "</select>&nbsp;";
		$result .= $this->getSubmitButtonHtml( $id, true );
		$result .= "</form>";
		return $result;
	}

	private function getHtmlFuerMitbauerInfo() : string {
        $result = "<h2>" . $this->mitbauer->getName() . "</h2>";
		$result .= "<p>" . $this->mitbauer->getEmailAsHtmlString() . "</p>";
		$result .= $this->mitbauer->getHtmlTableErnteanteile();
        $result .= "<p>Verteilstation: " . $this->mitbauer->getVerteilstation()->getName() . "</p>";
		return $result;
	}

	private function getHtmlFuerBearbeitung( SOLAWI_Verteiltag $tag ) : string {
		$vt2mb = SOLAWI_Verteiltag2Mitbauer::valueOf( $tag, $this->mitbauer );
		$onInput = $this->getOnInputEventHtml( $vt2mb->getId() );
        $result = "<h2>" . SOLAWI_formatDatum( $tag->getDatum() ) . "</h2>\n";
		$result .= "<form method='POST'>";
        $result .= $this->getHtmlAuswaehlbareVerteilstationen( $vt2mb ) . "<br>";
		// TODO Gemüse ernten und Kisten packen?
        $result .= $this->getSubmitButtonHtml( $vt2mb->getId(), false );
        $result .= "</form>\n";
		return $result;
	}

	
    /**
     * Baut die Radiogroup für die auswählbaren Verteilstationen
     */
    private function getHtmlAuswaehlbareVerteilstationen( SOLAWI_Verteiltag2Mitbauer $vt2mb ) : string {
		$onInput = $this->getOnInputEventHtml( $vt2mb->getId() );
		$result = "Verteilstation: <select name='station' $onInput>";
		
		foreach ( SOLAWI_Verteilstation::values() as $station )
			$result .= $this->getHtmlRadioVerteilstation( $station, $vt2mb );
		$result .= $this->getHtmlRadioVerteilstation( null, $vt2mb );
		$result .= "</select>";
        return $result;
    }

	private function getHtmlRadioVerteilstation( SOLAWI_Verteilstation|null $station, SOLAWI_Verteiltag2Mitbauer $vt2mb ) : string {
		$stationId = $station === null ? -1 : $station->getId();
		$selected = $station !== null && $station === $vt2mb->getVerteilstation()
						|| $station === null && $vt2mb->getVerteilstation() === null
							? " selected"
							: "";
		$name = $station === null ? "Keine Abholung" : $station->getName();
		return "<option value='$stationId'$selected>$name</option>";
	}
}
