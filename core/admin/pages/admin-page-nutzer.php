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
		if ( $id === get_current_user_id() )
			return new SOLAWI_SavePostdataResult( null, "Sich selbst darf man nicht bearbeiten" );
		$u2r = new SOLAWI_User2Rolle( $id, 0 );
		$result = "";
		foreach( SOLAWI_Rolle::values() as $rolle ) {
			if ( isset( $postData[ "rolle" . $rolle->getId() ] ) )
				$u2r->addRolle( $rolle );
		}
		SOLAWI_Repository::instance()->save( $u2r );
		return new SOLAWI_SavePostdataResult( "Erfolgreich gespeichert!" );
	}
	
	protected function initInhalt() : void {
		if ( !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) )
			return;
		
		$users = get_users();
		foreach( $users as $user ) {
			$this->addKachel( $user->get( "display_name" ) . " " . $user->get( "user_email" ), $this->baueNutzerBlock( $user ) );
		}
		
		$rollenText = "<h2>Legende</h2>";
		foreach( SOLAWI_Rolle::values() as $rolle )
			$rollenText .= "<p><b>" . $rolle->getName() . "</b>: " . $rolle->getBeschreibung() . "</p>";
		$this->addKachel( "Legende", $rollenText, false );
	}
	
	/**
	 * Gibt eine Zeile für einen Mitbauern aus.
	 */
	private function baueNutzerBlock( WP_User $nutzer ) : string {
		$userId = $nutzer->ID;
		$userMail = $nutzer->get( "user_email" );
		$result = "<form method='POST'>";
		$result .= "<h2>" . $nutzer->get( "display_name" ) . "</h2>";
		$result .= "<p><a href='mailto:$userMail'>$userMail</a></p>";
		$u2r = SOLAWI_User2Rolle::valueOf( $userId );

		$onInput = $this->getOnInputEventHtml( $userId );
		foreach( SOLAWI_Rolle::values() as $rolle ) {
			$checked =  $u2r->hasRolle( $rolle ) ? " checked" : "";
			$disabled = $userId == get_current_user_id() ? " disabled" : "";
			$rolleId = $rolle->getId();
			$rolleName = $rolle->getName();
			$result .= "<input type='checkbox'$checked name='rolle$rolleId' $onInput$disabled/>$rolleName<br>";
		}
		$result .= $this->getSubmitButtonHtml( $userId );
		$result .= "</form>";
		return $result;
	}
}
