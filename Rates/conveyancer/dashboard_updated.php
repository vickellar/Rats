<script>
function toggleDetails(summaryElement, targetId) {
    const detailsElement = document.getElementById(targetId);
    const isExpanded = detailsElement.style.display === 'block';
    
    // Close all other open details first
    document.querySelectorAll('.notification-details, .application-details').forEach(detail => {
        if (detail.id !== targetId) {
            detail.style.display = 'none';
            detail.previousElementSibling.classList.remove('expanded');
        }
    });
    
    // Toggle current details
    if (isExpanded) {
        detailsElement.style.display = 'none';
        summaryElement.classList.remove('expanded');
    } else {
        detailsElement.style.display = 'block';
        summaryElement.classList.add('expanded');
    }
}

// Fetch and update notifications with better error handling
function fetchNotifications() {
    console.log('Fetching notifications...');
    
    fetch('fetch_dashboard_data.php')
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text(); // Get as text first to see what we're getting
        })
        .then(text => {
            console.log('Raw response:', text);
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid JSON response: ' + text.substring(0, 100));
            }
            
            console.log('Parsed data:', data);
            
            const notifSection = document.getElementById('notifications-section');
            
            // Show debug info if available
            if (data.debug && data.debug.length > 0) {
                console.log('Debug info:', data.debug);
            }
            
            if (data.error) {
                notifSection.innerHTML = `
                    <p style="color: #dc2626; text-align: center; padding: 2rem;">
                        ${data.error}
                        ${data.debug ? '<br><small>Debug: ' + data.debug.join(', ') + '</small>' : ''}
                    </p>`;
            } else if (data.notifications && data.notifications.length > 0) {
                notifSection.innerHTML = data.notifications.map((notif, idx) => {
                    const notifId = 'notif-' + idx;
                    let monthsHtml = '';
                    if (notif.months && notif.months.length > 0) {
                        monthsHtml = `<div style="margin-top:1rem;"><strong>Monthly Breakdown:</strong><ul style='margin:0; padding-left:1.2em;'>` +
                            notif.months.map(month => {
                                let names = [month.month1_name, month.month2_name, month.month3_name, month.month4_name].filter(Boolean).join(', ');
                                return `<li>${names ? names + ': ' : ''}${month.month_balance}</li>`;
                            }).join('') +
                            `</ul></div>`;
                    }
                    return `
                    <div class="notification-item">
                        <div class="notification-summary" onclick="toggleDetails(this, '${notifId}')">
                            <p><strong>Bill invoice for:</strong> ${notif.account_id}</p>
                        </div>
                        <div class="notification-details" id="${notifId}">
                            <div class="details-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Account ID</div>
                                    <div class="detail-value">${notif.account_id}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Property</div>
                                    <div class="detail-value">${notif.address}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Total Balance</div>
                                    <div class="detail-value">${notif.total_balance}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Processing Fee</div>
                                    <div class="detail-value">${notif.processing_fee}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Overall Total</div>
                                    <div class="detail-value" style="font-weight: 600;">${notif.overall_total}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Calculated On</div>
                                    <div class="detail-value">${notif.created_at || notif.calculated_at}</div>
                                </div>
                            </div>
                            ${monthsHtml}
                            <div style="margin-top: 1rem;">
                                <a href="${notif.view_link || 'view_bill_details.php?application_id=' + notif.application_id}" style="color: #3b82f6; text-decoration: none; font-weight: 600;">View Full Details</a>
                            </div>
                        </div>
                    </div>
                    `;
                }).join('');
            } else {
                notifSection.innerHTML = '<p style="text-align: center; color: #64748b; padding: 2rem;">No new notifications</p>';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            document.getElementById('notifications-section').innerHTML = `
                <p style="color: #dc2626; text-align: center; padding: 2rem;">
                    Failed to fetch notifications: ${error.message}
                    <br><small>Check browser console for details</small>
                </p>`;
        });
}

// Initial load when page loads
fetchNotifications();

// Auto-refresh every 30 seconds
setInterval(fetchNotifications, 30000);

// Add smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add loading states for buttons
document.querySelectorAll('.quick-action-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const icon = this.querySelector('i');
        const originalClass = icon.className;
        icon.className = 'loading';
        
        setTimeout(() => {
            icon.className = originalClass;
        }, 1000);
    });
});
</script>
