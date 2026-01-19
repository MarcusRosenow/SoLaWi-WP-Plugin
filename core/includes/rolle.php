<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

enum SOLAWI_Rolle {
	
	case MITBAUER;
	case BAUER;
	case MANAGER;
    case ADMINISTRATOR;

	public static function values() : array {
		return self::cases();
	}

	/**
     * Gibt die ID der Rolle zurück. Für die Speicherung in der DB.
     */
    public function getId() : int {
		return match($this) {
			SOLAWI_Rolle::MITBAUER => 1,
			SOLAWI_Rolle::BAUER => 2,
			SOLAWI_Rolle::MANAGER => 4,
            SOLAWI_Rolle::ADMINISTRATOR => 8,
            default => die(),
		};
    }

	public function getName() : string {
		return match($this) {
			SOLAWI_Rolle::MITBAUER => "Mitbauer",
			SOLAWI_Rolle::BAUER => "Bauer",
			SOLAWI_Rolle::MANAGER => "Manager",
            SOLAWI_Rolle::ADMINISTRATOR => "Administrator",
            default => die(),
		};
	}
	
	public function getBeschreibung() : string {
		return match($this) {
			SOLAWI_Rolle::MITBAUER => "Mitbauern können im Login-Bereich der Webseite (Frontend) ihre Daten einsehen und teilweise bearbeiten.",
			SOLAWI_Rolle::BAUER => "Bauern (hauptamtliche) können im Admin-Bereich die Verteiltage einsehen und bearbeiten.",
			SOLAWI_Rolle::MANAGER => "Manager können alle Daten im Admin-Bereich einsehen und bearbeiten.",
            SOLAWI_Rolle::ADMINISTRATOR => "Administratoren können viele Daten im Admin-Bereich einsehen und bearbeiten. Sie können Ernteanteile aber nicht bearbeiten und auch keine Mitbauer-bezogenen Geldangaben einsehen.",
            default => die(),
		};
	}
}