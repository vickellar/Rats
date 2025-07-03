// Rate Clearance Service JavaScript

// Global variables
let currentRateData = null
const filteredRates = []

// Initialize the application
document.addEventListener("DOMContentLoaded", () => {
  initializeRateClearance()
  setupEventListeners()
  loadRateData()
})

function initializeRateClearance() {
  // Set initial active section
  showSection("rate-clearance")

  // Initialize filters
  setupFilters()

  // Setup modal event listeners
  setupModalListeners()
}

function setupEventListeners() {
  // Navigation links
  document.querySelectorAll(".nav-link").forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault()
      const section = this.getAttribute("data-section")
      if (section) {
        showSection(section)
        setActiveNavItem(this)
      }
    })
  })

  // Search functionality
  const searchInput = document.querySelector(".search-box input")
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      handleSearch(this.value)
    })
  }

  // Filter change events
  document.getElementById("priorityFilter")?.addEventListener("change", applyFilters)
  document.getElementById("typeFilter")?.addEventListener("change", applyFilters)

  // Approval decision change
  document.getElementById("approvalDecision")?.addEventListener("change", function () {
    const effectiveDateGroup = document.getElementById("effectiveDateGroup")
    if (this.value === "approve") {
      effectiveDateGroup.style.display = "block"
    } else {
      effectiveDateGroup.style.display = "none"
    }
  })
}

function setupModalListeners() {
  // Modal close buttons
  document.querySelectorAll(".modal-close").forEach((button) => {
    button.addEventListener("click", function () {
      closeModal(this.closest(".modal").id)
    })
  })

  // Click outside modal to close
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.addEventListener("click", function (e) {
      if (e.target === this) {
        closeModal(this.id)
      }
    })
  })
}

function setupFilters() {
  // Initialize filter dropdowns with current data
  updateFilterOptions()
}

function loadRateData() {
  // In a real application, this would fetch data from the server
  // For now, we'll use the data already loaded in PHP
  console.log("Rate data loaded")
}

function showSection(sectionId) {
  // Hide all sections
  document.querySelectorAll(".content-section").forEach((section) => {
    section.classList.remove("active")
  })

  // Show target section
  const targetSection = document.getElementById(sectionId)
  if (targetSection) {
    targetSection.classList.add("active")

    // Load section-specific data
    loadSectionData(sectionId)
  }
}

function setActiveNavItem(activeLink) {
  // Remove active class from all nav items
  document.querySelectorAll(".nav-item").forEach((item) => {
    item.classList.remove("active")
  })

  // Add active class to parent nav item
  if (activeLink) {
    activeLink.closest(".nav-item").classList.add("active")
  }
}

function loadSectionData(sectionId) {
  switch (sectionId) {
    case "rate-clearance":
      refreshRateTable()
      break
    case "rate-management":
      loadRateCategories()
      break
    case "compliance":
      updateComplianceMetrics()
      break
    case "reports":
      loadReportData()
      break
  }
}

// Rate Management Functions
function reviewRate(rateId) {
  // Fetch rate details and populate modal
  fetchRateDetails(rateId).then((rate) => {
    populateReviewModal(rate)
    openModal("rateReviewModal")
  })
}

function approveRate(rateId) {
  if (confirm("Are you sure you want to approve this rate?")) {
    submitRateDecision(rateId, "approve", "Quick approval")
  }
}

function rejectRate(rateId) {
  const reason = prompt("Please provide a reason for rejection:")
  if (reason) {
    submitRateDecision(rateId, "reject", reason)
  }
}

function fetchRateDetails(rateId) {
  // Simulate API call
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve({
        id: rateId,
        rate_code: "RC-2024-001",
        rate_type_name: "Consulting Services",
        entity_name: "Acme Consulting",
        current_rate: 150.0,
        proposed_rate: 175.0,
        rate_unit: "hour",
        justification: "Rate increase requested due to market conditions and increased expertise requirements.",
        priority: "high",
        submitted_by: "John Smith",
        created_at: "2024-01-15",
      })
    }, 500)
  })
}

function populateReviewModal(rate) {
  document.getElementById("reviewRateCode").textContent = rate.rate_code
  document.getElementById("reviewRateType").textContent = rate.rate_type_name
  document.getElementById("reviewEntity").textContent = rate.entity_name
  document.getElementById("reviewCurrentRate").textContent = `$${rate.current_rate.toFixed(2)}/${rate.rate_unit}`
  document.getElementById("reviewProposedRate").textContent = `$${rate.proposed_rate.toFixed(2)}/${rate.rate_unit}`

  const change = rate.proposed_rate - rate.current_rate
  const changePercent = ((change / rate.current_rate) * 100).toFixed(1)
  const changeElement = document.getElementById("reviewRateChange")
  changeElement.textContent = `${change >= 0 ? "+" : ""}$${change.toFixed(2)} (${change >= 0 ? "+" : ""}${changePercent}%)`
  changeElement.className = change >= 0 ? "rate-increase" : "rate-decrease"

  document.getElementById("reviewJustification").textContent = rate.justification

  // Store current rate data for submission
  currentRateData = rate
}

