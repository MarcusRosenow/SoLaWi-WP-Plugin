<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Findet in dem Array der Entities das Element mit der übergebenen ID.
 * Wird kein Element gefunden, wird null zurückgegeben.
 */
function SOLAWI_findEntityById( array $entities, int $id ) : object|null {
	foreach ( $entities as $entity ) {
		if ( $entity->getId() === $id )
			return $entity;
	}
	return null;
}

/**
 * Gibt einen String zurück, der aus den Elementen des übergebenen Arrays zusammengesetzt wird.
 */
function SOLAWI_arrayToString( array $myArray, string|null $callback = null, string $delimiter = ", " ) : string {
	$result = "";
	foreach ( $myArray as $elem ) {
		$elemStr = strval( $callback === null ? $elem : call_user_func( array( $elem, $callback ) ) );
		if ( $result === "" )
			$result = $elemStr;
		else
			$result .= $delimiter . $elemStr;
	}
	return $result;
}
/**
 * Entfernt alle vorkommen von $object aus dem übergebenen Array und gibt ein neues Array zurück.
 */
function SOLAWI_arrayRemove( array $myArray, $object ) : array {
	$result = [];
	foreach( $myArray as $o ) {
		if ( $o !== $object )
			$result[] = $o;
	}
	return $result;
}

function SOLAWI_formatAnzahl( float $anzahl ) : string {
	return str_replace( ".", ",", strval( $anzahl ) );
}

function SOLAWI_formatWaehrung( float $waehrung, bool $mitEuroZeichen = false ) : string {
	if ( $mitEuroZeichen )
		return number_format( $waehrung, 2, ',', '.' ) . "&nbsp;&euro;";
	else
		return number_format( $waehrung, 2, ',', '' );
}

/**
 * Gibt das Datum als String zurück.
 * @param format null: deutsch, true: international, false: ohne Sonderzeichen
 */
function SOLAWI_formatDatum( DateTime $datum, bool|null $format = null ) : string {
	if ( $format === null )
		return $datum->format( get_option( 'date_format' ) );
	if ( $format === true )
		return $datum->format( 'Y-m-d' );
	return $datum->format( 'Ymd' );
}

/**
 * Gibt zurück, ob der übergebene Wert eine gültige Angabe für die Anzahl von Ernteanteilen ist
 */
function SOLAWI_isGueltigerAnteil( $wert ) : bool {
	if ( $wert === null )
		return false;
    if ( !is_numeric( $wert ) )
		return false;
	$floatValue = floatval( $wert );
	if ( $floatValue < 0 )
		return false;
	return $floatValue == 0.5 || $floatValue == intval( $wert );
}

/**
 * Gibt zurück, ob der übergebene Wert ein gültiger Preis für einen Ernteanteil ist
 */
function SOLAWI_isGueltigerPreis( $wert ) : bool {
	if ( $wert === null )
		return false;
    if ( !is_numeric( $wert ) )
		return false;
	$floatValue = floatval( $wert );
	if ( $floatValue < 0 )
		return false;
	// Höchstens zwei Nachkommastellen
	return $floatValue * 100 == intval( $floatValue * 100 );
}

/**
 * Gibt zurück, ob für den übergebenen User die übergebene Rolle (bzw. eine der übergebenen Rollen) vergeben ist.
 * Wird kein User übergeben, wird der aktuelle User verwendet.
 */
function SOLAWI_hasRolle( SOLAWI_Rolle ...$rollen ) : bool {
	$user = SOLAWI_Mitbauer::getAktuellenMitbauer();
	foreach( $rollen as $rolle )
		if ( $user->hasRolle( $rolle ) )
			return true;
	return false;
}