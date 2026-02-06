<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

enum SOLAWI_Bereich {
	
	case FLEISCH;
	case MOPRO;
	case GEMUESE;
	
	public function getName() : string {
		return match($this) {
			SOLAWI_Bereich::FLEISCH => '<span class="solawi_bereich">&#128002;Fleisch</span>',
			SOLAWI_Bereich::MOPRO => '<span class="solawi_bereich">&#129371;MoPro</span>',
			SOLAWI_Bereich::GEMUESE => '<span class="solawi_bereich">&#129365;Gem&uuml;se</span>',
            default => die(),
		};
	}

	public function getShortName() : string {
		return match($this) {
			SOLAWI_Bereich::FLEISCH => '&#128002;',
			SOLAWI_Bereich::MOPRO => '&#129371;',
			SOLAWI_Bereich::GEMUESE => '&#129365;',
            default => die(),
		};
	}

	public function getSimpleName() : string {
		return match($this) {
			SOLAWI_Bereich::FLEISCH => 'Fleisch',
			SOLAWI_Bereich::MOPRO => 'MoPro',
			SOLAWI_Bereich::GEMUESE => 'Gem&uuml;se',
            default => die(),
		};
	}

	public function getDbName() : string {
		return match($this) {
			SOLAWI_Bereich::FLEISCH => 'fleisch',
			SOLAWI_Bereich::MOPRO => 'mopro',
			SOLAWI_Bereich::GEMUESE => 'gemuese',
            default => die(),
		};
	}

	public static function values() : array {
		return self::cases();
	}

	public static function valueOf( String $dbName ) : SOLAWI_Bereich {
		foreach ( self::values() as $bereich )
			if ( $bereich->getDbName() === $dbName )
				return $bereich;
		wp_die( "Fehler: Bereich nicht gefunden" );
	}
}