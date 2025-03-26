<?php
/*
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once '../Database/db.php';
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rates Clearance Calculator</title>
<style>
    body {
        font-family: 'Arial', sans-serif; /* Modern font */
        margin: 10px; /* Reduced margin */
        background-color: #f4f4f4; /* Light background */
    }
    .header {
        background-color: #007bff; /* Blue background */
        padding: 10px; /* Reduced padding for the header */
        color: white; /* White text color */
        text-align: center; /* Centered text */
        margin-bottom: 10px; /* Reduced space below the header */
        border-radius: 8px; /* Rounded corners */
        position: relative; /* Relative positioning for child elements */
    }
    .print-button {
        position: absolute; /* Absolute positioning */
        top: 10px; /* Align to top */
        right: 10px; /* Align to right */
        background-color: white; /* Initial background color */
        color: black; /* Text color */
        border: none; /* No border */
        padding: 5px 10px; /* Reduced padding */
        border-radius: 4px; /* Rounded corners */
        cursor: pointer; /* Pointer cursor */
        transition: background-color 0.3s; /* Smooth transition */
    }
    .container {
        display: flex;
        justify-content: space-between;
        max-width: 800px;
        margin: auto;
        padding: 5px; /* Reduced padding */
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #ffffff; /* White background for the form */
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    }
    .form-section, .accounts-section {
        width: 45%; /* Adjusted width for sections */
        padding: 5px; /* Reduced padding */
    }
    .input-group {
        display: flex;
        align-items: center; /* Align items vertically centered */
        margin-bottom: 5px; /* Reduced space between rows */
    }
    button {
        margin-top: 5px; /* Reduced margin */
        padding: 5px 10px; /* Reduced padding */
        border: none;
        border-radius: 4px;
        background-color: #5cb85c;
        color: white;
        cursor: pointer;
        font-size: 14px; /* Smaller font size */
        transition: background-color 0.3s; /* Smooth transition */
        width: 100%; /* Uniform width for all buttons */
        max-width: 200px; /* Set a maximum width for consistency */
    }
    .footer {
        text-align: center; /* Centered footer */
        margin-top: 10px; /* Reduced margin */
        font-size: 10px; /* Smaller font for footer */
        color: #777; /* Lighter color for footer text */
    }
    .record-list {
        margin-top: 10px;
        list-style-type: none; /* Remove bullet points */
        padding: 0;
    }
    .record-item {
        padding: 5px;
        border-bottom: 1px solid #ddd; /* Divider for records */
    }
    .record-item:last-child {
        border-bottom: none; /* Remove bottom border for last item */
    }
</style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js  "></script>
</head>
<body>

<div class="header">
    <h2>Rates Clearance Calculator</h2>
    <button class="print-button" id="printButton" onclick="printPage()">Print</button>
</div>

<div class="container">
    <div class="form-section">
        <label for="period">Select Period</label>
        <select id="period" name="period" onchange="updatePeriodDetails()">
            <option value="">Select...</option>
            <option value="3">3 Months</option>
            <option value="6">6 Months</option>
            <option value="custom">Enter Custom Period</option>
        </select>

        <input type="text" id="customPeriod" name="customPeriod" placeholder="Enter period in months" style="display:none;" oninput="updateCustomPeriod()">
    </div>

    <div class="accounts-section">
        <label for="accounts">Number of Accounts</label>
        <select id="accounts" name="accounts" onchange="updateAccountDetails()">
            <option value="">Select Account</option>
            <option value="1">1 Account</option>
            <option value="2">2 Accounts</option>
            <option value="3">3 Accounts</option>
        </select>
    </div>
</div>

<div class="balance-section">
    
    <h3>Account Summary</h3>
    <div id="accountDetails"></div>

    <div class="input-group">
        <label>Processing Fee (USD):</label>
        <input type="text" class="processing-fee" placeholder="Enter processing fee">
    </div>

    <div class="input-group">
        <label>Total Balance (USD):</label>
        <input type="text" id="OveralTotalBalance" placeholder="Overal Total balance" readonly>
    </div>
    
    
    <button onclick="calculateTotal()">Calculate</button>
    <button onclick="saveRecords()">Save</button>
    <button onclick="viewSavedRecords()">View Saved</button>
</div>

<div class="footer">
    <p>&copy; 2025 Rates Clearance Calculator. All rights reserved.</p>
</div>

<!-- Overlay for saved records -->
<div id="savedRecordsOverlay">
    <div class="overlay-content">
        <button class="close-button" onclick="closeOverlay()">Close</button>
        <h3>Saved Records</h3>
        <ul id="recordList"></ul>
        <p id="noRecordsMessage" style="display: none; color: red;">No records found.</p>
    </div>
</div>

