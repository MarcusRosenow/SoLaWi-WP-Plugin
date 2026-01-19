<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Für die Initialisierung des Backends für die Verteilstationen
 */
final class SOLAWI_AdminPageVerteilstationen extends SOLAWI_AbstractAdminPage {
	
	private const NEU_ID = 99999;

	public function getTitel() : string {
		return "Verteilstationen";
	}
	
	public function savePostdata( int $id, array $postData ) : SOLAWI_SavePostdataResult {
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			return new SOLAWI_SavePostdataResult( null, "Nicht authorisiert" );
		$station = null;
		if ( $id === self::NEU_ID ) {
			$id = SOLAWI_Verteilstation::nextId();
			$station = new SOLAWI_Verteilstation( $id, $postData[ "name" ] );
			SOLAWI_Repository::instance()->save( $station );
			return new SOLAWI_SavePostdataResult( "Neue Station gespeichert!", null, true );
		} else {
			$station = SOLAWI_Verteilstation::valueOf( $id );
			$station->setName( $postData[ "name" ] );
			if ( isset( $postData[ "loeschen" ] ) && $postData[ "loeschen" ] === "ja" ) {
				SOLAWI_Repository::instance()->remove( $station );
				return new SOLAWI_SavePostdataResult( "Station gelöscht!", null, true );
			} else {
				SOLAWI_Repository::instance()->save( $station );
				return new SOLAWI_SavePostdataResult(null,$erg . null, false);
			}
		}
	}
	
	protected function initInhalt() : void {
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			return;
		$stationen = SOLAWI_Verteilstation::values();
		$stationen[] = new SOLAWI_Verteilstation( self::NEU_ID, "Neue Station" );
		foreach ( $stationen as $station )
			$this->addKachel( $station->getName(), $this->toString( $station ), $station->getId() !== self::NEU_ID );
	}
	
	/**
	 * Gibt eine Zeile für eine Verteilstation aus.
	 */
	private function toString( SOLAWI_Verteilstation $station ) : string {
		$id = $station->getId();
		$onInput = $this->getOnInputEventHtml( $id );
		$isNeu = $id === self::NEU_ID;
		$name = $station->getName();
		$result = "<form method='POST'>";
		$value = $isNeu ? "placeholder='$name'" : "value='$name'";
		$result .= "<input type='text' name='name' $value $onInput/><br>";
		if ( !$isNeu ) {
			$anzahlMitbauern = count( SOLAWI_Mitbauer::values( $station ) );
			$result .= "<p>Anzahl Mitbauern hier: $anzahlMitbauern<br></p>";
			if ( $anzahlMitbauern == 0 && !$station->isHof() ) {
				$result .= "<p><input type='checkbox' name='loeschen' value='ja' $onInput/>&nbsp;<label for='loeschen'>Diese Station löschen</label></p>";
			}
		}
		$result .= $this->getSubmitButtonHtml( $id );
		$result .= "</form>";
		return $result;
	}
}
