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
        $vt2mb->setBemerkung( $data[ "bemerkung" ] );
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
        $result = "";
        foreach ( $tag->getVerteilungen() as $bereich )
            $result .= $this->getHtmlFuerZuVerteilendenBereich( $bereich, $tag->getText( $bereich ) );
        if ( $mitbauer->hasErnteAnteile( $tag->getVerteilungen(), $tag->getDatum() ) ) {
            if ( $tag->getDatum() > new DateTime() ) {
                // Nur zukünftige Verteiltage sind bearbeitbar
                $result .= "<form method='POST'><p class='has-text-align-center'><b>Wo möchtest du deine Kiste(n) abholen?</b><br>";
                $result .= $this->getHtmlAuswaehlbareVerteilstationen( $id, $mitbauer, $vt2mb->getVerteilstation() );
                $result .= "Andere Station? Dann melde dich <a href='" . $this->getKontaktFormularUrl( $tag, $mitbauer ) . "'>HIER</a>.<br>";
                $result .= "<textarea rows='3' cols='50' maxlength='255' placeholder='Bemerkung' name='bemerkung' $onInputEvent>{$vt2mb->getBemerkung()}</textarea><br>";
                $result .= $this->getSubmitButtonHtml( $id ) . "</p>";
                $result .= "</form>";
            } else {
                $result .= "Dein Verteiltag heute: " . $vt2mb->getVerteilstation()->getName();
            }
        }
        else {
            $result .= "<p class='has-text-align-center'>An diesem Tag bekommst du keinen Ernteanteil.</p>";
        }
        return $result;
    }

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
        $result = "<p class='has-text-align-center'><b>Verteilung von " . $bereich->getName() . "</b>";
        if ( $text !== null && $text !== "" )
            $result .= "<br>$text";
        $result .= "</p>";
        return $result;
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
            $result .= "<input type='radio' id='station$station_id' name='station' value='$station_id'$checked $onInputEvent>";
            $result .= " <label for='station$station_id'>$name</label><br>";
		}
        return $result;
    }
}