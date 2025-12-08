<?php
if ($f == "bank_transfer") {
    // Initialize response array
    $data = array('status' => 500, 'message' => 'An unknown error occurred.');

    // Backend error logging function
    function logBackendError($message) {
        $logFile = __DIR__ . '/bank_transfer_errors.log';
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
    }

    try {
        // Check session validity
        if (Wo_CheckSession($hash_id) !== true) {
            throw new Exception("Invalid session. Please log in again.");
        }

        // Validate required inputs
        if (empty($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 1) {
            throw new Exception("Invalid price. Please provide a valid amount.");
        }

        // Secure inputs
        $description = !empty($_POST['description']) ? Wo_Secure($_POST['description']) : '';
        $mediaFilename = '';

        // Handle file upload if a thumbnail is provided
        if (!empty($_FILES["thumbnail"])) {
            $fileInfo = array(
                'file' => $_FILES["thumbnail"]["tmp_name"],
                'name' => $_FILES['thumbnail']['name'],
                'size' => $_FILES["thumbnail"]["size"],
                'type' => $_FILES["thumbnail"]["type"],
                'types' => 'jpeg,jpg,png,bmp,gif'
            );

            $media = Wo_ShareFile($fileInfo);
            if (empty($media['filename'])) {
                throw new Exception("File upload failed or unsupported file type.");
            }
            $mediaFilename = $media['filename'];
        }

        // Process payment type
        if (!empty($_POST['payment_type']) && $_POST['payment_type'] == 'wallet') {
            $insert_id = Wo_InsertBankTrnsfer(array(
                'user_id' => $wo['user']['id'],
                'description' => $description,
                'price' => Wo_Secure($_POST['price']),
                'receipt_file' => $mediaFilename,
                'mode' => 'wallet'
            ));
        } elseif (!empty($_POST['payment_type']) && $_POST['payment_type'] == 'funding') {
            if (empty($_POST['fund_id']) || !is_numeric($_POST['fund_id']) || $_POST['fund_id'] < 1) {
                throw new Exception("Price or fund ID is invalid for funding payment.");
            }

            $fund_id = Wo_Secure($_POST['fund_id']);
            $fund = $db->where('id', $fund_id)->getOne(T_FUNDING);
            if (empty($fund)) {
                throw new Exception("Fund not found for the provided fund ID.");
            }

            $insert_id = Wo_InsertBankTrnsfer(array(
                'user_id' => $wo['user']['id'],
                'description' => $description,
                'price' => Wo_Secure($_POST['price']),
                'receipt_file' => $mediaFilename,
                'mode' => 'donate',
                'fund_id' => $fund_id
            ));
        } else {
            // Check if pro_packages_types is defined and is an array
            if (empty($wo['pro_packages_types']) || !is_array($wo['pro_packages_types'])) {
                throw new Exception("Pro packages types are not defined or invalid.");
            }

            if (empty($_POST['type']) || !in_array($_POST['type'], array_keys($wo['pro_packages_types']))) {
                throw new Exception("Invalid or missing type for pro package.");
            }

            $pro = $wo['pro_packages'][$wo['pro_packages_types'][$_POST['type']]];
            $insert_id = Wo_InsertBankTrnsfer(array(
                'user_id' => $wo['user']['id'],
                'description' => $description,
                'price' => $pro['price'],
                'receipt_file' => $mediaFilename,
                'mode' => Wo_Secure($_POST['type'])
            ));
        }

        // Check if the insert was successful
        if (empty($insert_id)) {
            throw new Exception("Failed to insert bank transfer record.");
        }

        // Success response
        $data = array(
            'status' => 200,
            'message' => $success_icon . $wo['lang']['bank_transfer_request']
        );
    } catch (Exception $e) {
        // Log the error
        logBackendError($e->getMessage());

        // Return error response
        $data = array(
            'status' => 500,
            'message' => $e->getMessage()
        );
    }

    // Return JSON response
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}