
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Clone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #eaeaea;
        }

        .main {
            flex: 1;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 2px solid #eaeaea;
            background-color: white;
            position: relative;
        }

        .title {
            color: black;
            font-size: 32px;
            font-weight: bold;
        }

        .notification-icon {
            position: relative;
            font-size: 30px;
            color: #3498db;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -10px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 5px;
            font-size: 0.8em;
        }

        .nav-links {
            display: flex;
            gap: 15px;
            position: absolute;
            right: 20px;
            bottom: 10px;
        }

        .nav-links a {
            color: #2980b9;
            text-decoration: none;
            font-size: 16px;
        }

        .stats {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }

        .stat-card {
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
            flex: 1;
            margin: 0 10px;
            cursor: pointer;
        }

        .approved-applications {
            background-color: #2ecc71;
            color: white;
        }

        .pending-approvals {
            background-color: #f1c40f;
            color: white;
        }

        .new-applications {
            background-color: #3498db;
            color: white;
        }

        .stat-card h3 {
            margin: 0 0 10px;
        }

        .inbox {
            margin-top: 20px;
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .inbox h2 {
            margin: 0 0 20px;
        }

        .inbox .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #3498db;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            cursor: pointer;
        }

        th {
            background-color: #f2f2f2;
        }

        .new-application {
            background-color: #d4edda;
        }

        .opened-application {
            background-color: #f9f9f9;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            height: 70%;
            border-radius: 8px;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header .icons {
            display: flex;
            gap: 10px;
        }

        .modal-header .icons i {
            cursor: pointer;
            font-size: 20px;
        }

        #closeModal {
            font-size: 30px;
            color: red;
            cursor: pointer;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }

        .modal-footer button {
            margin-left: 10px;
            background-color: #ccc;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        /* Button Hover Effects */
        .modal-footer button:hover {
            opacity: 0.8;
        }

        /* Specific Color Changes */
        #approveButton:hover {
            background-color: #2ecc71;
        }

        #replyButton:hover,
        #forwardButton:hover {
            background-color: #3498db;
        }

        #declineButton:hover {
            background-color: #e74c3c;
        }

        /* Reply Section Styles */
        .reply-section {
            margin-top: 20px;
        }

        .reply-section input {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .reply-section textarea {
            width: 100%;
            height: 100px;
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .reply-section input[type="file"] {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="main" id="main">
        <div class="header">
            <div class="title">FINANCIAL DIRECTOR</div>
            <div class="notification-icon" id="notificationIcon">&#128276;
                <span class="notification-badge" id="notificationCount">0</span>
            </div>
            <div class="nav-links">
                <a href="#">Dashboard</a>
                <a href="#">Reports</a>
                <a href="#">Profile</a>
                <a href="#">History</a>
                <a href="#">Logout</a>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card approved-applications" id="approvedApplications">
                <h3>Approved Applications</h3>
                <p id="approvedCount">0</p>
            </div>
            <div class="stat-card pending-approvals" id="pendingApplications">
                <h3>Pending Approvals</h3>
                <p id="pendingCount">0</p>
            </div>
            <div class="stat-card new-applications" id="newApplications">
                <h3>New Applications</h3>
                <p id="newAppCount">8</p>
            </div>
        </div>

        <div class="inbox" id="newApplicationForm" style="display: none;">
            <h2>New Applications Inbox</h2>
            <span class="close-button" id="closeNewApplicationForm">&times;</span>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Activity</th>
                    </tr>
                </thead>
                <tbody id="applicationList">
                    <!-- Dynamic rows will be added here -->
                </tbody>
            </table>
            <p id="applicationCount"></p>
        </div>

        <div class="inbox" id="pendingApplicationForm" style="display: none;">
            <h2>Pending Applications Inbox</h2>
            <span class="close-button" id="closePendingApplicationForm">&times;</span>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Activity</th>
                    </tr>
                </thead>
                <tbody id="pendingApplicationList">
                    <!-- Dynamic rows will be added here -->
                </tbody>
            </table>
            <p id="pendingApplicationCount"></p>
        </div>

        <div class="inbox" id="approvedApplicationForm" style="display: none;">
            <h2>Approved Applications Inbox</h2>
            <span class="close-button" id="closeApprovedApplicationForm">&times;</span>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Activity</th>
                    </tr>
                </thead>
                <tbody id="approvedApplicationList">
                    <!-- Dynamic rows will be added here -->
                </tbody>
            </table>
            <p id="approvedApplicationCount"></p>
        </div>
    </div>

    <!-- Modal for Application Details -->
    <div id="applicationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="icons">
                    <i class="fas fa-trash" id="deleteIcon"></i>
                    <i class="fas fa-print" id="printIcon"></i>
                    <i class="fas fa-save" id="saveIcon"></i>
                </div>
                <h2 id="modalTitle"></h2>
                <span id="closeModal">&times;</span>
            </div>
            <div id="modalBody">
                <!-- Application details will be populated here -->
            </div>
            <div class="modal-footer">
                <button id="approveButton">Approve</button>
                <button id="replyButton">Reply</button>
                <button id="forwardButton">Forward</button>
                <button id="declineButton">Decline</button>
            </div>
        </div>
    </div>

    <audio id="declineSound" src="your-sound-file.mp3" preload="auto"></audio>

    <script>
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationCount = document.getElementById('notificationCount');
        const newAppCount = document.getElementById('newAppCount');
        const pendingCount = document.getElementById('pendingCount');
        const approvedCount = document.getElementById('approvedCount');
        const newApplicationForm = document.getElementById('newApplicationForm');
        const pendingApplicationForm = document.getElementById('pendingApplicationForm');
        const approvedApplicationForm = document.getElementById('approvedApplicationForm');
        const applicationList = document.getElementById('applicationList');
        const pendingApplicationList = document.getElementById('pendingApplicationList');
        const approvedApplicationList = document.getElementById('approvedApplicationList');
        const applicationCount = document.getElementById('applicationCount');
        const pendingApplicationCount = document.getElementById('pendingApplicationCount');
        const approvedApplicationCount = document.getElementById('approvedApplicationCount');
        const applicationModal = document.getElementById('applicationModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const closeModal = document.getElementById('closeModal');
        const closeNewApplicationForm = document.getElementById('closeNewApplicationForm');
        const closePendingApplicationForm = document.getElementById('closePendingApplicationForm');
        const closeApprovedApplicationForm = document.getElementById('closeApprovedApplicationForm');

        // Prepopulate applications: some new, some pending, and some approved
        let applications = [
            { name: "Alice Johnson", email: "alice@example.com", date: new Date().toLocaleDateString(), activity: 'New' },
            { name: "Bob Smith", email: "bob@example.com", date: new Date().toLocaleDateString(), activity: 'Pending' },
            { name: "Charlie Brown", email: "charlie@example.com", date: new Date().toLocaleDateString(), activity: 'New' },
            { name: "Diana Prince", email: "diana@example.com", date: new Date().toLocaleDateString(), activity: 'Approved' },
            { name: "Ethan Hunt", email: "ethan@example.com", date: new Date().toLocaleDateString(), activity: 'Pending' },
            { name: "Frank Castle", email: "frank@example.com", date: new Date().toLocaleDateString(), activity: 'New' },
            { name: "Grace Kelly", email: "grace@example.com", date: new Date().toLocaleDateString(), activity: 'Pending' },
            { name: "Hannah Montana", email: "hannah@example.com", date: new Date().toLocaleDateString(), activity: 'New' }
        ];

        function updateNotificationCount() {
            const newCount = applications.filter(app => app.activity === 'New').length;
            notificationCount.textContent = newCount;
        }

        function updatePendingCount() {
            const pendingCountValue = applications.filter(app => app.activity === 'Pending').length;
            pendingCount.textContent = pendingCountValue;
        }

        function updateApprovedCount() {
            const approvedCountValue = applications.filter(app => app.activity === 'Approved').length;
            approvedCount.textContent = approvedCountValue;
        }

        function updateApplicationList() {
            applicationList.innerHTML = '';
            applications.forEach(app => {
                if (app.activity === 'New') {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td>${app.name}</td><td>${app.date}</td><td>${app.activity}</td>`;
                    row.classList.add('new-application');
                    row.addEventListener('click', function() {
                        openApplicationModal(app);
                    });
                    applicationList.appendChild(row);
                }
            });
            applicationCount.textContent = `Total Sent Applications: ${applications.length}`;
            newAppCount.textContent = applications.filter(app => app.activity === 'New').length;
            updateNotificationCount();
            updatePendingCount();
            updateApprovedCount();
        }

        function updatePendingApplicationList() {
            pendingApplicationList.innerHTML = '';
            applications.forEach(app => {
                if (app.activity === 'Pending') {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td>${app.name}</td><td>${app.date}</td><td>${app.activity}</td>`;
                    row.classList.add('new-application');
                    row.addEventListener('click', function() {
                        openApplicationModal(app);
                    });
                    pendingApplicationList.appendChild(row);
                }
            });
            pendingApplicationCount.textContent = `Total Pending Applications: ${applications.filter(app => app.activity === 'Pending').length}`;
        }

        function updateApprovedApplicationList() {
            approvedApplicationList.innerHTML = '';
            applications.forEach(app => {
                if (app.activity === 'Approved') {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td>${app.name}</td><td>${app.date}</td><td>${app.activity}</td>`;
                    row.classList.add('opened-application');
                    row.addEventListener('click', function() {
                        openApplicationModal(app);
                    });
                    approvedApplicationList.appendChild(row);
                }
            });
            approvedApplicationCount.textContent = `Total Approved Applications: ${applications.filter(app => app.activity === 'Approved').length}`;
        }

        function openApplicationModal(app) {
            modalTitle.textContent = app.name;
            modalBody.innerHTML = `<p><strong>Email:</strong> ${app.email}</p>
                                   <p><strong>Date:</strong> ${app.date}</p>
                                   <p><strong>Activity:</strong> ${app.activity}</p>
                                   <p><strong>Details:</strong> This is the detailed view of the application.</p>`;
            applicationModal.style.display = 'block';
        }

        document.getElementById('newApplications').addEventListener('click', function () {
            newApplicationForm.style.display = 'block';
            pendingApplicationForm.style.display = 'none';
            approvedApplicationForm.style.display = 'none';
            updateApplicationList();
        });

        document.getElementById('pendingApplications').addEventListener('click', function () {
            pendingApplicationForm.style.display = 'block';
            newApplicationForm.style.display = 'none';
            approvedApplicationForm.style.display = 'none';
            updatePendingApplicationList();
        });

        document.getElementById('approvedApplications').addEventListener('click', function () {
            approvedApplicationForm.style.display = 'block';
            newApplicationForm.style.display = 'none';
            pendingApplicationForm.style.display = 'none';
            updateApprovedApplicationList();
        });

        closeModal.addEventListener('click', function() {
            applicationModal.style.display = 'none';
        });

        closeNewApplicationForm.addEventListener('click', function() {
            newApplicationForm.style.display = 'none';
        });

        closePendingApplicationForm.addEventListener('click', function() {
            pendingApplicationForm.style.display = 'none';
        });

        closeApprovedApplicationForm.addEventListener('click', function() {
            approvedApplicationForm.style.display = 'none';
        });

        document.getElementById('approveButton').addEventListener('click', function() {
            const currentApplication = applications.find(app => app.name === modalTitle.textContent);
            if (currentApplication && currentApplication.activity !== 'Approved') {
                currentApplication.activity = 'Approved';
                updateApprovedCount();
                updateApplicationList();
                updatePendingApplicationList();
                updateApprovedApplicationList(); // Update the approved application list
                applicationModal.style.display = 'none';
            }
        });

        document.getElementById('replyButton').addEventListener('click', function() {
            const currentApplication = applications.find(app => app.name === modalTitle.textContent);
            if (currentApplication) {
                modalBody.innerHTML = `
                    <div class="reply-section">
                        <input type="text" id="replyEmail" value="${currentApplication.email}" readonly>
                        <textarea id="replyMessage" placeholder="Type your message here..."></textarea>
                        <input type="file" id="attachment" accept="image/*,.pdf,.doc,.docx" />
                    </div>
                    <div class="modal-footer">
                        <button id="sendButton">Send</button>
                        <button id="backButton">Back</button>
                    </div>`;

                document.getElementById('sendButton').addEventListener('click', function() {
                    const message = document.getElementById('replyMessage').value;
                    const fileInput = document.getElementById('attachment');
                    const file = fileInput.files[0];

                    if (message) {
                        if (file) {
                            alert(`Message sent with attachment: ${file.name}`);
                        } else {
                            alert('Message sent successfully without attachment!');
                        }
                        applicationModal.style.display = 'none';
                    } else {
                        alert('Message cannot be empty.');
                    }
                });

                document.getElementById('backButton').addEventListener('click', function() {
                    applicationModal.style.display = 'none';
                });
            }
        });

        document.getElementById('forwardButton').addEventListener('click', function() {
            const currentApplication = applications.find(app => app.name === modalTitle.textContent);
            if (currentApplication) {
                modalBody.innerHTML = `
                    <div class="forward-section">
                        <label for="forwardEmails">Forward to (comma-separated emails):</label>
                        <input type="text" id="forwardEmails" placeholder="Enter email addresses">
                    </div>
                    <div class="modal-footer">
                        <button id="sendForwardButton">Send</button>
                        <button id="backForwardButton">Back</button>
                    </div>`;

                document.getElementById('sendForwardButton').addEventListener('click', function() {
                    const forwardEmails = document.getElementById('forwardEmails').value;
                    if (forwardEmails) {
                        alert(`Application forwarded to: ${forwardEmails}`);
                        applicationModal.style.display = 'none';
                    } else {
                        alert('Please enter email addresses.');
                    }
                });

                document.getElementById('backForwardButton').addEventListener('click', function() {
                    applicationModal.style.display = 'none';
                });
            }
        });

        document.getElementById('declineButton').addEventListener('click', function() {
            const currentApplication = applications.find(app => app.name === modalTitle.textContent);
            if (currentApplication) {
                currentApplication.activity = 'Declined';
                updateApplicationList();
                updatePendingApplicationList();
                applicationModal.style.display = 'none';
                document.getElementById('declineSound').play();
                updatePendingCount();
            }
        });

        document.getElementById('deleteIcon').addEventListener('click', function() {
            const currentApplication = applications.find(app => app.name === modalTitle.textContent);
            if (currentApplication) {
                applications = applications.filter(app => app.name !== currentApplication.name);
                updateApplicationList();
                updatePendingApplicationList();
                updateApprovedApplicationList();
                applicationModal.style.display = 'none';
            }
        });

        document.getElementById('printIcon').addEventListener('click', function() {
            alert('Print functionality is not implemented.');
        });

        document.getElementById('saveIcon').addEventListener('click', function() {
            alert('Save functionality is not implemented.');
        });
    </script>
</body>
</html>
