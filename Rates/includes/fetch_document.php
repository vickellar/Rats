
<<?php 

IF(session_status() === NULL){
    session_start();
}

include("../Database/db.php");

//checks if the file submitted
if(isset($_POST['submit'])){
    $targetDir = "./uploads/";
    $targetFile = $targetDir . basename($_FILE["pdffile"]["name"]);
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    //checking if the is a pdf or png and less than 5 mb

    if($filetype != "pdf" || $fileType != "png" || $_FILE["pdffile"]["size"] > 5000000){
        echo "Error!  Only file with less than 5mb is allowed to be uploaded";

    }else{
        //move the uploaded file to the upload folder.
        if(move_uploaded_file($_FILES["pdffile"]["tmp_name"], $targetFile)){

            //insert the file info onto the database
            $filename = $_FILE["pdffile"]["name"];
            $folder_path = $targetDir;
            $time_stamp = date('Y-m-d H:i:s');
            $pdo = "INSERT INTO file_uploads (filename, folder_path, time_stamp) VALUE ('$filename', '$folder_path', '$time_stamp')";


            if($stm ->query($sql) === TRUE){
                echo "File uploaded successfull";

            }else{
                echo "Error" . $pdo . "<br>" . $stm->error;
            }
        }else{
            echo "Erro file upload";  

        }
    } 
}


?>



<?php 
// Download the the files 

include("../Database/db.php");

$pdo = "SELECT * FROM file_uplaod";
$result = $stm->query($pdo);


?>