function submitRateDecision() {
  const decision = document.getElementById("approvalDecision").value
  const comments = document.getElementById("approvalComments").value
  const effectiveDate = document.getElementById("effectiveDate").value

  if (!decision) {
    showNotification("Please select a decision", "error")
    return
  }

  if (!currentRateData) {
    showNotification("No rate data available", "error")
    return
  }

  // Submit the decision
  submitRateDecisionAPI(currentRateData.id, decision, comments, effectiveDate)
    .then((response) => {
      if (response.success) {
        showNotification(`Rate ${decision}d successfully`, "success")
        closeModal("rateReviewModal")
        refreshRateTable()
      } else {
        showNotification(`Failed to ${decision} rate: ${response.error}`, "error")
      }
    })
    .catch((error) => {
      showNotification(`Error: ${error.message}`, "error")
    })
}

function submitRateDecisionAPI(rateId, decision, comments, effectiveDate) {
  // Simulate API call
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve({
        success: true,
        message: `Rate ${decision}d successfully`,
      })
    }, 1000)
  })
}

// New Rate Request Functions
function openNewRateModal() {
  // Reset form
  document.getElementById("newRateForm").reset()

  // Set default effective date to today
  document.getElementById("effectiveFrom").value = new Date().toISOString().split("T")[0]

  openModal("newRateModal")
}

function submitNewRate() {
  const form = document.getElementById("newRateForm")
  const formData = new FormData(form)

  // Validate form
  if (!form.checkValidity()) {
    form.reportValidity()
    return
  }

  // Collect form data
  const rateData = {
    rate_type_id: document.getElementById("rateType").value,
    entity_type: document.getElementById("entityType").value,
    proposed_rate: Number.parseFloat(document.getElementById("proposedRate").value),
    rate_unit: document.getElementById("rateUnit").value,
    priority: document.getElementById("priority").value,
    justification: document.getElementById("justification").value,
    effective_from: document.getElementById("effectiveFrom").value,
    expires_at: document.getElementById("expiresAt").value,
  }

  // Submit new rate request
  submitNewRateAPI(rateData)
    .then((response) => {
      if (response.success) {
        showNotification("Rate request submitted successfully", "success")
        closeModal("newRateModal")
        refreshRateTable()
      } else {
        showNotification(`Failed to submit rate request: ${response.error}`, "error")
      }
    })
    .catch((error) => {
      showNotification(`Error: ${error.message}`, "error")
    })
}

function submitNewRateAPI(rateData) {
  // Simulate API call
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve({
        success: true,
        rate_id: "RC-2024-" + Math.floor(Math.random() * 1000),
        message: "Rate request submitted successfully",
      })
    }, 1000)
  })
}

// Filter and Search Functions
function applyFilters() {
  const priorityFilter = document.getElementById("priorityFilter").value
  const typeFilter = document.getElementById("typeFilter").value

  // Apply filters to rate table
  const rows = document.querySelectorAll(".rate-row")

  rows.forEach((row) => {
    let showRow = true

    if (priorityFilter) {
      const priorityBadge = row.querySelector(".priority-badge")
      if (!priorityBadge || !priorityBadge.classList.contains(priorityFilter)) {
        showRow = false
      }
    }

    if (typeFilter && showRow) {
      // This would need to be implemented based on data attributes
      // For now, we'll skip this filter
    }

    row.style.display = showRow ? "" : "none"
  })

  updateFilteredCount()
}

function handleSearch(query) {
  if (!query.trim()) {
    // Show all rows if search is empty
    document.querySelectorAll(".rate-row").forEach((row) => {
      row.style.display = ""
    })
    return
  }

  const rows = document.querySelectorAll(".rate-row")

  rows.forEach((row) => {
    const text = row.textContent.toLowerCase()
    const matches = text.includes(query.toLowerCase())
    row.style.display = matches ? "" : "none"
  })

  updateFilteredCount()
}

function updateFilteredCount() {
  const visibleRows = document.querySelectorAll(".rate-row:not([style*='display: none'])")
  const countBadge = document.querySelector(".count-badge")
  if (countBadge) {
    countBadge.textContent = visibleRows.length
  }
}

function updateFilterOptions() {
  // Update filter dropdowns based on current data
  // This would be implemented based on actual data structure
}

// Utility Functions
function refreshRateTable() {
  // Refresh the rate table data
  // In a real application, this would fetch fresh data from the server
  console.log("Refreshing rate table...")
}

