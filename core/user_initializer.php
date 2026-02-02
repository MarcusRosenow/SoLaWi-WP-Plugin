<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Klasse um User zu initialisieren
 */
final class SOLAWI_UserInitializer {

	private function __construct(){
	}

	/**
	 * Initialisiert die Rechte aller Nutzer.
	 */
	static function initUsers() : void {
		$ergebnis = SOLAWI_Repository::instance()->migriereMitbauern();
		foreach( SOLAWI_Mitbauer::values() as $user ) {
			self::initUser( $user );
		}
	}

	/**
	 * Initialisiert die Rechte des übergebenen Nutzers.
	 * Allerdings nur dann, wenn er nicht bereits initialisiert ist.
	 */
	static function initUser( SOLAWI_Mitbauer $user ) : void {
		if ( !$user->isRolleInitialisiert() ) {
			if ( is_super_admin( $userId ) )
				$user->setRollen( array( SOLAWI_Rolle::MITBAUER, SOLAWI_Rolle::ADMINISTRATOR ) );
			else
				$user->setRollen( array( SOLAWI_Rolle::MITBAUER ) );
		}
	}
}