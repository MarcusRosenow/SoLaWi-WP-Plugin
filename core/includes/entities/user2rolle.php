<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 */
final class SOLAWI_User2Rolle {
	
    private static array|null $values;
	
	private int $userId;
	
	private int $rollen;

	private bool $isPersistent;
	
	public function __construct( int $userId, int $rollen = 0 ) {
        $this->userId = $userId;
        $this->rollen = $rollen;
		$this->isPersistent = true;
    }
	
	/**
	 * Gibt alle User2Rolle-Objekte zurück
	 */
	private static function values() : array {
		if ( !isset( self::$values ) ) {
			self::$values = SOLAWI_Repository::instance()->getUser2Rollen();
		}
		return self::$values;
	}

	/**
	 * Gibt das Objekt zum übergebenen Nutzer zurück. ungleich null
	 */
	public static function valueOf( int $userId ) : SOLAWI_User2Rolle {
		foreach( self::values() as $u2r )
			if ( $u2r->userId === $userId )
				return $u2r;
		$u2r = new SOLAWI_User2Rolle( $userId );
		$u2r->isPersistent = false;
		self::$values[] = $u2r;
		return $u2r;
	}
	
	/**
	 * Setzt alle Verteilstationen zurück
	 */
	public static function clearCache() {
		self::$values = null;
	}


	/**
	 * Gibt zurück, ob der übergebene Nutzer die übergebene Rolle hat
	 */
	public static function hasUserRolle( int $userId, SOLAWI_Rolle $rolle ) : bool {
		$u2r = self::valueOf( $userId );
		return $u2r->hasRolle( $rolle );
	}

	/**
	 * Gibt die User-ID zurück
	 */
	public function getId() : int {
		return $this->userId;
	}

	/**
	 * Gibt zurück, ob dieser Datensatz aus der DB geladen wurde.
	 */
	public function isPersistent() {
		return $this->isPersistent;
	}

	/**
	 * Gibt die interne Repräsentation der Rollen zurück
	 */
	public function getRollen() : int {
		return $this->rollen;
	}

	public function hasRolle( SOLAWI_Rolle $rolle ) : bool {
		return ($this->rollen & $rolle->getId()) > 0;
	}

	/**
	 * Fügt die übergebene Rolle diesem Objekt hinzu
	 */
	public function addRolle( SOLAWI_Rolle $rolle ) : void {
		$this->rollen = $this->rollen | $rolle->getId();
	}

	/**
	 * Entfernt die übergebene Rolle von diesem Objekt
	 */
	public function removeRolle( SOLAWI_Rolle $rolle ) : void {
		if ( $this->hasRolle( $rolle ) )
			$this->rollen -= $rolle->getId();
	}
}