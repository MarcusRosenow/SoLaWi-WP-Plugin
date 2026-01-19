<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class SOLAWI_AbstractShortcode {

    /**
     * Gibt den Schlüssel für diese Seite zurück
     */
    public abstract function getKey() : string;

    /**
     * Speichert die übergebenen Daten
     */
    public abstract function savePostdata( int $id, array $postData ) : void;

    /**
     * Gibt das HTML zurück, welches anstelle des Shortcodes dargestellt werden soll
     */
    public abstract function getHtml( SOLAWI_Mitbauer $mitbauer ) : string;

	/**
	 * Gibt den String für den Submit/Reset-Button zurück.
	 */
	protected final function getSubmitButtonHtml( string $id, bool $visible = false, bool $mitZuruecksetzen = true, string|null $submitText = null ) : String {
        $key = $this->getKey();
        $result = wp_nonce_field( $key . $id );
        $hidden = $visible ? "" : " hidden='true'";
		$result .= "<input type='hidden' name='id' value='$id'/>";
		$result .= "<input type='hidden' name='form' value='$key'/>";
        $valueSubmit = $submitText == null ? "" : " value='$submitText'";
		$result .= "<input type='submit'$hidden$valueSubmit id='submit_$key$id'/>";
        if ( $mitZuruecksetzen )
            $result .= "<input type='reset'$hidden id='reset_$key$id'/>";
        return $result;
	}

    public final function getOnInputEventHtml( string $id ) : String {
        $key = $this->getKey();
        return "oninput='solawi_changed(\"$key$id\")'";
    }
}