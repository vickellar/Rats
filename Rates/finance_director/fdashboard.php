<?php
/*
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance_director') {
    error_log("Redirecting to index.php due to invalid role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'No role set')); // Log the redirection reason

    header("Location: ../index.php");
    exit();
}

include '../Database/db.php';
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Clone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding: 40px;
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
            font-size: 20px;
            color: black;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -10px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 4px;
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

        .declined-application {
            background-color: #e74c3c;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100vw; /* Full width of the viewport */
            height: 100vh; /* Full height of the viewport */
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 0 auto; /* Center the content */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Adjust width as needed */
            height: 80%; /* Adjust height as needed */
            border-radius: 8px;
            overflow-y: auto;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
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

        /* Profile Form Styles */
        .profile-form {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .profile-form .form-group {
            margin-bottom: 15px;
            position: relative;
        }

        .profile-form .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .profile-form .form-group input {
            width: calc(100% - 40px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding-right: 40px;
        }

        .profile-form .form-group .icon {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 18px;
            color: #999;
        }

        .profile-form .profile-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-form .profile-header img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-form .profile-header .upload-btn {
            margin-top: 10px;
            cursor: pointer;
        }

        .profile-form .password-strength {
            height: 5px;
            background-color: #ddd;
            margin-top: 5px;
        }

        .profile-form .password-strength-bar {
            height: 100%;
            width: 0;
            background-color: red;
        }

        .profile-form .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #3498db;
        }

        .profile-form .close-button:hover {
            color: red;
        }

        .profile-form .form-buttons {
            text-align: center;
        }

        /* Spinner Animation */
        @keyframes spinner {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #3498db;
            animation: spinner 1s linear infinite;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
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
                <a href="#" id="profileTab">Profile</a>
                <a href="#" id="history">History</a>
                <a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card new-applications" id="newApplications">
                <h3>New Applications</h3>
                <p id="newAppCount">8</p>
            </div>
            <div class="stat-card pending-approvals" id="pendingApplications">
                <h3>Pending Approvals</h3>
                <p id="pendingCount">0</p>
            </div>
            <div class="stat-card approved-applications" id="approvedApplications">
                <h3>Approved Applications</h3>
                <p id="approvedCount">0</p>
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

        <div class="inbox" id="historyForm" style="display: none;">
            <h2>History</h2>
            <span class="close-button" id="closeHistoryForm">&times;</span>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Activity</th>
                    </tr>
                </thead>
                <tbody id="historyList">
                    <!-- Dynamic rows will be added here -->
                </tbody>
            </table>
        </div>

        <!-- Profile Form -->
        <div class="profile-form" id="profileForm" style="display: none;">
            <span class="close-button" id="closeProfileForm">&times;</span>
            <div class="profile-header">
                <img src="default-avatar.jpg" alt="Profile Picture" id="profilePicture">
                <div class="upload-btn" id="uploadBtn">
                    <i class="fas fa-camera"></i> Upload Photo
                    <input type="file" id="profilePictureInput" accept="image/*" style="display: none;">
                </div>
            </div>
            <form id="profileEditForm">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Enter your name">
                    <i class="fas fa-user icon"></i>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email">
                    <i class="fas fa-envelope icon"></i>
                </div>
                <div class="form-group">
                    <label for="password">Enter Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password">
                    <i class="fas fa-lock icon"></i>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="Enter your phone number">
                    <i class="fas fa-phone icon"></i>
                </div>
                <div class="form-buttons">
                    <button type="button" id="saveButton" class="btn">Save</button>
                    <button type="button" id="editButton" class="btn">Edit</button>
                </div>
            </form>
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
                <p><strong>Email:</strong> <span id="modalEmail"></span></p>
                <p><strong>Date:</strong> <span id="modalDate"></span></p>
                <p><strong>Activity:</strong> <span id="modalActivity"></span></p>
                <p><strong>Details:</strong> This is the detailed view of the application.</p>
                <div id="modalMedia">
                    <!-- PDF or Image will be displayed here -->
                </div>
            </div>
            <div class="modal-footer">
                <button id="approveButton">Approve</button>
                <button id="replyButton">Reply</button>
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
        const modalEmail = document.getElementById('modalEmail');
        const modalDate = document.getElementById('modalDate');
        const modalActivity = document.getElementById('modalActivity');
        const closeModal = document.getElementById('closeModal');
        const closeNewApplicationForm = document.getElementById('closeNewApplicationForm');
        const closePendingApplicationForm = document.getElementById('closePendingApplicationForm');
        const closeApprovedApplicationForm = document.getElementById('closeApprovedApplicationForm');
        const closeHistoryForm = document.getElementById('closeHistoryForm');
        const historyList = document.getElementById('historyList');
        const profileTab = document.getElementById('profileTab');
        const profileForm = document.getElementById('profileForm');
        const profilePicture = document.getElementById('profilePicture');
        const uploadBtn = document.getElementById('uploadBtn');
        const profilePictureInput = document.getElementById('profilePictureInput');
        const saveButton = document.getElementById('saveButton');
        const editButton = document.getElementById('editButton');
        const closeProfileForm = document.getElementById('closeProfileForm');

        let history = [];

        function updateHistory(app, action) {
            const now = new Date().toLocaleString();
            history.push({ name: app.name, date: now, activity: action });
            updateHistoryList();
        }

        function updateHistoryList() {
            historyList.innerHTML = '';
            history.forEach(entry => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${entry.name}</td><td>${entry.date}</td><td>${entry.activity}</td>`;
                historyList.appendChild(row);
            });
        }

        let applications = [
            { name: "Alice Johnson", email: "alice@example.com", date: new Date().toLocaleDateString(), activity: 'New', mediaType: 'pdf', mediaUrl: 'path/to/alice.pdf' },
            { name: "Bob Smith", email: "bob@example.com", date: new Date().toLocaleDateString(), activity: 'Pending', mediaType: 'image', mediaUrl: 'path/to/bob.jpg' },
            { name: "Charlie Brown", email: "charlie@example.com", date: new Date().toLocaleDateString(), activity: 'New', mediaType: 'pdf', mediaUrl: 'path/to/charlie.pdf' },
            { name: "Diana Prince", email: "diana@example.com", date: new Date().toLocaleDateString(), activity: 'Approved', mediaType: 'image', mediaUrl: 'path/to/diana.jpg' },
            { name: "Ethan Hunt", email: "ethan@example.com", date: new Date().toLocaleDateString(), activity: 'Pending', mediaType: 'pdf', mediaUrl: 'path/to/ethan.pdf' },
            { name: "Frank Castle", email: "frank@example.com", date: new Date().toLocaleDateString(), activity: 'New', mediaType: 'image', mediaUrl: 'path/to/frank.jpg' },
            { name: "JOJI MAPOSA", email: "joji@gmail.com", date: new Date().toLocaleDateString(), activity: 'Pending', mediaType: 'pdf', mediaUrl: 'path/to/joji.pdf' },
            { name: "VICKELAH NJISWE", email: "vickelahnjiswe.com", date: new Date().toLocaleDateString(), activity: 'Pending', mediaType: 'image', mediaUrl: 'path/to/vickelah.jpg' },
            { name: "Grace Kelly", email: "grace@example.com", date: new Date().toLocaleDateString(), activity: 'Pending', mediaType: 'pdf', mediaUrl: 'path/to/grace.pdf' },
            { name: "Hannah Montana", email: "hannah@example.com", date: new Date().toLocaleDateString(), activity: 'New', mediaType: 'image', mediaUrl: 'path/to/hannah.jpg' }
        ];

        function updateNotificationCount() {
            const newCount = applications.filter(app => app.activity === 'New').length;
            notificationCount.textContent = newCount;
        }

        function updatePendingCount() {
            const pendingCountValue = applications.filter(app => app.activity === 'Pending' || app.activity === 'Opened').length;
            pendingCount.textContent = pendingCountValue;
        }

        function updateApprovedCount() {
            const approvedCountValue = applications.filter(app => app.activity === 'Approved').length;
            approvedCount.textContent = approvedCountValue;
        }

        function updateApplicationList() {
            applicationList.innerHTML = '';
            applications.forEach(app => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${app.name}</td><td>${app.date}</td><td>${app.activity}</td>`;

                if (app.activity === 'New') {
                    row.classList.add('new-application');
                } else if (app.activity === 'Opened') {
                    row.classList.add('opened-application');
                }

                row.addEventListener('click', function() {
                    openApplicationModal(app);
                    if (app.activity === 'New') {
                        app.activity = 'Opened'; // Change only if it was 'New'
                        updateHistory(app, 'Opened');
                    }
                    updateApplicationList();
                    updatePendingApplicationList();
                    updateApprovedApplicationList();
                });
                applicationList.appendChild(row);
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
                if (app.activity === 'Pending' || app.activity === 'Opened') {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td>${app.name}</td><td>${app.date}</td><td>${app.activity}</td>`;

                    row.classList.add(app.activity === 'Opened' ? 'opened-application' : 'pending-application');
                    row.addEventListener('click', function() {
                        openApplicationModal(app);
                    });
                    pendingApplicationList.appendChild(row);
                }
            });

            pendingApplicationCount.textContent = "Total Pending Applications: " + applications.filter(app => app.activity === 'Pending' || app.activity === 'Opened').length;
        }

        function updateApprovedApplicationList() {
            approvedApplicationList.innerHTML = '';
            applications.forEach(app => {
                if (app.activity === 'Approved' || app.activity === 'Opened') {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td>${app.name}</td><td>${app.date}</td><td>${app.activity}</td>`;

                    row.classList.add('opened-application');
                    row.addEventListener('click', function() {
                        openApplicationModal(app);
                    });
                    approvedApplicationList.appendChild(row);
                }
            });

            approvedApplicationCount.textContent = 'Total Approved Applications: ' + applications.filter(app => app.activity === 'Approved' || app.activity === 'Opened').length;
        }

        function openApplicationModal(app) {
            modalTitle.textContent = app.name;
            modalEmail.textContent = app.email;
            modalDate.textContent = app.date;
            modalActivity.textContent = app.activity;

            const modalMedia = document.getElementById('modalMedia');
            modalMedia.innerHTML = ''; // Clear previous content

            if (app.mediaType === 'pdf') {
                modalMedia.innerHTML = `<iframe src="${app.mediaUrl}" width="100%" height="400px"></iframe>`;
            } else if (app.mediaType === 'image') {
                modalMedia.innerHTML = '<img src="' + app.mediaUrl + '" alt="Application Picture" style="max-width:100%; height:auto;">';
            }

            applicationModal.style.display = 'block';
        }

        document.getElementById('newApplications').addEventListener('click', function () {
            profileForm.style.display = 'none';
            newApplicationForm.style.display = 'block';
            pendingApplicationForm.style.display = 'none';
            approvedApplicationForm.style.display = 'none';
            updateApplicationList();
        });

        document.getElementById('pendingApplications').addEventListener('click', function () {
            profileForm.style.display = 'none';
            pendingApplicationForm.style.display = 'block';
            newApplicationForm.style.display = 'none';
            approvedApplicationForm.style.display = 'none';
            updatePendingApplicationList();
        });

        document.getElementById('approvedApplications').addEventListener('click', function () {
            profileForm.style.display = 'none';
            approvedApplicationForm.style.display = 'block';
            newApplicationForm.style.display = 'none';
            pendingApplicationForm.style.display = 'none';
            updateApprovedApplicationList();
        });

        document.getElementById('history').addEventListener('click', function() {
            profileForm.style.display = 'none';
            document.getElementById('historyForm').style.display = 'block';
            updateHistoryList();
        });

        notificationIcon.addEventListener('click', function() {
            profileForm.style.display = 'none';
            newApplicationForm.style.display = 'block';
            pendingApplicationForm.style.display = 'none';
            approvedApplicationForm.style.display = 'none';
            updateApplicationList();
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

        closeHistoryForm.addEventListener('click', function() {
            document.getElementById('historyForm').style.display = 'none';
        });

        document.getElementById('approveButton').addEventListener('click', function() {
            const currentApplication = applications.find(app => app.name === modalTitle.textContent);
            if (currentApplication && currentApplication.activity !== 'Approved') {
                currentApplication.activity = 'Approved';
                updateHistory(currentApplication, 'Approved');
                updateApplicationList();
                updatePendingApplicationList();
                updateApprovedApplicationList();
                applicationModal.style.display = 'none';
            }
        });

        document.getElementById('declineButton').addEventListener('click', function() {
            const currentApplication = applications.find(app => app.name === modalTitle.textContent);
            if (currentApplication) {
                currentApplication.activity = 'Declined';
                updateHistory(currentApplication, 'Declined');
                updateApplicationList();
                updatePendingApplicationList();
                applicationModal.style.display = 'none';
                document.getElementById('declineSound').play();
            }
        });

        document.getElementById('deleteIcon').addEventListener('click', function() {
            const currentApplication = applications.find(app => app.name === modalTitle.textContent);
            if (currentApplication) {
                applications = applications.filter(app => app.name !== currentApplication.name);
                updateHistory(currentApplication, 'Deleted');
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

        profileTab.addEventListener('click', function() {
            profileForm.style.display = 'block';
            newApplicationForm.style.display = 'none';
            pendingApplicationForm.style.display = 'none';
            approvedApplicationForm.style.display = 'none';
            document.getElementById('historyForm').style.display = 'none';
        });

        uploadBtn.addEventListener('click', function() {
            profilePictureInput.click();
        });

        profilePictureInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePicture.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        saveButton.addEventListener('click', function() {
            // Simulate saving and redirect after 5 seconds
            setTimeout(function() {
                window.location.href = 'finance-director-dashboard.html'; // Redirect to dashboard
            }, 5000);
        });

        editButton.addEventListener('click', function() {
            // Enable editing of the form fields
            document.getElementById('profileEditForm').querySelectorAll('input').forEach(input => {
                input.disabled = false;
            });
        });

        closeProfileForm.addEventListener('click', function() {
            profileForm.style.display = 'none';
        });

        // Password Strength Meter
        const passwordInput = document.getElementById('password');
        const passwordStrengthBar = document.getElementById('passwordStrengthBar');

        passwordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            let strength = 0;

            if (password.length >= 4) {
                strength += 1;
            }
            if (password.length >= 6) {
                strength += 1;
            }
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
                strength += 1;
            }
            if (password.match(/[\d]/)) {
                strength += 1;
            }
            if (password.match(/[\W]/)) {
                strength += 1;
            }

            switch (strength) {
                case 0:
                case 1:
                    passwordStrengthBar.style.width = '20%';
                    passwordStrengthBar.style.backgroundColor = 'red';
                    break;
                case 2:
                case 3:
                    passwordStrengthBar.style.width = '50%';
                    passwordStrengthBar.style.backgroundColor = 'orange';
                    break;
                case 4:
                case 5:
                    passwordStrengthBar.style.width = '100%';
                    passwordStrengthBar.style.backgroundColor = 'green';
                    break;
            }
        });
    </script>
</body>
</html>
