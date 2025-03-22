

<?php
session_start(); // Start the session

// Include database connection file
require_once("../Database/db.php");

if (isset($_GET['property_id'])) {
    $propertyId = $_GET['property_id'];
    // SQL query to fetch property details and account numbers by property ID
    $sql = "
        SELECT 
            a.application_id,
            a.user_id,
            a.property_id,
            a.applicant_address,
            a.email_address,
            a.relationship_to_owner,
            a.description,
            a.title_deed,
            a.identity_proof,
            a.additional_documents,
            a.status,
            a.created_at,
            a.updated_at,
            p.address,
            p.size,
            p.type,
            p.owner,
            GROUP_CONCAT(ac.account_number SEPARATOR ', ') AS account_numbers,
            p.updated_at 
        FROM 
            rate_clearance_applications a
        LEFT JOIN 
            accounts ac ON a.property_id = ac.property_id
        LEFT JOIN 
            properties p ON a.property_id = p.property_id
        WHERE 
            a.property_id = ?
        GROUP BY 
            p.property_id
    ";

    try {
        // Prepare and execute the query
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $propertyId, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch the property details
        if ($property = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Display property details
            echo "<div class='property-container'>";
            echo "<h2 style='color: #333;'>Property Details</h2>";
            echo "<p style='color: #666; font-size: 0.9em;'>Posted On: " . htmlspecialchars($property['created_at']) . "</p>";

            echo "<div class='panels-container'>";
            echo "<div class='property-details'>";
            echo "<h3>Property Information</h3>";
            echo "<p><strong>ID:</strong> " . htmlspecialchars($property['property_id']) . "</p>";
            echo "<p><strong>Owner:</strong> " . htmlspecialchars($property['owner']) . "</p>";
            echo "<p><strong>Address:</strong> " . htmlspecialchars($property['address']) . "</p>";
            echo "<p><strong>Size:</strong> " . htmlspecialchars($property['size']) . " m2</p>";
            echo "<p><strong>Type:</strong> " . htmlspecialchars($property['type']) . "</p>";
            echo "<p><strong>Account Numbers:</strong> " . htmlspecialchars($property['account_numbers']) . "</p>";
            echo "</div>";

            echo "<div class='applicant-details'>";
            echo "<h3>Applicant Information</h3>";
            echo "<p><strong>Application ID:</strong> " . htmlspecialchars($property['application_id']) . "</p>";
            echo "<p><strong>User ID:</strong> " . htmlspecialchars($property['user_id']) . "</p>";
            echo "<p><strong>Applicant Address:</strong> " . htmlspecialchars($property['applicant_address']) . "</p>";
            echo "<p><strong>Email Address:</strong> " . htmlspecialchars($property['email_address']) . "</p>";
            echo "<p><strong>Relationship to Owner:</strong> " . htmlspecialchars($property['relationship_to_owner']) . "</p>";
            echo "<p><strong>Description:</strong> " . htmlspecialchars($property['description']) . "</p>";
            echo "<p><strong>Status:</strong> " . htmlspecialchars($property['status']) . "</p>";
            echo "</div>";
            echo "</div>";

            echo "<div class='supporting-documents'>";
            echo "<h3>Supporting Documents</h3>";
            if (!empty($property['identity_proof'])) {
                echo "<p><strong>Identity Proof:</strong> <a href='download.php?file=" . htmlspecialchars($property['identity_proof']) . "'>Download</a></p>";
            }
            if (!empty($property['title_deed'])) {
                echo "<p><strong>Title Deed:</strong> <a href='download.php?file=" . htmlspecialchars($property['title_deed']) . "'>Download</a></p>";
            }
            if (!empty($property['additional_documents'])) {
                echo "<p><strong>Additional Documents:</strong> <a href='download.php?file=" . htmlspecialchars($property['additional_documents']) . "'>Download</a></p>";
            }
            echo "</div>";

            echo "</div>";
        } else {
            echo "No property found with the given ID.";
        }
    } catch (PDOException $e) {
        echo "Error fetching properties: " . $e->getMessage();
    }
} else {
    echo "No property ID provided.";
}

// Add Reply Button
echo "<div style='margin-top: 20px;'>";
echo "<form action='add_reply.php' method='POST'>";
echo "<input type='hidden' name='property_id' value='" . htmlspecialchars($property_id) . "'>";
echo "<input type='hidden' name='content' value='" . htmlspecialchars($content) . "'>";

echo "<button type='submit'>Reply</button>";
echo "</form>";
echo "</div>";
?>

?>
       
<style>
    .property-container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background-color: #f9f9f9;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .property-container h2 {
        color: #333;
        margin-top: 0;
    }

    .property-container p {
        color: #666;
        font-size: 0.9em;
    }

    .panels-container {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }

    .property-details, .applicant-details {
        flex: 1;
        margin: 0 10px;
        padding: 15px;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .property-details h3, .applicant-details h3 {
        margin-top: 0;
        color: #333;
    }

    .property-details p, .applicant-details p {
        margin: 10px 0;
    }

    .property-details p strong, .applicant-details p strong {
        color: #333;
    }

    .supporting-documents {
        margin-top: 20px;
        padding: 15px;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .supporting-documents h3 {
        margin-top: 0;
        color: #333;
    }

    .supporting-documents p {
        margin: 10px 0;
    }

    .supporting-documents p strong {
        color: #333;
    }

    .property-details a, .applicant-details a, .supporting-documents a {
        color: #007bff;
        text-decoration: none;
    }

    .property-details a:hover, .applicant-details a:hover, .supporting-documents a:hover {
        text-decoration: underline;
    }
</style>
