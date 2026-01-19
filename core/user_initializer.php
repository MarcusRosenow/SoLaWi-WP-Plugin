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
		foreach( get_users( [ 'fields' => 'ID' ] ) as $userId ) {
			self::initUser( $userId );
		}
	}

	/**
	 * Initialisiert die Rechte des übergebenen Nutzers.
	 * Allerdings nur dann, wenn er nicht bereits initialisiert ist.
	 */
	static function initUser( int $userId ) : void {
		$u2r = SOLAWI_User2Rolle::valueOf( $userId );
		if ( $u2r->isPersistent() )
			return;
		$u2r->addRolle( SOLAWI_Rolle::MITBAUER );
		if ( is_super_admin( $userId ) )
			$u2r->addRolle( SOLAWI_Rolle::ADMINISTRATOR );
		SOLAWI_Repository::instance()->save( $u2r );
	}
}