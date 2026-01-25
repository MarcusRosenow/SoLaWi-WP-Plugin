<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

final class SOLAWI_ShortcodeVerteiltage extends SOLAWI_AbstractShortcode {

    public function getKey() : string {
        return "verteiltage";
    }

    public function savePostdata( int $id, array $data ) : void {
        $stationId = intval( $data[ "station" ] );
        $vt2mb = SOLAWI_Verteiltag2Mitbauer::valueById( $id );
        $vt2mb->setVerteilstation( $stationId >= 0 ? SOLAWI_Verteilstation::valueOf( $stationId ) : null );
        $vt2mb->setGemueseErnten( isset( $data[ "ernten" ] ) );
        $vt2mb->setKistenPacken( isset( $data[ "packen" ] ) );
        SOLAWI_Repository::instance()->save( $vt2mb );
    }

    /**
     * Gibt das HTML für die nächsten Verteiltage aus
     */
    public function getHtml( SOLAWI_Mitbauer $mitbauer, int|null $tag_id = null ) : string {
        if ( isset( $tag_id ) ) {
            $tag = SOLAWI_Verteiltag::valueOf( $tag_id );
            if ( $tag == null )
                return ""; // Das passiert, wenn Kalendereinträge der Vergangenheit angeklickt werden
            return $this->getHtmlFuerEinenTag( $tag, $mitbauer);
        }
        $tage = SOLAWI_Verteiltag::values( 3 );
        $widget = new SOLAWI_WidgetRegisterkarten();
        foreach ( $tage as $tag )
            $widget->add( $tag->getId(), SOLAWI_formatDatum( $tag->getDatum() ), $this->getHtmlFuerEinenTag( $tag, $mitbauer) );
        return $widget->getHtml();
    }

	/**
	 * Ein Inhaltsblock für einen Verteiltag.
	 */
    private function getHtmlFuerEinenTag( SOLAWI_Verteiltag $tag, SOLAWI_Mitbauer $mitbauer ) : string {
        $vt2mb = SOLAWI_Verteiltag2Mitbauer::valueOf( $tag, $mitbauer );
        $id = $vt2mb->getId();
        $onInputEvent = $this->getOnInputEventHtml( $id );
        $result = "<p class='has-text-align-center'><b>Was wird verteilt?</b><br>";
        foreach ( $tag->getVerteilungen() as $bereich ) {
            $result .= $bereich->getName() . "<br>";
            $text = $tag->getText( $bereich );
            if ( $text !== null && $text !== "" )
                $result .= $text . "<br>";
        }
        $result .= "</p>";
        $result .= '<!-- wp:separator --><hr class="wp-block-separator has-alpha-channel-opacity"/><!-- /wp:separator -->';
        // Nur zukünftige Verteiltage sind bearbeitbar
        if ( $tag->getDatum() > new DateTime() ) {
            $result .= "<form method='POST'>";
            if ( $mitbauer->hasErnteAnteile( $tag->getVerteilungen(), $tag->getDatum() ) ) {
                $result .= "<p class='has-text-align-center'><b>Ich hole meine Kiste hier ab:</b><br>";
                $result .= $this->getHtmlAuswaehlbareVerteilstationen( $id, $mitbauer, $vt2mb->getVerteilstation() );
                $result .= "<br>Andere Station? Anderes Anliegen? Dann melde dich <a href='" . $this->getKontaktFormularUrl( $tag, $mitbauer ) . "'>HIER</a>.</p>";
            }
            else {
                $result .= "<p class='has-text-align-center'>An diesem Tag bekommst du keinen Ernteanteil.</p>";
            }
            if ( $tag->isVerteilungVon( SOLAWI_Bereich::GEMUESE ) ) {
                $result .= "<p class='has-text-align-center'>" . $this->getHtmlGemueseErnten( $id, $vt2mb ) . "</p>";
                $result .= "<p class='has-text-align-center'>" . $this->getHtmlKistenPacken( $id, $vt2mb ) . "</p>";
            }
            $result .= "<p class='has-text-align-center'>" . $this->getSubmitButtonHtml( $id ) . "</p>";
            $result .= "</form>";
        } else {
            $result .= "Deine Verteilstation heute: " . $vt2mb->getVerteilstation()->getName();
        }
        return $result;
    }

    /**
     * Baut den Link für das Kontaktformular zusammen
     */
    private function getKontaktFormularUrl( SOLAWI_Verteiltag $tag, SOLAWI_Mitbauer $mitbauer ) : string {
        $kontaktUrl = "kontakt-anfahrt/";
        $kontaktUrl .= "?betreff=Verteiltag " . SOLAWI_formatDatum( $tag->getDatum() );
        $kontaktUrl .= "&your-message=(Bitte diesen Text so anpassen, dass er passt...)%0A
%0A
Hallo Eileen,%0A
%0A
ich möchte die Verteilstation am " . SOLAWI_formatDatum( $tag->getDatum() ) . " wechseln.%0A
Ich möchte in [...] abholen.%0A
Es ist [noch nicht/bereits] mit dem Leiter der Verteilgruppe abgestimmt.%0A
Kannst du bitte veranlassen, dass meine Kiste dort bereitsteht?!%0A
%0A
Vielen Dank.%0A
%0A
Liebe Grüße%0A
{$mitbauer->getName()}
";
        $kontaktUrl .= "&your-email=" . $mitbauer->getEmail();
        $kontaktUrl .= "&your-name=" . $mitbauer->getName();
        return site_url( $kontaktUrl );
    }

