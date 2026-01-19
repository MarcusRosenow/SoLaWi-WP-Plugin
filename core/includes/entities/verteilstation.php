<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 */
final class SOLAWI_Verteilstation {
	
    private static array|null $values;
	
	private int $id;
	
	private string $name;
	
	public function __construct( int $id, string $name ) {
        $this->id = $id;
        $this->name = $name;
    }
	
	/**
	 * Gibt alle Verteilstationen zurück
	 */
	public static function values() : array {
		if ( !isset( self::$values ) ) {
			self::$values = SOLAWI_Repository::instance()->getVerteilstationen();
		}
		return self::$values;
	}
	
	/**
	 * Setzt alle Verteilstationen zurück
	 */
	public static function clearCache() {
		self::$values = null;
	}
	
	/**
	 * Gibt die Verteilstation mit der übergebenen ID zurück
	 */
	public static function valueOf( int $id ) : SOLAWI_Verteilstation {
		return SOLAWI_findEntityById( self::values(), $id );
	}
	
	/**
	 * Gibt die nächste zu vergebene ID zurück
	 */
	public static function nextId() : int {
		$values = self::values();
		$maxId = 0;
		foreach ( $values as $value ) {
			if ( $value->getId() > $maxId ) {
				$maxId = $value->getId();
			}
		}
		return $maxId + 1;
	}
	
	/**
	 * Gibt die ID zurück
	 */
	public function getId() : int {
		return $this->id;
	}
	
	/**
	 * Gibt den Namen zurück
	 */
	public function getName() : string {
		return $this->name;
	}
	
	/**
	 * Setzt den Namen
	 */
	public function setName( string $name ) {
		$this->name = $name;
	}

	/**
	 * Ob es sich um den Standard-Abholort mit der ID 0 handelt
	 */
	public function isHof() : bool {
		return $this->getId() === 0;
	}
}