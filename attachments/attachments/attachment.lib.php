<?php

/**
 * attachment.lib.php
 *
 * @author Giada Pintos @ Pingitore Informatica
 */

/**
 * Permette l'invio diretto del file allegato selezionato
 * @return array
 */
function viewAttachment(): array
{
    $id_attachment = globals::get_POST('id_a');
    $destinationDir = parametri::get("folder_attachments");
    $attachmentTypes = explode(',', parametri::get("attachment_type"));

    $sqlStmt = "SELECT nome_file FROM allegati 
                WHERE id = :id";

    $par_arr = array(':id' => $id_attachment);

    try {
        $dbConn = db::connect();
        $sth = $dbConn->prepare($sqlStmt);
        $sth->execute($par_arr);
        $row = $sth->fetch(PDO::FETCH_OBJ);


        if (!empty($row)) {

            $attachmentUrl = $row->nome_file;
            $rowArr['miofile'] = base64_encode(file_get_contents($destinationDir . $attachmentUrl));
            $rowArr['ajax_result'] = 'ok';

            $fileExtension = pathinfo($attachmentUrl, PATHINFO_EXTENSION);

            if (in_array($fileExtension, $attachmentTypes)) {
                // Settare Content-Type header appropriato
                if ($fileExtension == 'pdf') {
                    header('Content-Type: application/pdf');
                } elseif ($fileExtension == 'jpeg' || $fileExtension == 'jpg') {
                    header('Content-Type: image/jpeg');
                }

                // Settare Content-Disposition header per inviare l'allegato
                header("Content-Disposition: attachment; filename='$attachmentUrl'");

                // Output del file
                readfile($destinationDir . $attachmentUrl);
                log_event::write(OPEN_ATTACHMENT_CONFIRMED, current_user::id() . "Viewing file" . $id_attachment);
            } else {
                $rowArr['ajax_result'] = 'error';
                $rowArr['ajax_error'] = 'Allegato non supportato';
                return $rowArr;

                // Tipo del file non autorizzato
                log_event::write(OPEN_ATTACHMENT_FAILED, "Failed viewing file" . $id_messaggio . "from user: " . current_user::id());
            }

        } else {
            $rowArr['ajax_result'] = 'error';
            $rowArr['ajax_error'] = 'Allegato non trovato';
            return $rowArr;

        }
    } catch (PDOException $e) {
        errorManager::ajaxNotify($e);
        $rowArr['ajax_result'] = 'error';
        $rowArr['ajax_error'] = 'Non è stato possibile recuperare allegato';
        return $rowArr;
    }
    return $rowArr;
}


/**
 * Ellimina l'allegato selezionato se presente nel DB
 * @return string[]
 */
function deleteAttachment(): array
{
    $id_attachment = globals::get_POST('id_a');
    $destinationDir = parametri::get("folder_attachments");

    // Query per avere il nome del file
    $sqlStmt = "SELECT * FROM allegati WHERE id = :id";

    $par_arr = array(':id' => $id_attachment);

    try {
        $dbConn = db::connect();
        $sth = $dbConn->prepare($sqlStmt);
        $sth->execute($par_arr);
        $row = $sth->fetch(PDO::FETCH_OBJ);

        if (!empty($row)) {
            $attachmentUrl = $row->nome_file;

            // Eliminazione dall'archivio filesystem
            $file_path = $destinationDir . $attachmentUrl;

            if (file_exists($file_path)) {
                unlink($file_path);
            }

            //Elliminazione dal database
            $deleteStmt = "DELETE FROM allegati WHERE id = :id";

            $deleteParArr = array(
                ':id' => $row->id
            );

            $deleteSth = $dbConn->prepare($deleteStmt);
            $deleteSth->execute($deleteParArr);

            return array('ajax_result' => 'ok');

        } else {
            return array(
                'ajax_result' => 'error',
                'ajax_error' => 'Allegato non trovato'
            );
        }
    } catch (PDOException $e) {
        errorManager::ajaxNotify($e);
        return array(
            'ajax_result' => 'error',
            'ajax_error' => 'Non è stato possibile eliminare l\'allegato'
        );
    }
}


/**
 * Processa il caricamento di un nuovo allegato basandosi sui parametri del DB
 * @return array|string|null Un array contenente il percorso del file allegato se il caricamento è riuscito e non ci sono stati errori.
 */
function newAttachment(): array|string|null
{
    if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
        return null; // Non è stato fornito alcun allegato o si è verificato un errore nel caricamento
    }

    $file_name = basename($_FILES['attachment']['name']);
    $destinationDir = parametri::get("folder_attachments");
    $attachment_type = explode(',', parametri::get("attachment_type"));
    $attachment_size = parametri::get("attachment_size");
    $attachmentSizeLimit = $attachment_size * 1024;
    $filePath = null;

    // Check se c'è un file allegato
    if ((isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK)) {
        $uploadFile = $destinationDir . $file_name;

        // Controllo sul tipo del file in base ai parametri del DB
        $fileExt = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $attachment_type)) {
            log_event::write(UPLOAD_ATTACHMENT_FAILED, "User " . current_user::id() . " tried to upload a file with invalid type" . $filePath);

            return [
                'ajax_result' => 'error',
                'ajax_error' => 'Invalid file type.'
            ];
        }

        // Controllo della size dell'allegato in base ai parametri del DB
        if ($_FILES['attachment']['size'] >= $attachmentSizeLimit) {
            log_event::write(UPLOAD_ATTACHMENT_FAILED, "User " . current_user::id() . " tried to upload a file bigger than what's autorized" . $filePath);

            return [
                'ajax_result' => 'error',
                'ajax_error' => 'File size exceeds the maximum allowed size.'
            ];
        }

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadFile)) {
            $filePath = $uploadFile;
            log_event::write(UPLOAD_ATTACHMENT_CONFIRMED, current_user::id() . "Uploaded file" . $filePath);
            return $filePath;
        } else {
            return [
                'ajax_result' => 'error',
                'ajax_error' => 'File upload failed.'
            ];
        }
    }
    return null;
}
