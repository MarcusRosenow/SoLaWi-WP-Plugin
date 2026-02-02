<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

final class SOLAWI_AdminPrintBieterverfahren extends SOLAWI_AbstractAdminPrint {

    public function getHtmlBody() : string {
        if ( !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER) ) {
            wp_die('Nicht authorisiert');
        }
        if ( !isset( $_GET['verfahren'] ) || !isset( $_GET['runde'] ) ) {
            wp_die('Verfahren oder Runde fehlt');
        }

        $verfahren = SOLAWI_Bieterverfahren::valueOf( intval( $_GET['verfahren'] ) );
        $runde = intval( $_GET['runde'] );

        $result = "<h1>Gebote aus dem Bieterverfahren</h1>";
        $result .= "Geöffnet von ";
        $result .= SOLAWI_formatDatum( $verfahren->getGueltigAb() ) . " bis ";
        $result .= $verfahren->getGueltigBis() == null ? "offen" : SOLAWI_formatDatum( $verfahren->getGueltigBis() );
        $result .= "<br>Runde $runde von ";
        $result .= $verfahren->getLetzteRunde();

        $result .= $this->getHtmlTableFuerGebote( $verfahren, $runde );
        $result .= $this->getHtmlTableOhneGebote( $verfahren, $runde );
        return $result;
    }

    private function getHtmlTableFuerGebote( SOLAWI_Bieterverfahren $verfahren, int $runde ) : string {
        $result = "<div>";
        $result .= "<h2>Abgegebene Gebote</h2>";

        $result .= "<table border width='100%'><tr><th>Name</th>";
        foreach( SOLAWI_Bereich::values() as $bereich ) {
            $result .= "<th>Anzahl<br>" . $bereich->getName() . "</th>";
            $result .= "<th>Gebot<br>" . $bereich->getName() . "</th>";
        }
        $result .= "</tr>";
        $gebote = $verfahren->getGebote( $runde );
        foreach ( $gebote as $gebot ) {
            $result .= $this->getHtmlZeileGebot( $gebot );
        }
        $result .= "</table></div>";
        return count( $gebote ) == 0 ? "" : $result;
    }

    private function getHtmlZeileGebot( SOLAWI_BieterverfahrenGebot $gebot ) : string {
        $result = "<tr><td>" . $gebot->getMitbauer()->getName() . "</td>";
        foreach( SOLAWI_Bereich::values() as $bereich ) {
            $result .= "<td align=right>" . SOLAWI_formatAnzahl( $gebot->getAnzahl( $bereich ) ) . "</td>";
            $result .= "<td align=right>" . SOLAWI_formatWaehrung( $gebot->getPreis( $bereich ) ) . "</td>";
        }
        $result .= "</tr>";
        return $result;
    }

    private function getHtmlTableOhneGebote( SOLAWI_Bieterverfahren $verfahren, int $runde ) : string {
        $result = "<div>";
        $result .= "<h2>Mitbauern ohne abgegebene Gebote</h2>";

        $result .= "<table border width='100%'><tr><th>Name</th>";
        foreach( SOLAWI_Bereich::values() as $bereich ) {
            $result .= "<th>Anzahl<br>" . $bereich->getName() . "</th>";
            $result .= "<th>Preis alt / neu<br>" . $bereich->getName() . "</th>";
        }
        $result .= "</tr>";
        $gebote = $verfahren->getGebote( $runde );
        foreach ( SOLAWI_Mitbauer::values() as $mitbauer ) {
            if ( $verfahren->getGebot( $runde, $mitbauer ) != null )
                continue;
            if ( $mitbauer->hasErnteanteile( null, $verfahren->getStartDerSaison() ) )
                $result .= $this->getHtmlZeileOhneGebot( $verfahren, $runde, $mitbauer );
        }
        $result .= "</table></div>";
        return count( $gebote ) == 0 ? "" : $result;
    }

    private function getHtmlZeileOhneGebot( SOLAWI_Bieterverfahren $verfahren, int $runde, SOLAWI_Mitbauer $mitbauer ) : string {
        $result = "<tr><td>" . $mitbauer->getName() . "</td>";
        $ea = $mitbauer->getErnteanteilIntern( $verfahren->getStartDerSaison() );
        foreach( SOLAWI_Bereich::values() as $bereich ) {
            $anzahl = $ea->getAnzahl( $bereich );
            $preisAlt = $ea->getPreis( $bereich );
            $preisNeu = $anzahl * $verfahren->getDurchschnittsGebot( $runde, $bereich );
            $result .= "<td align=right>" . SOLAWI_formatAnzahl( $anzahl ) . "</td>";
            $result .= "<td align=right>" . SOLAWI_formatWaehrung( $preisAlt ) . " / " . SOLAWI_formatWaehrung( $preisNeu ) . "</td>";
        }
        $result .= "</tr>";
        return $result;
    }
}
?>