<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Für die Initialisierung des Backends für die Nutzer-Berechtigungen
 */
final class SOLAWI_AdminPageNutzer extends SOLAWI_AbstractAdminPage {

	public function getTitel() : string {
		return "Nutzer";
	}
	
	public function savePostdata( int $id, array $postData ) : SOLAWI_SavePostdataResult {
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			return new SOLAWI_SavePostdataResult( null, "Nicht authorisiert" );
		$user = SOLAWI_Mitbauer::valueOf( $id );
		if ( $id != get_current_user_id() ) {
			$rollen = [];
			foreach( SOLAWI_Rolle::values() as $rolle ) {
				if ( isset( $postData[ "rolle" . $rolle->getId() ] ) )
					$rollen[] = $rolle;
			}
			$user->setRollen( $rollen );
		}
		$user->setTelefonnummer( isset( $postData[ "telefon" ] ) ? $postData[ "telefon" ] : null );
		$user->setStrasse( isset( $postData[ "strasse" ] ) ? $postData[ "strasse" ] : null );
		$user->setOrt( isset( $postData[ "ort" ] ) ? $postData[ "ort" ] : null );
		$user->setBemerkung( isset( $postData[ "bemerkung" ] ) ? $postData[ "bemerkung" ] : null );
		return new SOLAWI_SavePostdataResult( "Erfolgreich gespeichert!" );
	}

	/**
	 * Baut das HTML für den Filter
	 */
	protected function getFilterHtml() : string {
		$result = parent::getFilterHtml();
		$url = ( new SOLAWI_AdminPrintNutzer() )->getUrl();
		$result.= "<p><a href='$url' target='_blank'>Daten exportieren</a>";
		return $result;
	}
	
	protected function initInhalt() : void {
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			return;
		
		$users = SOLAWI_Mitbauer::values();
		foreach( $users as $user ) {
			$this->addKachel( $user->getName() . " " . $user->getEmail(), $this->baueNutzerBlock( $user ) );
		}
	}

	protected function getEndeHtml() : string {
		$result = "<h2>Legende</h2>";
		foreach( SOLAWI_Rolle::values() as $rolle )
			$result .= "<p><b>" . $rolle->getName() . "</b>: " . $rolle->getBeschreibung() . "</p>";
		return $result;
	}
	
	/**
	 * Gibt eine Zeile für einen Mitbauern aus.
	 */
	private function baueNutzerBlock( SOLAWI_Mitbauer $nutzer ) : string {
		$userId = $nutzer->getId();
		$mail = $nutzer->getEmail();
		$telefon = $nutzer->getTelefonnummer();
		$strasse = $nutzer->getStrasse();
		$ort = $nutzer->getOrt();
		$bemerkung = $nutzer->getBemerkung();
		$result = "<form method='POST'>";
		$result .= "<h2>" . $nutzer->getName() . "</h2>";

		$grid = new SOLAWI_WidgetKachellayout( 2 );

		$onInput = $this->getOnInputEventHtml( $userId );
		$rollen = "";
		foreach( SOLAWI_Rolle::values() as $rolle ) {
			$checked =  $nutzer->hasRolle( $rolle ) ? " checked" : "";
			$disabled = $userId == get_current_user_id() ? " disabled" : "";
			$rolleId = $rolle->getId();
			$rolleName = $rolle->getName();
			$rollen .= "<input type='checkbox'$checked name='rolle$rolleId' $onInput$disabled/>$rolleName<br>";
		}
		$grid->add( null, "&#x1F511;" );
		$grid->add( null, $rollen );

		$grid->add( null, $nutzer->getEmailAsHtmlString( true ) );
		$grid->add( null, "<input type='text' value='" . $nutzer->getEmail() . "' disabled/>" );

		$grid->add( null, $nutzer->getTelefonnummerAsHtmlString( true ) );
		$grid->add( null, "<input type='text' value='$telefon' name='telefon' $onInput/>" );
		
		$grid->add( null, "&#x1F3E0;" );
		$grid->add( null, "<input type='text' value='$strasse' name='strasse' $onInput/>" );

		$grid->add( null, "&#x1F3E0;" );
		$grid->add( null, "<input type='text' value='$ort' name='ort' $onInput/>" );
		
		$grid->add( null, "&#x1F4DD;" );
		$grid->add( null, "<textarea name='bemerkung' $onInput rows=3>$bemerkung</textarea>" );

		$result .= $grid->getHtml();
		$result .= $this->getSubmitButtonHtml( $userId );
		$result .= "</form>";
		return $result;
	}
}
