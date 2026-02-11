<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

final class SOLAWI_AdminPrintVerteiltag extends SOLAWI_AbstractAdminPrint {

    public function getHtmlBody() : string {
        if ( !SOLAWI_hasRolle( SOLAWI_Rolle::BAUER, SOLAWI_Rolle::MANAGER, SOLAWI_Rolle::ADMINISTRATOR ) ) {
            wp_die('Nicht authorisiert');
        }
        if ( !isset( $_GET['datum'] ) || !isset( $_GET['bereich'] ) ) {
            wp_die('Datum oder Bereich fehlt');
        }

        $tag = SOLAWI_Verteiltag::valueOf( intval( $_GET['datum'] ) );
        $stationen = SOLAWI_Verteilstation::values();
        $bereich = SOLAWI_Bereich::valueOf( $_GET['bereich'] );

        if ( !$tag->isVerteilungVon( $bereich ) ) {
            wp_die( "Keine Verteilung von " . $bereich->getName() . " am " . SOLAWI_formatDatum( $tag->getDatum() ) . "!" );
        }

        $result = "";
        foreach ( $stationen as $station )
            $result .= $this->getHtmlTableFuerStation( $tag, $station, $bereich );
        
        $result .= $this->getHtmlTableFuerStation( $tag, null, $bereich );
        $result .= $this->getHtmlSummeAnteileInsgesamt( $tag, $bereich );
        return $result;
    }

    private function getHtmlTableFuerStation( SOLAWI_Verteiltag $tag, SOLAWI_Verteilstation|null $station, SOLAWI_Bereich $bereich ) : string {
        $result = "<div class='nopagebreak'>\n";
        $result .= "<h2>" . $bereich->getName() . " am " . SOLAWI_formatDatum( $tag->getDatum() ) . ": ". ( $station === null ? "Keine Abholung" : $station->getName() ) . "</h2>";

        $result .= "<table border width='100%'><tr><th width='50%'>Name</th><th width='25%'>Anzahl Anteile</th><th width='25%'>Abgeholt</th>";
        $relevanteMitbauern = [];
        foreach ( SOLAWI_Mitbauer::values() as $mitbauer ) {
            $anzahlErnteanteile = $mitbauer->getErnteanteil( $bereich, $tag->getDatum() );
            if ( $anzahlErnteanteile == 0 )
                continue;
            $vt2mb = SOLAWI_Verteiltag2Mitbauer::valueOf( $tag, $mitbauer );
            if ( $vt2mb->getVerteilstation() !== $station )
                continue;
            $relevanteMitbauern[] = $mitbauer;
            $result .= "<tr><td>" . $mitbauer->getName() . "</td>";
            $result .= "<td align=right>" . str_replace( ".", ",", strval( $anzahlErnteanteile ) ) . "</td>";
            $result .= "<td></td>";
        }
        $urlaubskisten = false;
        if ( $station != null && $station->isHof() && $tag->getAnzahlUrlaubskisten( $bereich ) > 0 ) {
            $result .= "<tr><td>Urlaubskiste(n)</td>";
            $result .= "<td align=right>" . $tag->getAnzahlUrlaubskisten( $bereich ) . "</td>";
            $result .= "<td></td>";
            $urlaubskisten = true;
        }
        // Summenzeile
        $summe = SOLAWI_Mitbauer::getHtmlSummierteAnteile( $relevanteMitbauern, $tag, $bereich, "<br>", $urlaubskisten );
        $result .= "<tr><td>&sum;&nbsp; Kisten</td><td align=right>$summe</td><td></td></tr>";
        $result .= "</table></div>\n";
        return count( $relevanteMitbauern ) == 0 && !$urlaubskisten ? "" : $result;
    }

    private function getHtmlSummeAnteileInsgesamt( SOLAWI_Verteiltag $tag, SOLAWI_Bereich $bereich ) : string {
        $relevanteMitbauern = [];
        $gemueseErnter = [];
        $kistenPacker = [];
        $summe = $tag->getAnzahlUrlaubskisten( $bereich );
        foreach ( SOLAWI_Mitbauer::values( null, true ) as $mitbauer ) {
            $vt2mb = SOLAWI_Verteiltag2Mitbauer::valueOf( $tag, $mitbauer );
            if ( $vt2mb->isGemueseErnten() )
                $gemueseErnter[] = $mitbauer;
            if ( $vt2mb->isKistenPacken() )
                $kistenPacker[] = $mitbauer;
            $anzahlErnteanteile = $mitbauer->getErnteanteil( $bereich, $tag->getDatum() );
            if ( $anzahlErnteanteile == 0 )
                continue;
            if ( $vt2mb->getVerteilstation() !== null ) {
                $relevanteMitbauern[] = $mitbauer;
                $summe += $anzahlErnteanteile;
            }
        }
        $summeJeAnteil = SOLAWI_Mitbauer::getHtmlSummierteAnteile( $relevanteMitbauern, $tag, $bereich, "<br>", true );
        $result = "<h2>" . $bereich->getName() . " am " . SOLAWI_formatDatum( $tag->getDatum() ) . ": Zusammenfassung</h2>";
        $result .= SOLAWI_formatAnzahl( $summe ) . " zu verteilende Anteile insgesamt an diesem Tag. Davon:<br>";
        $result .= $summeJeAnteil;
        if ( $bereich === SOLAWI_Bereich::GEMUESE ) {
            $result .= "<h3>Helfer beim Ernten</h3>";
            $result .= SOLAWI_arrayToString( $gemueseErnter, "getName" );
            $result .= "<h3>Helfer beim Packen</h3>";
            $result .= SOLAWI_arrayToString( $kistenPacker, "getName" );
        }
        return $result;
    }
}
?>