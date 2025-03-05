
<?php
session_start();
//require 'db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $applicant_Id = $_SESSION['user_id']; // Retrieve the login in user_ID
    $details = $_POST['details'];

    // Optional : handle file upload for supporting documents 
    if(isset($_FILES['documents']) && $_FILES['documents']['error'] == UPLOAD_ERR_OK){
        $fileTmpPath = $_FILES['documents']['tmp_name'];
        $fileName = $_FILES['documents']['name'];
        $filePath = 'uploads/'.$fileName;

        // move the upload file to the upload directory 
        $move_uploaded_file($fileTmpPath, $filePath);
    }else {
        $filePath = null;   // or handle the error 
    }
    $stmt = $pdo->prepare("INSERT INTO application(applicant_Id, details, $document_path) VALUES(?, ?, ?)");
    if ($stmt -> execute([$applicant_Id, $details, $filePath])) {
        echo "Appliction submitted successfully.";
    } else {
        echo "Error sumitting the application";
    }
}

?>
<form method="POST" enctype="multipart/form-data">
    <textarea name="details" placeholder="Enter application details" required></textarea>
    <input type="file" name = "documents">
    <button type="submit">Submit Application</button>
</form>