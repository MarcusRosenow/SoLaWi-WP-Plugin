<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

final class SOLAWI_ShortcodeVerteilgruppe extends SOLAWI_AbstractShortcode {

    public function getKey() : string {
        return "verteilgruppe";
    }

    public function savePostdata( int $id, array $data ) : void {
    }
    
    /**
     * Gibt das HTML für die Verteilgruppe aus.
     */
    public function getHtml( SOLAWI_Mitbauer $mitbauer, int|null $verteiltagId = null ) : string {
        $meineStation = $mitbauer->getVerteilstation();
        $verteiltag = $this->getOrCreateVerteiltag( $verteiltagId );

        if ( $verteiltag == null ) // Wenn zu weit in der Vergangenheit gesucht wird
            return "";

        if ( $meineStation->isHof() ) {
            $result = "Deine Verteilstation ist {$meineStation->getName()}.";
            if ( SOLAWI_Verteiltag2Mitbauer::valueOf( $verteiltag, $mitbauer )->getVerteilstation() === null )
                $result .= "<br>Am " . SOLAWI_formatDatum( $verteiltag->getDatum() ) . " möchtest du deine Kiste(n) aber nicht abholen.";
            return "<p class='has-text-align-center'>$result</p>";
        }
        $mitbauern = SOLAWI_Mitbauer::values( $meineStation );
        $result = "<p class='has-text-align-center'>Deine Verteilstation ({$meineStation->getName()}) am " . SOLAWI_formatDatum( $verteiltag->getDatum() ) . ":</p>";
        $result .= "<table class='solawi'><tr class='stark'><td class='left'>Mitbauer</td><td class='right'>";
        $result .= SOLAWI_arrayToString( $verteiltag->getVerteilungen(), "getName", "</td><td class='right'>" );
        $result .= "</td></tr>";
        $resultEnde = "";
        $summe = array();
        foreach ( SOLAWI_Bereich::values() as $bereich )
            $summe[ $bereich->getName() ] = 0;
        $relevanteMitbauern = [];
        foreach ( $mitbauern as $bauer ) {
            if ( !$bauer->hasErnteanteile( null, $verteiltag->getDatum() ) )
                continue;
            $vt2mb = SOLAWI_Verteiltag2Mitbauer::valueOf( $verteiltag, $bauer );
            $isStandardAbholung = $mitbauer->getVerteilstation() === $vt2mb->getVerteilstation();
            if ( $isStandardAbholung ) {
                $isMitbauerDabei = false;
                foreach ( $verteiltag->getVerteilungen() as $bereich ) {
                    $anteil = $bauer->getErnteanteil( $bereich, $verteiltag->getDatum() );
                    $summe[ $bereich->getName() ] += $anteil;
                    $isMitbauerDabei |= $anteil > 0 ;
                }
                if ( $isMitbauerDabei ) {
                    $relevanteMitbauern[] = $bauer;
                    $result .= $this->getHtmlTableRowMitbauer( $bauer, $verteiltag, $vt2mb->getVerteilstation(), null );
                } else {
                    $resultEnde .= $this->getHtmlTableRowMitbauer( $bauer, $verteiltag, $vt2mb->getVerteilstation(), "schwach" );
                }
            } else {
                $resultEnde .= $this->getHtmlTableRowMitbauer( $bauer, $verteiltag, $vt2mb->getVerteilstation(), "schwach" );
            }
        }
        $result .= $this->getHtmlTableRowSumme( $relevanteMitbauern, $meineStation, $verteiltag );
        if ( $resultEnde !== "" ) {
            $result .= "<tr class='schwach'><td colspan='4' class='center'><br><br>Diesmal nicht durch den Fahrdienst abzuholen</td></tr>";
            $result .= $resultEnde;
        }
        $result .= "</table></p>";
        return $result;
    }

    /**
     * Gibt den Verteiltag zur übergebenen ID zurück oder sucht den nächsten Verteiltag oder erstellt einfach einen neuen Verteiltag.
     * Wenn es zur übergebenen ID (ungleich null) keinen Verteiltag gibt (weil dieser schon zu weit in der Vergangenheit liegt), wird null zurückgegeben.
     */
    private function getOrCreateVerteiltag( int|null $verteiltagId ) : SOLAWI_Verteiltag|null {
        if ( $verteiltagId !== null ) 
            return SOLAWI_Verteiltag::valueOf( $verteiltagId );
        $verteiltag = SOLAWI_Verteiltag::values( 1 );
        if ( count( $verteiltag ) > 0 )
            return $verteiltag[0];
        $verteiltag = SOLAWI_Verteiltag::createFromDatum( new DateTime() );
        foreach ( SOLAWI_Bereich::values() as $bereich )
            $verteiltag->setVerteilungVon( $bereich );
        return $verteiltag;
    }

    /**
     * Gibt eine HTML-Tabellenzeile für einen Mitbauern aus.
     */
    private function getHtmlTableRowMitbauer( SOLAWI_Mitbauer $mitbauer, SOLAWI_Verteiltag $verteiltag, SOLAWI_Verteilstation|null $station, string $cssClass = null ) : string {
        $cssClass = $cssClass !== null ? " class='$cssClass'" : "";
        $result = "<tr$cssClass><td class='left'>" . $mitbauer->getName() . "</td>";
        foreach ( $verteiltag->getVerteilungen() as $bereich ) {
            $anteil = $mitbauer->getErnteanteilIntern( $verteiltag->getDatum() )->getAsHtmlString( $bereich );
            $result .= "<td class='right'>$anteil</td>";
        }
        $result .= "</tr>";
        return $result;
    }
    
    /**
     * Gibt eine HTML-Tabellenzeile für einen Mitbauern aus.
     */
    private function getHtmlTableRowSumme( array $mitbauern, SOLAWI_Verteilstation|null $station, SOLAWI_Verteiltag $verteiltag ) : string {
        $cssClass = " class='stark'";
        $result = "<tr$cssClass><td class='left'>&sum;&nbsp;Kisten</td>";
        foreach ( $verteiltag->getVerteilungen() as $bereich )
            $result .= "<td class='right'>" . SOLAWI_Mitbauer::getHtmlSummierteAnteile( $mitbauern, $verteiltag, $bereich ) . "</td>";
        $result .= "</tr>";
        return $result;
    }
}