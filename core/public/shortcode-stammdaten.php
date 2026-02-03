<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

final class SOLAWI_ShortcodeStammdaten extends SOLAWI_AbstractShortcode {

    public function getKey() : string {
        return "stammdaten";
    }

    public function savePostdata( int $id, array $data ) : void {
    }

    public function getHtml( SOLAWI_Mitbauer $mitbauer ) : string {
        $result = "<b>Name:</b><br>" . $mitbauer->getName() . "<br><br>";
        $result .= "<b>E-Mail:</b><br>" . $mitbauer->getEMail() . "<br><br>";
        $result .= "<b>Telefon:</b><br>" . $this->nonNull( $mitbauer->getTelefonnummer() ) . "<br><br>";
        $result .= "<b>Adresse:</b><br>" . $this->nonNull( $mitbauer->getStrasse() ) . "<br>" . $this->nonNull( $mitbauer->getOrt() );
        return $result;
    }

    private function nonNull( string|null $value ) : string {
        return $value === null ? "-" : $value;
    }
}