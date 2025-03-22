<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Settings Page with Theme Switcher</title>
 
  <style>
    
    /* Base theme classes */
    body.light-theme {
      background-color: #f7f7f7;
      color: #000;

    }
    body.dark-theme {
      background-color: #1e1e1e;
      color: #fff;
    }
    /* Container styling */
    .container {
      max-width: 800px;
      margin: auto;
      background: #fff;
      padding: 2rem;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      border-radius: 8px;
    }
    /* Adjust the container for dark-theme */
    body.dark-theme .container {
      background: #2b2b2b;
      box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    }
    h1 {
      margin-bottom: 1.5rem;
    }
    .section {
      margin-bottom: 2rem;
    }
    .section h2 {
      font-size: 1.25rem;
      border-bottom: 1px solid #ddd;
      padding-bottom: 0.5rem;
      margin-bottom: 1rem;
    }
    .setting-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.5rem 0;
      border-bottom: 1px solid #eee;
    }
    label {
      flex: 1;
    }
    input[type="checkbox"],
    select,
    input[type="text"] {
      margin-left: 1rem;
    }
    .save-btn {
      display: block;
      width: 100%;
      padding: 0.75rem;
      background: #0078d4;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-align: center;
      font-size: 1rem;
      margin-top: 1rem;
    }
    /* Theme selector (icon) styling */
    .theme-selector {
      display: flex;
      gap: 20px;
      align-items: center;
    }
    .theme-icon {
      cursor: pointer;
      transition: transform 0.2s ease;
      padding: 10px;
      border-radius: 50%;
      background: #f0f0f0;
    }
    .theme-icon.selected,
    .theme-icon:hover {
      transform: scale(1.1);
      background: #d0d0d0;
    }
    body.dark-theme .theme-icon {
      background: #444;
    }
    body.dark-theme .theme-icon.selected,
    body.dark-theme .theme-icon:hover {
      background: #666;
    }
    svg {
      display: block;
    }
  </style>
  
</head>
<body class="light-theme">
  <div class="container">
    <h1>Settings</h1>
    
    <!-- Account Settings -->
    <div class="section">
      <h2>Account Settings</h2>
      <div class="setting-item">
        <label for="2fa">Enable Two-Factor Authentication</label>
        <input type="checkbox" id="2fa">
      </div>
    </div>
    
    <!-- Privacy Settings -->
    <div class="section">
      <h2>Privacy Settings</h2>
      <div class="setting-item">
        <label for="profile-visibility">Profile Visibility</label>
        <select id="profile-visibility">
          <option value="public">Public</option>
          <option value="private">Private</option>
        </select>
      </div>
    </div>
    
    <!-- Theme Settings: Integrated theme switcher with icons -->
    <div class="section">
      <h2>Theme Settings</h2>
      <div class="theme-selector">
        <!-- Light Theme Icon (Sun) -->
        <span class="theme-icon" id="light-icon" onclick="setTheme('light')">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-sun" viewBox="0 0 16 16">
            <path d="M8 4.5a3.5 3.5 0 1 1 0 7 3.5 3.5 0 0 1 0-7z"/>
            <path d="M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm4.546 1.454a.5.5 0 0 1 .707 0l1.414 1.414a.5.5 0 1 1-.707.707L12.546 2.161a.5.5 0 0 1 0-.707zM16 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 16 8zm-1.454 4.546a.5.5 0 0 1 0 .707l-1.414 1.414a.5.5 0 1 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zM8 16a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 1 0v2A.5.5 0 0 1 8 16zm-4.546-1.454a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM0 8a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2A.5.5 0 0 1 0 8zm1.454-4.546a.5.5 0 0 1 0 .707L.04 5.575a.5.5 0 1 1-.707-.707L.747 3.747a.5.5 0 0 1 .707 0z"/>
          </svg>
        </span>
        <!-- Dark Theme Icon (Moon) -->
        <span class="theme-icon" id="dark-icon" onclick="setTheme('dark')">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-moon" viewBox="0 0 16 16">
            <path d="M6 0a6 6 0 0 0 0 12 6 6 0 0 1 0-12z"/>
            <path d="M8.5 2a6.5 6.5 0 0 0 0 13 6.5 6.5 0 0 0 5.004-10.93 7.034 7.034 0 0 0-5.004-2.07z"/>
          </svg>
        </span>
      </div>
    </div>
    
    <!-- Save button for all settings -->
    <button class="save-btn" onclick="saveSettings()">Save Changes</button>
  </div>

  <script>
    // Function to save current settings
    function saveSettings() {
      const settingsData = {
        twoFactorAuth: document.getElementById('2fa').checked,
        profileVisibility: document.getElementById('profile-visibility').value,
        theme: document.body.classList.contains('dark-theme') ? 'dark' : 'light'
      };

      // Example: Log settings to console (replace with your API integration)
      console.log('Settings saved:', settingsData);
      alert('Your settings have been saved.');
    }

    // Function to set the theme and update the icon state
    function setTheme(theme) {
      // Remove existing theme classes then add the new one
      document.body.classList.remove('light-theme', 'dark-theme');
      document.body.classList.add(theme + '-theme');

      // Update icon visual state
      if (theme === 'light') {
        document.getElementById('light-icon').classList.add('selected');
        document.getElementById('dark-icon').classList.remove('selected');
      } else {
        document.getElementById('dark-icon').classList.add('selected');
        document.getElementById('light-icon').classList.remove('selected');
      }

      // Persist the user's theme choice
      localStorage.setItem('theme', theme);
    }

    // On page load, apply the stored theme preference or default to light
    document.addEventL
    stener('DOMContentLoaded', function() {
      const storedTheme = localStorage.getItem('theme') || 'light';
      setTheme(storedTheme);
    });
  </script>
</body>
</html>
