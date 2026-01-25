<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 */
final class SOLAWI_Verteiltag2Mitbauer {
	
    private static array|null $values;

	private int $id;

	private SOLAWI_Verteiltag $verteiltag;

	private SOLAWI_Mitbauer $mitbauer;

	private SOLAWI_Verteilstation|null $verteilstation;

	private bool $gemueseErnten;

	private bool $kistenPacken;
	
	public function __construct( int $id, SOLAWI_Verteiltag $verteiltag, SOLAWI_Mitbauer $mitbauer ) {
		$this->id = $id;
		$this->verteiltag = $verteiltag;
        $this->mitbauer = $mitbauer;
		$this->verteilstation = $mitbauer->getVerteilstation();
        $this->gemueseErnten = false;
        $this->kistenPacken = false;
    }

	/**
	 * Gibt alle Verteiltag2Mitarbeiter zurück
	 */
	public static function values( SOLAWI_Verteiltag|null $verteiltag = null ) : array {
		if ( !isset( self::$values ) ) {
			self::$values = SOLAWI_Repository::instance()->getVerteiltag2Mitbauer();
		}
		if ( isset( $verteiltag ) ) {
			return array_filter( self::$values, function( SOLAWI_Verteiltag2Mitbauer $vt2mb ) use ( $verteiltag ) { return $vt2mb->getVerteiltag() === $verteiltag; } );
		}
		return self::$values;
	}
	
	/**
	 * Setzt alle Verteiltag2Mitarbeiter zurück
	 */
	public static function clearCache() {
		self::$values = null;
	}
	
	/**
	 * Gibt das Verteiltag2Mitarbeiter mit der übergebenen ID zurück
	 */
	public static function valueOf( SOLAWI_Verteiltag $verteiltag, SOLAWI_Mitbauer $mitbauer ) : SOLAWI_Verteiltag2Mitbauer {
		return self::valueById( self::ermittleId( $verteiltag, $mitbauer ) );
	}
	/**
	 * Gibt das Verteiltag2Mitarbeiter mit der übergebenen ID zurück.
	 * Zur Not wird ein neues Objekt dafür angelegt.
	 */
	public static function valueById( int $id ) : SOLAWI_Verteiltag2Mitbauer {
		$entity = SOLAWI_findEntityById( self::values(), $id );
		if ( !isset( $entity ) ) {
			$ids = str_split( strval( $id ), 8 );
			$verteiltag = SOLAWI_Verteiltag::valueOf( $ids[0] );
			if ( !isset( $verteiltag ) )
				$verteiltag = SOLAWI_Verteiltag::createFromId( $ids[0] );
			$mitbauer = SOLAWI_Mitbauer::valueOf( $ids[1] );
			$entity = new SOLAWI_Verteiltag2Mitbauer( $id, $verteiltag, $mitbauer );
			self::$values[] = $entity;
			return $entity;
		}
		return $entity;
	}

	private static function ermittleId( SOLAWI_Verteiltag $verteiltag, SOLAWI_Mitbauer $mitbauer ) : int {
		return intval( $verteiltag->getId() . $mitbauer->getId());
	}
	
	public function getId() : int {
		return $this->id;
	}

	public function getVerteiltag() : SOLAWI_Verteiltag {
		return $this->verteiltag;
	}

	public function getMitbauer() : SOLAWI_Mitbauer {
		return $this->mitbauer;
	}

	public function getVerteilstation() : SOLAWI_Verteilstation|null {
		return $this->verteilstation;
	}

	public function setVerteilstation( SOLAWI_Verteilstation|null $verteilstation ) : void {
		$this->verteilstation = $verteilstation;
	}

	/**
	 * Ob der Mitbauer an diesem Verteiltag beim Ernten des Gemüses helfen möchte.
	 */
	public function isGemueseErnten() : bool {
		return $this->gemueseErnten;
	}

	public function setGemueseErnten( bool $gemueseErnten ) : void {
		$this->gemueseErnten = $gemueseErnten;
	}
	
	/**
	 * Ob der Mitbauer an diesem Verteiltag beim Packen der Kisten helfen möchte.
	 */
	public function isKistenPacken() : bool {
		return $this->kistenPacken;
	}
	
	public function setKistenPacken( bool $kistenPacken ) : void {
		$this->kistenPacken = $kistenPacken;
	}
}