<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #eaeaea;
            transition: background-color 0.3s, color 0.3s;
        }

        .dark-theme {
            background-color: #2c3e50;
            color: white;
        }

        .margin-container {
            background-color: #3498db;
            color: white;
            padding: 10px;
            text-align: center;
            position: relative;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 2px solid #eaeaea;
        }

        .title {
            font-size: 28px;
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

        .nav-tabs {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }

        .nav-tabs a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-weight: bold;
            position: relative;
        }

        .search-btn {
            cursor: pointer;
            background: none;
            border: none;
            color: white;
            font-size: 30px;
        }

        .search-container {
            display: none; /* Hidden by default */
            margin: 20px 0;
            flex-direction: column;
        }

        .search-container input {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .search-container button {
            padding: 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #2980b9;
        }

        .tile {
            background-color: white;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
            flex: 1;
            margin: 0 10px;
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }

        /* Other styles remain unchanged... */
    </style>
</head>
<body>
    <div class="margin-container">
        <h2>Admin Dashboard</h2>
        <div class="nav-tabs">
            <a href="#history" onclick="showFullHistory()">History</a>
            <a href="#reports">Reports</a>
            <div style="position: relative;">
                <button class="search-btn" onclick="toggleSearch()">🔍</button>
                <div class="search-container" id="searchContainer">
                    <input type="text" id="nameInput" placeholder="Search by Name">
                    <input type="date" id="dateInput">
                    <button onclick="filterApplications()">Search</button>
                </div>
            </div>
            <div style="position: relative;">
                <a href="#" onclick="toggleDropdown(event)">Settings</a>
                <div class="dropdown" id="settingsDropdown">
                    <a href="#">Themes</a>
                    <a href="#" onclick="toggleTheme()">Toggle Dark/Light Theme</a>
                </div>
            </div>
        </div>
    </div>

    <div class="main" id="mainContent">
        <div class="header">
            <div class="title">Application Dashboard</div>
            <div class="notification-icon" id="notificationIcon">&#128276;
                <span class="notification-badge" id="notificationCount">0</span>
            </div>
        </div>

        <div class="grid-container">
            <div class="tile">
                <h2>Statistics</h2>
                <div class="stats">
                    <div class="stat-card" onclick="scrollToNewApplications()">
                        <h3>Total</h3>
                        <p>15</p>
                    </div>
                    <div class="stat-card pending-tile" onclick="scrollToNewApplications()">
                        <h3>Pending Applications</h3>
                        <p id="pendingCount">5</p>
                    </div>
                    <div class="stat-card" onclick="scrollToNewApplications()">
                        <h3>New Applications Received</h3>
                        <p id="newApplicationsCount">3</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-container">
            <div class="tile" style="flex: 2;" onclick="showFullHistory()">
                <h2>View Application History</h2>
                <div id="applicationHistory"></div>
            </div>

            <div class="tile" style="flex: 2;">
                <h2>Inbox for Applications</h2>
                <div id="inboxApplications"></div>
            </div>
        </div>
    </div>

    <div id="fullHistoryModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); color:white; padding:20px; overflow:auto;">
        <h2>Full Application History</h2>
        <div id="fullHistory"></div>
        <button onclick="closeFullHistory()">Close</button>
    </div>

    <script>
        let newApplicationsCount = 0; // Initial count of new applications
        const notificationCount = document.getElementById('notificationCount');
        const applicationHistory = document.getElementById('applicationHistory');
        const fullHistory = document.getElementById('fullHistory');

        const applications = []; // Array to hold application objects

        // Function to update notification count
        function updateNotificationCount() {
            notificationCount.textContent = newApplicationsCount; // Update badge
        }

        // Function to simulate receiving new notifications
        function receiveNotification() {
            newApplicationsCount++; // Increment new applications count
            updateNotificationCount();
            addApplicationToHistory();
        }

        // Function to add an application to the history
        function addApplicationToHistory() {
            const applicationId = newApplicationsCount;
            const applicationName = `Applicant ${applicationId}`;
            const applicationStatus = Math.random() > 0.5 ? 'Approved' : 'Denied'; // Randomly assign status
            const application = {
                id: applicationId,
                name: applicationName,
                status: applicationStatus,
                timestamp: new Date().toISOString()
            };
            applications.unshift(application); // Add to the beginning of the array

            // Update recent applications display
            updateRecentApplications();

            // If new applications exceed 5, keep only the latest 5
            if (applications.length > 5) {
                applications.pop(); // Remove the oldest application
            }
        }

        // Function to update the recent applications display
        function updateRecentApplications() {
            applicationHistory.innerHTML = ''; // Clear current display
            applications.forEach(app => {
                const applicationDiv = document.createElement('div');
                applicationDiv.className = 'application';
                applicationDiv.innerHTML = `Application ${app.id} (${app.name}): Status - <span class="${app.status.toLowerCase()}">${app.status}</span>`;
                applicationDiv.onclick = () => viewApplicationDetails(app); // Attach click event
                applicationHistory.appendChild(applicationDiv);
            });
        }

        // Function to filter applications based on search input
        function filterApplications() {
            const nameInput = document.getElementById('nameInput').value.toLowerCase();
            const dateInput = document.getElementById('dateInput').value;
            const filteredApps = applications.filter(app => 
                app.name.toLowerCase().includes(nameInput) &&
                (dateInput ? new Date(app.timestamp).toISOString().split('T')[0] === dateInput : true)
            );

            applicationHistory.innerHTML = ''; // Clear current display
            filteredApps.forEach(app => {
                const applicationDiv = document.createElement('div');
                applicationDiv.className = 'application';
                applicationDiv.innerHTML = `Application ${app.id} (${app.name}): Status - <span class="${app.status.toLowerCase()}">${app.status}</span>`;
                applicationDiv.onclick = () => viewApplicationDetails(app); // Attach click event
                applicationHistory.appendChild(applicationDiv);
            });
        }

        // Function to toggle the search container
        function toggleSearch() {
            const searchContainer = document.getElementById('searchContainer');
            searchContainer.style.display = searchContainer.style.display === 'flex' ? 'none' : 'flex';
        }

        // Function to show the full history of applications
        function showFullHistory() {
            fullHistory.innerHTML = ''; // Clear current full history display
            applications.forEach(app => {
                const applicationDiv = document.createElement('div');
                applicationDiv.className = 'application';
                applicationDiv.innerHTML = `Application ${app.id} (${app.name}): Status - <span class="${app.status.toLowerCase()}">${app.status}</span> (Received: ${new Date(app.timestamp).toLocaleString()})`;
                applicationDiv.onclick = () => viewApplicationDetails(app); // Attach click event
                fullHistory.appendChild(applicationDiv);
            });
            document.getElementById('fullHistoryModal').style.display = 'block'; // Show modal
        }

        // Function to close the full history modal
        function closeFullHistory() {
            document.getElementById('fullHistoryModal').style.display = 'none'; // Hide modal
        }

        // Function to view details of a specific application
        function viewApplicationDetails(app) {
            alert(`Application ID: ${app.id}\nName: ${app.name}\nStatus: ${app.status}\nReceived: ${new Date(app.timestamp).toLocaleString()}`);
        }

        // Simulate receiving a notification every 5 seconds
        setInterval(receiveNotification, 5000);
        updateNotificationCount(); // Initial update

        // Dropdown toggle function
        function toggleDropdown(event) {
            event.preventDefault();
            const dropdown = document.getElementById('settingsDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        // Theme toggle function
        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
        }

        // Close dropdown if clicked outside
        window.onclick = function(event) {
            if (!event.target.matches('.nav-tabs a')) {
                const dropdowns = document.getElementsByClassName("dropdown");
                for (let i = 0; i < dropdowns.length; i++) {
                    dropdowns[i].style.display = "none";
                }
                const searchContainer = document.getElementById('searchContainer');
                if (searchContainer.style.display === 'flex') {
                    searchContainer.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>