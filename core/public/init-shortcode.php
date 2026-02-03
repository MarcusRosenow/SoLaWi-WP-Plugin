<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

final class SOLAWI_InitShortcode {

    private array $shortcodes = array();

    public function __construct() {
        $shortcodeClasses = [
            new SOLAWI_ShortcodeBieterverfahren(),
            new SOLAWI_ShortcodeErnteanteile(),
            new SOLAWI_ShortcodeVerteilgruppe(),
            new SOLAWI_ShortcodeVerteiltage(),
            new SOLAWI_ShortcodeStammdaten()
        ];
        foreach ( $shortcodeClasses as $class ) {
            $this->shortcodes[ $class->getKey() ] = $class;
        }
        add_shortcode( 'solawiform', array( $this, 'print' ) );
        add_action( "plugins_loaded", array( $this, "savePostdata") );
    }
    
    /** 
     * Speichert die geänderten Angaben des Benutzers
     */
	public function savePostdata() : void {
        if ( isset( $_POST ) && SOLAWI_hasRolle( SOLAWI_Rolle::MITBAUER ) ) {
            $id = isset( $_POST[ "id" ] ) ? $_POST[ "id" ] : null;
            $key = isset( $_POST[ "form" ] ) ? $_POST[ "form" ] : null;
		    if ( isset( $id ) && isset( $key ) ) {
                check_ajax_referer( $key . $id );
                $this->shortcodes[ $key ]->savePostdata( intval( $id ), $_POST );
		    }
        }
	}

    public function print( array $attributes ) : string {
        $result = "<div class='solawi_block'>";
        $mitbauer = SOLAWI_Mitbauer::getAktuellenMitbauer();
        if ( !isset( $attributes[ "page" ] ) ) {
            $result .= "<p><b>Shortcode solawiform</b>: Es fehlt die Angabe des Attributs 'page'!</p><pre>[solawiform page=...]</pre>";
        } elseif ( !isset( $mitbauer ) ) {
            $result .= "<p>Bitte melde dich an, um deine persönlichen Daten einzusehen.</p>";
        } elseif ( !isset( $this->shortcodes[ $attributes[ "page" ] ] ) ) {
            $result .= "<p><b>Shortcode solawiform</b>: Angegebene 'page' nicht vorhanden!</p>
                                <p>Verfügbare Werte: " . SOLAWI_arrayToString( array_keys( $this->shortcodes ) );
        } else {
            $verteiltagId = isset( $attributes[ 'verteiltagid' ] ) ? $attributes[ 'verteiltagid' ] : null;
            $result .=  $this->shortcodes[ $attributes[ "page" ] ]->getHtml( $mitbauer, $verteiltagId );
        }
        $result .= "</div>";
        return $result;
    }
}