<script>

    const records = [];

    function updatePeriodDetails() {
        const period = document.getElementById('period').value;
        const customInput = document.getElementById('customPeriod');
        const accountCount = document.getElementById('accounts').value;
        const accountDetails = document.getElementById('accountDetails');

        if (period === 'custom') {
            customInput.style.display = 'block';
            return; // Exit if custom is selected
        } else {
            customInput.style.display = 'none';
            if (period) {
                generateAccountDetails(period, accountCount);
            }
        }
    }

    function updateCustomPeriod() {
        const customPeriod = document.getElementById('customPeriod').value;
        const accountCount = document.getElementById('accounts').value;
        generateAccountDetails(customPeriod, accountCount);
    }

    function updateAccountDetails() {
        const accountCount = document.getElementById('accounts').value;
        const period = document.getElementById('period').value;
        const customInput = document.getElementById('customPeriod');

        if (period === 'custom') {
            if (customInput.value) {
                generateAccountDetails(customInput.value, accountCount);
            }
        } else {
            generateAccountDetails(period, accountCount);
        }
    }

    function generateAccountDetails(months, accountCount) {
        const accountDetails = document.getElementById('accountDetails');
        accountDetails.innerHTML = ''; // Clear previous details

        // Loop through each account
        for (let i = 1; i <= accountCount; i++) {
            accountDetails.innerHTML += `

                <p>Account ${i} </p>
                <div class="input-group">
                    <label>Account number:</label>
                    <input type="text" id="accountNumber" placeholder="Enter account number">
                </div>

                <!--old code-->
                <div class="input-group">
                    <label>Balance for Account ${i} (USD):</label>
                    <input type="text" placeholder="Enter balance" class="account-balance" onblur="formatCurrency(this)">
                </div>

                <div class="input-group">
                    <label>Month 1 
                        <select id="accounts" name="accounts"">
                            <option value="January">Jan Account</option>
                            <option value="February">Feb Accounts</option>
                            <option value="MAtch">Mat Accounts</option>
                            <option value="April"> Apr Accounts</option>
                            <option value="May"> May Accounts</option>
                            <option value="June"> Jun Accounts</option>
                            <option value="July "> Jul Accounts</option>
                            <option value="August "> Aug Accounts</option>
                            <option value="September "> Sep Accounts</option>
                            <option value="October "> Oct Accounts</option>
                            <option value="November "> Nov Accounts</option>
                            <option value="December "> Dec</option>
                        </select>(USD):
                    </label>
                    <input type="text" placeholder="Enter monthly balance" class="monthly-balance" onblur="formatCurrency(this)">
                </div>

                <div class="input-group">
                     <label>Month 2  
                        <select id="accounts" name="accounts"">
                            <option value="January">Jan Account</option>
                            <option value="February">Feb Accounts</option>
                            <option value="MAtch">Mat Accounts</option>
                            <option value="April"> Apr Accounts</option>
                            <option value="May"> May Accounts</option>
                            <option value="June"> Jun Accounts</option>
                            <option value="July "> Jul Accounts</option>
                            <option value="August "> Aug Accounts</option>
                            <option value="September "> Sep Accounts</option>
                            <option value="October "> Oct Accounts</option>
                            <option value="November "> Nov Accounts</option>
                            <option value="December "> Dec</option>
                        </select>
                     (USD):
                     </label>
                     <input type="text" placeholder="Enter monthly balance" class="monthly-balance" onblur="formatCurrency(this)">
                </div>
                <div class="input-group">
                     <label>Month 3
                        <select id="accounts" name="accounts"">
                            <option value="January">Jan Account</option>
                            <option value="February">Feb Accounts</option>
                            <option value="MAtch">Mat Accounts</option>
                            <option value="April"> Apr Accounts</option>
                            <option value="May"> May Accounts</option>
                            <option value="June"> Jun Accounts</option>
                            <option value="July "> Jul Accounts</option>
                            <option value="August "> Aug Accounts</option>x
                            <option value="September "> Sep Accounts</option>
                            <option value="October "> Oct Accounts</option>
                            <option value="November "> Nov Accounts</option>
                            <option value="December "> Dec</option>
                        </select>
                     (USD):
                     </label>
                     <input type="text" placeholder="Enter monthly balance" class="monthly-balance" onblur="formatCurrency(this)">
                </div>

                <div class="input-group">
                    <label>Account Total Balance (USD):</label>
                    <input type="text" id="totalBalance" placeholder="Total balance" readonly>
                </div>

            `;
        }

        // // Loop through the months after the account balances
        // for (let j = 1; j <= months; j++) {
        //     accountDetails.innerHTML += `
        //         <div class="input-group">
        //             <label>Month ${j} (USD):</label>
        //             <input type="text" placeholder="Enter monthly balance" class="monthly-balance" onblur="formatCurrency(this)">
        //         </div>
        //     `;
        // }
    }

    function formatCurrency(input) {
        let value = parseFloat(input.value.replace(/[^0-9.-]+/g, ""));
        if (!isNaN(value)) {
            input.value = value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
        } else {
            input.value = '';
        }
    }

    function calculateOveralTotal() {
        const accountBalances = document.querySelectorAll('.account-balance');
        const monthlyBalances = document.querySelectorAll('.monthly-balance');
        let total = 0;

        // Sum account balances
        accountBalances.forEach(input => {
            const value = parseFloat(input.value.replace(/[^0-9.-]+/g, "")) || 0;
            total += value;
        });

        // Sum monthly balances
        monthlyBalances.forEach(input => {
            const value = parseFloat(input.value.replace(/[^0-9.-]+/g, "")) || 0;
            total += value;
        });

        // Display total balance
        document.getElementById('totalBalance').value = total.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    }

    function calculateTotal() {
        const accountBalances = document.querySelectorAll('.account-balance');
        const monthlyBalances = document.querySelectorAll('.monthly-balance');
        let total = 0;

        // Sum account balances
        accountBalances.forEach(input => {
            const value = parseFloat(input.value.replace(/[^0-9.-]+/g, "")) || 0;
            total += value;
        });

        // Sum monthly balances
        monthlyBalances.forEach(input => {
            const value = parseFloat(input.value.replace(/[^0-9.-]+/g, "")) || 0;
            total += value;
        });

        // Add processing fee
        const processingFee = document.querySelector('.processing-fee').value;
        total += parseFloat(processingFee.replace(/[^0-9.-]+/g, "")) || 0;

        // Display total balance
        document.getElementById('OveralTotalBalance').value = total.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    }

    function saveRecords() {
        const accountNumber = document.getElementById('accountNumber').value;
        const TotalBalance = document.getElementById('TotalBalance').value;
        const OveralTotalBalance = document.getElementById('OveralTotalBalance').value;
        const processingFee = document.querySelector('.processing-fee').value;
        const accountDetails = document.getElementById('accountDetails').innerHTML;

        if (accountNumber && OveralTotalBalance && TotalBalance) {
            const record = { holder: accountNumber,TotalBalance, OveralTotalBalance, processingFee, accountDetails, date: new Date() };
            records.unshift(record); // Add the new record at the beginning of the array
            generatePDF(record); // Generate PDF after saving
            clearEntries(); // Clear entries after saving
        } else {
            alert("Please enter the account holder name and calculate the total balance before saving.");
        }
    }

    function generatePDF(record) {
        const { holder,TotalBalance, OveralTotalBalance, processingFee, accountDetails } = record;
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        doc.setFontSize(16);
        doc.text("Rates Clearance Calculator", 20, 20);
        doc.setFontSize(12);
        doc.text("Account Holder:" + holder, 20, 40);
        doc.text("Total Balance:" + TotalBalance , 20, 50);
        doc.text("Overal Total Balance:" + OveralTotalBalance , 20, 50);
        doc.text("Processing Fee: " + processingFee, 20, 60);
        doc.text("Account Details:", 20, 70);
        doc.fromHTML(accountDetails, 20, 80);

        const pdfName = `${holder}_rates_clearance.pdf`;
        doc.save(pdfName);
    }

    function viewSavedRecords() {
        const recordList = document.getElementById('recordList');
        recordList.innerHTML = ''; // Clear previous records
        const noRecordsMessage = document.getElementById('noRecordsMessage');

        if (records.length === 0) {
            noRecordsMessage.style.display = 'block'; // Show no records message
        } else {
            noRecordsMessage.style.display = 'none'; // Hide no records message
            records.forEach(record => {
                const li = document.createElement('li');
                li.textContent = `Holder: ${record.holder}, Overal Total Balance: ${record.OveralTotalBalance}, Processing Fee: ${record.processingFee}`;
                li.className = 'record-item';
                recordList.appendChild(li);
            });
        }

        document.getElementById('savedRecordsOverlay').style.display = 'block'; // Show overlay
    }

    function closeOverlay() {
        document.getElementById('savedRecordsOverlay').style.display = 'none'; // Hide overlay
    }

    function printPage() {
        const printButton = document.getElementById('printButton');
        const originalContent = document.body.innerHTML; // Save the original content

        printButton.classList.remove('success', 'failure'); // Reset classes
        printButton.disabled = true; // Disable button during print

        // Print the document
        window.print();

        // After print, change button color based on success or failure
        printButton.classList.add('success'); // Assuming print was successful
        setTimeout(() => {
            printButton.classList.remove('success'); // Reset color after a delay
            printButton.disabled = false; // Re-enable button
        }, 2000);
    }

    function clearEntries() {
        document.getElementById('accountNumber').value = '';
        document.getElementById('TotalBalance').value = '';
        document.getElementById('OveralTotalBalance').value = '';
        document.getElementById('accountDetails').innerHTML = '';
        document.getElementById('customPeriod').value = '';
        document.getElementById('period').selectedIndex = 0;
        document.getElementById('accounts').selectedIndex = 0;
    }
</script>

</body>
</html>
