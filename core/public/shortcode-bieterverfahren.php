<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Frontend-Ausgabe für das Bieterverfahren
 */
final class SOLAWI_ShortcodeBieterverfahren extends SOLAWI_AbstractShortcode {

    private string|null $info;

    private bool|null $error;

    public function getKey() : string {
        return "bieterverfahren";
    }

    public function savePostdata( int $id, array $data ) : void {
        $gebot = SOLAWI_WidgetErfassunggebote::konvertierePostdataZuGebot( $data );
        if ( is_string( $gebot ) ) {
            $this->info = $gebot;
            $this->error = true;    
        } else {
            SOLAWI_Repository::instance()->save( $gebot );
            $this->info = "Danke, dein Gebot für Runde $id wurde erfasst.";
            $this->error = false;
        }
    }

    public function getHtml( SOLAWI_Mitbauer $mitbauer ) : string {
        $result = "";
        if ( isset( $this->info ) ) {
            $color = isset( $this->error ) && $this->error ? "red" : "green";
            $result .= "<p><b style='color:$color'>" . $this->info . "</b></p>";
        }
        $widget = new SOLAWI_WidgetErfassunggebote( false, array( $this, "getSubmitButton" ), $mitbauer );
        $result .= $widget->getHtml();
        return $result;
    }

    public function getSubmitButton( int $runde ) : string {
        return $this->getSubmitButtonHtml( $runde, true, false, "Gebot abgeben" );
    }
}