function loadRateCategories() {
  // Load rate category data for management section
  console.log("Loading rate categories...")
}

function updateComplianceMetrics() {
  // Update compliance dashboard metrics
  console.log("Updating compliance metrics...")
}

function loadReportData() {
  // Load report data
  console.log("Loading report data...")
}

// Modal Functions
function openModal(modalId) {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.style.display = "block"
    document.body.style.overflow = "hidden"
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.style.display = "none"
    document.body.style.overflow = ""
  }

  // Clear current rate data
  currentRateData = null
}

// Report Functions
function generateTrendReport() {
  showNotification("Generating rate trend report...", "info")
  // Implement report generation
}

function generateVendorReport() {
  showNotification("Generating vendor rate comparison...", "info")
  // Implement vendor report generation
}

function generateComplianceReport() {
  showNotification("Generating compliance report...", "info")
  // Implement compliance report generation
}

function generateCostReport() {
  showNotification("Generating cost impact analysis...", "info")
  // Implement cost report generation
}

function generateCustomReport() {
  showNotification("Opening custom report builder...", "info")
  // Implement custom report builder
}

function scheduleReport() {
  showNotification("Opening report scheduler...", "info")
  // Implement report scheduling
}

function exportRateData() {
  showNotification("Exporting rate data...", "info")
  // Implement data export functionality
}

// Rate Type Management Functions
function openRateTypeModal() {
  showNotification("Opening rate type configuration...", "info")
  // Implement rate type modal
}

function editRateType(typeId) {
  showNotification(`Editing rate type ${typeId}...`, "info")
  // Implement rate type editing
}

function viewRateHistory(typeId) {
  showNotification(`Loading rate history for type ${typeId}...`, "info")
  // Implement rate history view
}

function bulkUpdateRates() {
  showNotification("Opening bulk update tool...", "info")
  // Implement bulk rate updates
}

// Compliance Functions
function scheduleAudit() {
  showNotification("Opening audit scheduler...", "info")
  // Implement audit scheduling
}

function viewExpiringRates() {
  showNotification("Loading expiring rates...", "info")
  // Filter to show only expiring rates
  applyExpiringFilter()
}

function viewExpiredRates() {
  showNotification("Loading expired rates...", "info")
  // Filter to show only expired rates
  applyExpiredFilter()
}

function applyExpiringFilter() {
  // Implementation for filtering expiring rates
  const rows = document.querySelectorAll(".rate-row")
  rows.forEach((row) => {
    // This would check expiration dates in real implementation
    row.style.display = ""
  })
}

function applyExpiredFilter() {
  // Implementation for filtering expired rates
  const rows = document.querySelectorAll(".rate-row")
  rows.forEach((row) => {
    // This would check expired status in real implementation
    row.style.display = ""
  })
}

// Notification System
function showNotification(message, type = "info") {
  // Create notification element
  const notification = document.createElement("div")
  notification.className = `notification notification-${type}`
  notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">&times;</button>
    `

  // Add styles
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 3000;
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `

  // Add to document
  document.body.appendChild(notification)

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.style.animation = "slideOut 0.3s ease"
      setTimeout(() => {
        notification.remove()
      }, 300)
    }
  }, 5000)

  // Manual close
  notification.querySelector(".notification-close").addEventListener("click", () => {
    notification.style.animation = "slideOut 0.3s ease"
    setTimeout(() => {
      notification.remove()
    }, 300)
  })
}

function getNotificationIcon(type) {
  const icons = {
    success: "check-circle",
    error: "exclamation-circle",
    warning: "exclamation-triangle",
    info: "info-circle",
  }
  return icons[type] || "info-circle"
}

function getNotificationColor(type) {
  const colors = {
    success: "#10b981",
    error: "#ef4444",
    warning: "#f59e0b",
    info: "#3b82f6",
  }
  return colors[type] || "#3b82f6"
}

// Export functions for global access
window.reviewRate = reviewRate
window.approveRate = approveRate
window.rejectRate = rejectRate
window.openNewRateModal = openNewRateModal
window.submitNewRate = submitNewRate
window.submitRateDecision = submitRateDecision
window.applyFilters = applyFilters
window.exportRateData = exportRateData
window.generateTrendReport = generateTrendReport
window.generateVendorReport = generateVendorReport
window.generateComplianceReport = generateComplianceReport
window.generateCostReport = generateCostReport
window.generateCustomReport = generateCustomReport
window.scheduleReport = scheduleReport
window.openRateTypeModal = openRateTypeModal
window.editRateType = editRateType
window.viewRateHistory = viewRateHistory
window.bulkUpdateRates = bulkUpdateRates
window.scheduleAudit = scheduleAudit
window.viewExpiringRates = viewExpiringRates
window.viewExpiredRates = viewExpiredRates
window.closeModal = closeModal
