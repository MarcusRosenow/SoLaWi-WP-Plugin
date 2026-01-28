/**
 * Wird aufgerufen, wenn in einem Form-Element eine Änderung vorgenommen wird.
 */
function solawi_changed(id) {
	button = document.getElementById("submit_" + id);
	if (!button.hidden)
		return;
	show_buttons(id, true);

	resultDiv = document.getElementById("result_" + id);
	if ( resultDiv != null )
		resultDiv.innerHTML = "";


	button = document.getElementById("reset_" + id);
	if ( button != null )
		button.onclick = function () {
			show_buttons(id, false);
		};
}

/**
 * zeigt den submit/reset-Button mit der übergebenen id an.
 */
function show_buttons(id, visible) {
	button = document.getElementById("submit_" + id);
	button.hidden = !visible;

	button = document.getElementById("reset_" + id);
	if ( button != null )
		button.hidden = !visible;
}

/**
 * Blendet textareas aus und ein. je nachdem, wie die zugehörigen Checkboxes der Bereiche angehakt sind
 * @param {int} id 
 */
function solawi_show_textareas(id) {
	form = document.getElementById("form_" + id);
	checkboxen = form.getElementsByTagName("input");
	textareas = form.getElementsByTagName("textarea");
	inputs = form.getElementsByTagName("input");
	aktiveBereiche = [];
	for (i = 0; i < checkboxen.length; i++) {
		checkbox = checkboxen[i];
		if (checkbox.type == "checkbox" && checkbox.name.startsWith("verteilung_")) {
			bereich = checkbox.name.substring("verteilung_".length);
			checked = checkbox.checked;
			div = document.getElementById( "input_" + bereich + id );
			div.hidden = !checked;
		}
	}
}

/**
 * Steuert die Sichtbarkeit von Registerkarten
 * @param {int} id 
 */
function registerkarteSelected(id, gruppenId) {
	ueberschriften = document.getElementsByName( "registerkarteHeader_" + gruppenId );
	seiten = document.getElementsByName( "registerkarteBody_" + gruppenId );

	for (i = 0; i < ueberschriften.length; i++) {
		elem = ueberschriften[i];
		elem.className = "header_unselected";
		if (elem.id == "registerkarteHeader_" + gruppenId + "_"  + id)
			elem.className = "header_selected";
	}

	for (i = 0; i < seiten.length; i++) {
		elem = seiten[i];
		if (elem.id == "registerkarteBody_" + gruppenId + "_" + id) {
			elem.className = "registerkarte_visible";
		} else {
			elem.className = "registerkarte_hidden";
		}
	}
}

/**
 * Wird aufgerufen, wenn im Admin-Bereich der Anteil in einem Feld auf der Ernteanteile-Seite geändert wird.
 * @param {string} id Die Id des einzelnen Feldes.
 */
function anteilChanged(id) {
	feld = document.getElementById(id);
	if (feld.value.search(/^([0-9]|0[.,]5)$/) > -1)
		feld.style.color = "black";
	else
		feld.style.color = "red";
}

/**
 * Wird aufgerufen, wenn im Admin-Bereich das Filter-Feld gefüllt wird
 */
function filterDiv( filterId ) {
	filterValue = document.getElementById(filterId).value;
	kinder = document.getElementsByTagName("div");
	for (i = 0; i < kinder.length; i++) {
		div = kinder[i];
		if (div.id == null || !div.id.startsWith("___") && !div.id.startsWith("neu___"))
			continue;
		div_id = div.id.toLowerCase();
		div_id = div_id.substring(div_id.search("___") + 3);
		if (filterValue == "" || div_id.search(filterValue.toLowerCase()) > -1) {
			div.style.display = "block";
		} else {
			div.style.display = "none";
		}
	}
}

/**
 * Berechnet anhand der übergebenen Werte den Richtwert
 * 
 * @param {int} runde 
 * @param {string} bereich 
 * @param {float} richtwertProAnteil 
 */
function berechneRichtwert( runde, bereich, richtwertProAnteil ) {
	anzahlFeld = document.getElementById( "anzahl" + runde + bereich );
	richtwertFeld = document.getElementById( "richtwert" + runde + bereich );
	richtwertFeld.value = formatWaehrung( anzahlFeld.value * richtwertProAnteil );
}

function berechneGesamtwert( runde ) {
	gesamtFeld = document.getElementById( "gesamt" + runde );
	bereiche = [ "fleisch", "mopro", "gemuese" ];
	gesamtpreis = 0;
	for ( i=0; i<bereiche.length; i++)
		gesamtpreis += parseFloat( document.getElementById( "preis" + runde + bereiche[i] ).value );
	gesamtFeld.value = formatWaehrung( gesamtpreis );
}

function formatWaehrung( wert ) {
	if ( wert == 0 ) {
		return "0,00 €";
	} else {
		wert = wert * 100 + ""; // in Cent
		wert = wert.substring( 0, wert.length - 2 ) + "," + wert.substring( wert.length - 2 );
		wert += " €";
		return wert;
	}
}

function setzeMitbauerInWidgetErfassungGebote( wert ) {
	formulare = document.getElementsByTagName( "form" );
	for ( i=0; i<formulare.length; i++) {
		formular = formulare[i];
		if ( formular["runde"] != null && formular["mitbauer"] != null ) {
			mitbauer = formular["mitbauer"];
			mitbauer.value = wert;

		}
	}
}