<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Messages</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212; /* Default dark background */
            color: #ffffff; /* Default text color */
            margin: 0;
            padding: 20px;
            transition: background-color 0.3s, color 0.3s; /* Smooth transition */
        }
        .header {
            background-color: #007bff; /* Blue color */
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            position: relative;
        }
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: #ffffff; /* White background for button */
            color: #000; /* Black text for button */
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }
        .search-container {
            margin-bottom: 20px; /* Space below search input */
        }
        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .message-container {
            max-width: 600px;
            margin: auto;
            background-color: #1e1e1e;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .message {
            padding: 10px;
            border-bottom: 1px solid #333;
            cursor: pointer; /* Pointer cursor for clickable messages */
            display: flex; /* Use flexbox to layout items */
            justify-content: space-between; /* Space between items */
            flex-direction: column; /* Stack items vertically */
        }
        .message:last-child {
            border-bottom: none;
        }
        .sender {
            font-weight: bold;
            font-size: 1.2em; /* Bigger font for sender */
            margin-bottom: 5px; /* Space below sender */
        }
        .timestamp {
            font-size: 0.8em;
            color: #bbb;
            margin-top: 5px; /* Space above timestamp */
            align-self: flex-end; /* Align to the right */
        }
        .text {
            margin-top: 5px; /* Space above text */
        }
        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
            padding-top: 60px;
        }
        .modal-content {
            background-color: #ffffff; /* Default white background for modal */
            margin: 5% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            transition: background-color 0.3s; /* Smooth transition */
        }
        .modal-title {
            font-size: 1.5em;
            margin-bottom: 15px;
            color: black; /* Black text for title */
        }
        .modal-text {
            color: black; /* Black text for modal content */
        }
        .button-container {
            margin-top: 20px;
        }
        .button {
            background-color: #007bff; /* Blue background for buttons */
            color: white; /* White text for buttons */
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            margin-right: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }
        .reply-input {
            display: none; /* Hide input by default */
            margin-top: 10px;
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .recipient-select {
            display: none; /* Hide select by default */
            margin-top: 10px;
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Reports</h1>
        <button class="theme-toggle" onclick="toggleTheme()">Light</button>
    </div>

    <div class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Search messages..." onkeyup="filterMessages()">
    </div>
    
    <div class="message-container" id="messageContainer"></div>

    <!-- The Modal -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle" class="modal-title"></h2>
            <p id="modalText" class="modal-text"></p> <!-- Added class for styling -->
            <div class="button-container">
                <button class="button" onclick="showReplyInput()">Reply</button>
                <button class="button" onclick="showRecipientSelect()">Forward</button>
            </div>
            <input type="text" id="replyInput" class="reply-input" placeholder="Type your reply here..." />
            <select id="recipientSelect" class="recipient-select" onchange="forwardMessage()">
                <option value="">Select Recipient</option>
                <option value="Recipient 1">Recipient 1</option>
                <option value="Recipient 2">Recipient 2</option>
                <option value="Recipient 3">Recipient 3</option>
            </select>
        </div>
    </div>

    <script>
        let isDarkTheme = true;

        // Sample messages with timestamps
        const messages = [
            { sender: 'Bless', text: 'Tell me madzoka ndigounza power', timestamp: '2023-10-12T10:30:00' },
            { sender: '071 574 0878', text: 'You: Hello', timestamp: '2023-10-12T11:00:00' },
            { sender: 'Econet', text: 'Buy SmartBiz Monthly data packages...', timestamp: '2023-10-12T09:00:00' },
            { sender: 'Mama💖', text: 'Ndasvika mwangan', timestamp: '2023-10-12T12:15:00' },
            { sender: '+263164', text: 'You have successfully purchased...', timestamp: '2023-10-12T13:00:00' },
            { sender: 'Chido', text: 'Yo: Tsanos moms vasvika here?', timestamp: '2023-10-12T10:45:00' },
            { sender: 'GuL3s', text: 'Wena mhani vasvika here?', timestamp: '2023-10-12T10:50:00' },
            { sender: 'MNB Bank', text: 'Dear Customer, this is a reminder...', timestamp: '2023-10-12T14:00:00' }
        ];

        // Sort messages by timestamp
        messages.sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));

        // Function to render messages
        function renderMessages(filteredMessages) {
            const messageContainer = document.getElementById('messageContainer');
            messageContainer.innerHTML = ''; // Clear existing messages
            (filteredMessages || messages).forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message';
                messageDiv.onclick = () => openModal(message.sender, message.text); // Open modal on click
                messageDiv.innerHTML = `
                    <div class="sender">${message.sender}</div>
                    <div class="text">${message.text}</div>
                    <div class="timestamp">${new Date(message.timestamp).toLocaleString()}</div>
                `;
                messageContainer.appendChild(messageDiv);
            });
        }

        function filterMessages() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const filteredMessages = messages.filter(message => 
                message.sender.toLowerCase().includes(input) || 
                message.text.toLowerCase().includes(input)
            );
            renderMessages(filteredMessages);
        }

        function toggleTheme() {
            if (isDarkTheme) {
                document.body.style.backgroundColor = "#ffffff"; // Light background
                document.body.style.color = "#000000"; // Dark text
                document.querySelector('.message-container').style.backgroundColor = "#f9f9f9"; // Light message container
                document.querySelector('.theme-toggle').innerText = "Dark"; // Change button text
                document.querySelector('.modal-content').style.backgroundColor = "#ffffff"; // White modal for light mode
            } else {
                document.body.style.backgroundColor = "#121212"; // Dark background
                document.body.style.color = "#ffffff"; // Light text
                document.querySelector('.message-container').style.backgroundColor = "#1e1e1e"; // Dark message container
                document.querySelector('.theme-toggle').innerText = "Light"; // Change button text
                document.querySelector('.modal-content').style.backgroundColor = "#ffffff"; // White modal for dark mode
            }
            isDarkTheme = !isDarkTheme;
        }

        function openModal(sender, message) {
            document.getElementById("modalTitle").innerText = sender; // Set title in modal
            document.getElementById("modalText").innerText = message; // Set message in modal
            document.getElementById("myModal").style.display = "block"; // Show modal
            document.getElementById("recipientSelect").style.display = "none"; // Hide recipient select on open
        }

        function closeModal() {
            document.getElementById("myModal").style.display = "none"; // Hide modal
            document.getElementById("replyInput").style.display = "none"; // Hide reply input
            document.getElementById("replyInput").value = ""; // Clear input value
            document.getElementById("recipientSelect").value = ""; // Reset recipient select
        }

        function showReplyInput() {
            const replyInput = document.getElementById("replyInput");
            replyInput.style.display = replyInput.style.display === "none" || replyInput.style.display === "" ? "block" : "none"; // Toggle visibility
            replyInput.focus(); // Focus on the input field
            document.getElementById("recipientSelect").style.display = "none"; // Hide recipient select
        }

        function showRecipientSelect() {
            const recipientSelect = document.getElementById("recipientSelect");
            recipientSelect.style.display = recipientSelect.style.display === "none" || recipientSelect.style.display === "" ? "block" : "none"; // Toggle visibility
            recipientSelect.focus(); // Focus on the select field
            document.getElementById("replyInput").style.display = "none"; // Hide reply input
        }

        function forwardMessage() {
            const recipient = document.getElementById("recipientSelect").value;
            const message = document.getElementById("modalText").innerText;

            if (recipient) {
                alert(`Message forwarded to ${recipient}:\n"${message}"`);
                closeModal(); // Close modal after forwarding
            }
        }

        // Initial render of messages
        renderMessages();

        // Close the modal when the user clicks anywhere outside of it
        window.onclick = function(event) {
            if (event.target == document.getElementById("myModal")) {
                closeModal();
            }
        }
    </script>

</body>
</html>