    private function getHtmlFuerZuVerteilendenBereich( SOLAWI_Bereich $bereich, string|null $text ) : string {
    }

    /**
     * Baut die Radiogroup für die auswählbaren Verteilstationen
     */
    private function getHtmlAuswaehlbareVerteilstationen( int $id, SOLAWI_Mitbauer $mitbauer, SOLAWI_Verteilstation|null $aktuelleStation ) : string {
        // Der Hof ist immer auswählbar
        $auswaehlbareVerteilstationen = [ SOLAWI_Verteilstation::valueOf( 0 ) ];
        // Meine Standardstation ist auch auswählbar
        if ( !in_array( $mitbauer->getVerteilstation(), $auswaehlbareVerteilstationen ) )
            $auswaehlbareVerteilstationen[] = $mitbauer->getVerteilstation();
        // Und wenn der Admin eine andere Verteilstation angegeben hat, als ich normalerweise auswählen könnte
        if ( !in_array( $aktuelleStation, $auswaehlbareVerteilstationen ) )
            $auswaehlbareVerteilstationen[] = $aktuelleStation;
        // Und der Urlaub ist immer auswählbar
        if ( $aktuelleStation !== null )
            $auswaehlbareVerteilstationen[] = null;

        $onInputEvent = $this->getOnInputEventHtml( $id );
        $result = "";
		foreach ( $auswaehlbareVerteilstationen as $station ) {
			$station_id = $station !== null ? $station->getId() : -1;
            $name = $station !== null ? $station->getName() : "Keine Abholung!";
			$checked = $station === $aktuelleStation ? " checked" : "";
            $result .= "-&nbsp;<input type='radio' id='station$station_id' name='station' value='$station_id'$checked $onInputEvent>";
            $result .= " <label for='station$station_id'>$name</label>&nbsp;-";
		}
        return $result;
    }

    /**
     * Baut die Checkbox für das Ernten des Gemüses
     */
    private function getHtmlGemueseErnten( int $id, SOLAWI_Verteiltag2Mitbauer $vt2mb ) : string {
        $tag = $vt2mb->getVerteiltag();
        $mitbauer = $vt2mb->getMitbauer();

        $teilnehmendeMitbauern = [];
        foreach ( SOLAWI_Verteiltag2Mitbauer::values( $tag ) as $otherVt2Mb ) {
            if ( $otherVt2Mb->isGemueseErnten() )
                $teilnehmendeMitbauern[] = $otherVt2Mb->getMitbauer();
        }
        $teilnehmendeMitbauern = SOLAWI_arrayToString( $teilnehmendeMitbauern, "getName" );

        $onInputEvent = $this->getOnInputEventHtml( $id );
        $checked = $vt2mb->isGemueseErnten() ? " checked" : "";
        $result = "<input type='checkbox' id='ernten' name='ernten'$checked $onInputEvent>";
        $result .= " <label for='ernten'><b>Ich helfe beim Ernten des Gemüses ({$tag->getStartGemueseErnten()} - {$tag->getEndeGemueseErnten()} Uhr)</b></label><br>";
        if ( $teilnehmendeMitbauern == "" )
            $result .= "Bisher keine Anmeldungen";
        else
            $result .= "Anmeldungen: $teilnehmendeMitbauern";
        return $result;
    }

    /**
     * Baut die Checkbox für das Kisten packen
     */
    private function getHtmlKistenPacken( int $id, SOLAWI_Verteiltag2Mitbauer $vt2mb ) : string {
        $tag = $vt2mb->getVerteiltag();
        $mitbauer = $vt2mb->getMitbauer();
        
        $teilnehmendeMitbauern = [];
        foreach ( SOLAWI_Verteiltag2Mitbauer::values( $tag ) as $otherVt2Mb ) {
            if ( $otherVt2Mb->isKistenPacken() )
                $teilnehmendeMitbauern[] = $otherVt2Mb->getMitbauer();
        }
        $teilnehmendeMitbauern = SOLAWI_arrayToString( $teilnehmendeMitbauern, "getName" );

        $onInputEvent = $this->getOnInputEventHtml( $id );
        $checked = $vt2mb->isKistenPacken() ? " checked" : "";
        $result = "<input type='checkbox' id='packen' name='packen'$checked $onInputEvent>";
        $result .= " <label for='packen'><b>Ich helfe beim Packen der Kisten ({$tag->getStartKistenPacken()} - {$tag->getEndeKistenPacken()} Uhr)</b></label><br>";
        if ( $teilnehmendeMitbauern == "" )
            $result .= "Bisher keine Anmeldungen";
        else
            $result .= "Anmeldungen: $teilnehmendeMitbauern";
        return $result;
    }
}