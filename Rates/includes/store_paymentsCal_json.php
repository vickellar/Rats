<?php
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Initialize an array to store the form data
    $formData = [];

    // Collect account holder details
    $formData['holder'] = $_POST['accountHolder'];
    $formData['processingFee'] = $_POST['processingFee'];
    $formData['totalBalance'] = $_POST['totalBalance'];
    $formData['overallTotalBalance'] = $_POST['overallTotalBalance'];

    // Collect account details
    $formData['accounts'] = [];
    $accountCount = $_POST['accountCount']; // Assuming you have a hidden input for account count

    for ($i = 1; $i <= $accountCount; $i++) {
        $account = [
            'accountNumber' => $_POST['accountNumber' . $i],
            'balances' => []
        ];

        // Assuming you have inputs for each month's balance
        for ($month = 1; $month <= 3; $month++) {
            $account['balances'][] = [
                'month' => 'Month ' . $month,
                'balance' => $_POST['balance' . $i . '_' . $month]
            ];
        }

        $formData['accounts'][] = $account;
    }

    // Convert the form data to JSON
    $jsonFormData = json_encode($formData, JSON_PRETTY_PRINT);

    // Define the file path
    $filePath = 'form_data.json';

    // Write the JSON data to the file
    file_put_contents($filePath, $jsonFormData);

    // Output success message
    echo "Data successfully saved to $filePath";
} else {
    echo "Invalid request method";
}
?>