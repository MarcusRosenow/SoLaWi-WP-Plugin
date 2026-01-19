/**
 * Nach dem Laden des Dokumentes das Senden der Formulare mit AJAX anpassen ...
 */
document.addEventListener('DOMContentLoaded', function () {
	const forms = document.getElementsByTagName('form');
	for (i = 0; i < forms.length; i++) {
		const form = forms[i];
		if ( form["id"] != null && form["id"].value != "-1" && form["clazz"] != null )
			addSubmitListenerForForm( form );
	}
});

function addSubmitListenerForForm( form ) {
  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    id = form["id"].value;
    
    // UI-Hinweise zurücksetzen
    const resDiv = document.getElementById('result_' + id );
    resDiv.textContent = 'Wird gespeichert ...';
    show_buttons( id, false );
	
    // FormData sammeln
    const formData = new FormData(form);

    // nonce & action hinzufügen (wird auch im PHP geprüft)
    formData.append('security', SolawiAjaxForm.nonce);
    formData.append('action', 'solawi_form_submit');
	
    try {
      const resp = await fetch(SolawiAjaxForm.ajax_url, {
        method: 'POST',
        body: formData,
      });

      const json = await resp.json();

      const messages = json?.data?.messages || json?.messages || {};
      message = Object.values(messages).join('<br>');
      const reload = json?.data?.reloadPage || json?.reloadPage || false;
      if ( reload ) {
        message += "<br>";
        message += "<b>Seite wird neu geladen!</b>";
      }
      resDiv.innerHTML = message;
      if ( reload )
        location.reload();
    } catch (err) {
      resDiv.textContent = 'Netzwerkfehler. Bitte erneut versuchen.';
    }

  });
}