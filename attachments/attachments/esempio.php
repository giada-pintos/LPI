<?php

// Esempio di come usare la lib.

/**
 * Ritorna la lista dei messaggi appartenenti al topic (chat) selezionato
 * @param $id_topic
 * @param $fl_attachment
 * @return array
 */
function ChatHistory($id_topic, $fl_attachment = null): array
{
    $sqlStmt = 'SELECT c.*, a.nome_file AS allegato_nome, a.id AS idAllegato
                FROM comunicazioni c
                    LEFT JOIN allegati a ON c.id = a.id_messaggio
                    WHERE c.id_topic = :id
                    AND c.id_utente_nuvola = :id_utente_nuvola';

    $par_arr = array(
        ':id' => $id_topic,
        ':id_utente_nuvola' => current_user::id()
    );

// Aggiungiamo la clausola di filtro se è fornito
    if (!empty($fl_attachment)) {
        $sqlStmt .= ' AND a.nome_file LIKE :fl_attachment';
        $par_arr[':fl_attachment'] = '%' . $fl_attachment . '%';
    }

    $sqlStmt .= ' ORDER BY dt_rec';

    $buffer = "<ul class='list'>";

    try {
        $dbConn = db::connect();
        $sth = $dbConn->prepare($sqlStmt);
        $sth->execute($par_arr);

        $buffer .= "<h3 class='mt-2' id='chat-header-text' data-id_topic='$id_topic'></h3>";

        while ($row = $sth->fetch(PDO::FETCH_OBJ)) {
            if ($row->fl_read == 1) {
                $classMessaggio = ($row->fl_mittente == MITTENTE_NUVOLA ? 'float-right other-message' : ' my-message');
            } else {
                $classMessaggio = ($row->fl_mittente == MITTENTE_NUVOLA ? 'float-right other-message nuovi-messaggi' : ' my-message');
            }

            // Parte grafica della Libreria (da integrare nel proprio modulo)
            $attachmentButton = '';
            if (!empty($row->allegato_nome)) {
                $attachmentButton = "<i class='fa-solid fa-paperclip' id='attachement_button' data-id='$row->id' onclick='viewAttachment($row->idAllegato)'></i>
    <i class='fa-solid fa-x' onclick='deleteAttachment($row->idAllegato)'></i>";
            }

            $msg = htmlspecialchars($row->messaggio);
            $buffer .= "<li class='clearfix'>
        <div class='message-date " . ($row->fl_mittente == MITTENTE_NUVOLA ? 'float-right' : '') . "'>
        <span class='message-date-time'>$row->dt_rec</span>
        $attachmentButton
        </div>
        <div class='message $classMessaggio'>$msg</div>
    </li>";
        }

        $sth->closeCursor();

        // Aggiornare la flag "letto"
        $sqlStmt = 'UPDATE comunicazioni
    SET fl_read = 1
    WHERE id_topic = :id
    AND id_utente_nuvola = :id_utente_nuvola
    AND fl_mittente = ' . MITTENTE_ARCOBALENO;

        $par_arr_2 = array(
            ':id' => $id_topic,
            ':id_utente_nuvola' => current_user::id()
        );

        $sth = $dbConn->prepare($sqlStmt);
        $sth->execute($par_arr_2);

        $rowArr['ajax_result'] = 'ok';
        $rowArr['chat_history'] = $buffer;
    } catch (PDOException $e) {
        errorManager::ajaxNotify($e);
        $rowArr['ajax_result'] = 'error';
        $rowArr['ajax_error'] = 'Non è stato possibile recuperare i messaggi';
    }

    return $rowArr;
}

>