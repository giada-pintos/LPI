/**
 * attachment.lib.js
 *
 * @author Giada Pintos @ Pingitore Informatica
 */

ajax_attach = "/lib/attachments/ajax_services.php";


/**
 * Riceve l'allegato selezionato e lo apre se il formato è supportato dal browser
 *
 * @param id_mes (id_mes = id messaggio o id attività)
 */
function viewAttachment(id_mes) {
    $.ajax({
        method: "POST",
        url: ajax_attach,
        data: {
            cmd: "viewAttachment",
            id_a: id_mes
        },
        xhrFields: {
            responseType: 'blob' // Settato a 'blob' per ricevere dati in binario
        },
        success: function (blob) {
            const url = window.URL.createObjectURL(blob);

            // Apri il PDF in una nuova finestra per visualizzare e download
            const newWindow = window.open(url);
            if (!newWindow) {
                alert('Please allow popups for this site to view/download the PDF.');
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert("Error: " + textStatus);
        }
    });
}


/**
 * Ellimina allegato selezionato se esistente
 *
 * @param id_mes (id_mes = id messaggio o id attività)
 */
function deleteAttachment(id_mes) {
    $.ajax({
        method: "POST",
        url: ajax_attach,
        data: {
            cmd: "deleteAttachment",
            id_a: id_mes
        },

        success: function (response) {
            const objVal = JSON.parse(response);

            if (objVal.ajax_result === 'ok') {
                console.log('Allegato eliminato con successo');
                location.reload();
            } else {
                alert('Errore durante l\'eliminazione dell\'allegato: ' + objVal.ajax_error);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert('Errore durante la richiesta AJAX: ' + textStatus);
        }
    });
}
