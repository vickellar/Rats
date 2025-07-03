// Global variables
let currentSection = "dashboard"
let invoiceData = []
let paymentData = []
let auditLog = []

// Initialize the application
document.addEventListener("DOMContentLoaded", () => {
  initializeApp()
  loadSampleData()
  setupEventListeners()
})

function initializeApp() {
  // Set initial active section
  showSection("dashboard")

  // Initialize notification count
  updateNotificationCount()

  // Setup sidebar toggle for mobile
  setupSidebarToggle()
}

function loadSampleData() {
  // Sample invoice data
  invoiceData = [
    {
      id: "INV-2024-001",
      vendor: "Acme Corporation",
      amount: 15000,
      date: "2024-01-15",
      status: "urgent",
      priority: "high",
      description: "Q1 Office Supplies",
      verified: false,
    },
    {
      id: "INV-2024-002",
      vendor: "Tech Solutions Ltd",
      amount: 8500,
      date: "2024-01-14",
      status: "pending",
      priority: "medium",
      description: "Software Licenses",
      verified: false,
    },
    {
      id: "INV-2024-003",
      vendor: "Office Supplies Inc",
      amount: 2300,
      date: "2024-01-13",
      status: "pending",
      priority: "low",
      description: "Monthly Supplies",
      verified: true,
    },
  ]

  // Sample payment data
  paymentData = [
    {
      id: "PAY-2024-045",
      vendor: "Global Services Inc",
      amount: 25000,
      description: "Q1 Consulting Services",
      dueDate: "2024-01-20",
      requestedBy: "John Smith",
      status: "pending",
    },
    {
      id: "PAY-2024-046",
      vendor: "Employee Reimbursement",
      amount: 1200,
      description: "Travel Expenses",
      dueDate: "2024-01-18",
      requestedBy: "Sarah Johnson",
      status: "pending",
    },
  ]

  // Sample audit log
  auditLog = [
    {
      timestamp: "2024-01-15 14:30",
      action: "Invoice INV-2024-001 approved",
      user: "Finance Director",
      type: "approved",
    },
    {
      timestamp: "2024-01-15 13:45",
      action: "Payment PAY-2024-044 rejected",
      user: "Finance Director",
      type: "rejected",
    },
    {
      timestamp: "2024-01-15 11:20",
      action: "Payment PAY-2024-043 approved",
      user: "Finance Director",
      type: "approved",
    },
  ]
}

function setupEventListeners() {
  // Navigation links
  document.querySelectorAll(".nav-link").forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault()
      const section = this.getAttribute("data-section")
      showSection(section)
      setActiveNavItem(this)
    })
  })

  // Modal close buttons
  document.querySelectorAll(".modal-close").forEach((button) => {
    button.addEventListener("click", function () {
      closeModal(this.closest(".modal"))
    })
  })

  // Invoice approval buttons
  document.addEventListener("click", (e) => {
    if (e.target.classList.contains("btn-approve") || e.target.closest(".btn-approve")) {
      handleApproval(e)
    }

    if (e.target.classList.contains("btn-reject") || e.target.closest(".btn-reject")) {
      handleRejection(e)
    }

    if (e.target.classList.contains("btn-review") || e.target.closest(".btn-review")) {
      handleReview(e)
    }
  })

  // Search functionality
  const searchInput = document.querySelector(".search-box input")
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      handleSearch(this.value)
    })
  }

  // Filter functionality
  document.querySelectorAll(".filter-select, .filter-input").forEach((filter) => {
    filter.addEventListener("change", () => {
      applyFilters()
    })
  })

  // Notification click
  document.querySelector(".notification-btn")?.addEventListener("click", () => {
    showSection("invoices")
    setActiveNavItem(document.querySelector('[data-section="invoices"]'))
  })
}

function setupSidebarToggle() {
  const sidebarToggle = document.querySelector(".sidebar-toggle")
  const sidebar = document.querySelector(".sidebar")

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener("click", () => {
      sidebar.classList.toggle("open")
    })

    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", (e) => {
      if (
        window.innerWidth <= 1024 &&
        !sidebar.contains(e.target) &&
        !sidebarToggle.contains(e.target) &&
        sidebar.classList.contains("open")
      ) {
        sidebar.classList.remove("open")
      }
    })
  }
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
    currentSection = sectionId

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
    case "invoices":
      renderInvoiceTable()
      break
    case "payments":
      renderPaymentCards()
      break
    case "audit":
      renderAuditTrail()
      break
    case "reports":
      // Reports are static for now
      break
    case "budget":
      updateBudgetDisplay()
      break
    default:
      // Dashboard is loaded by default
      break
  }
}

function renderInvoiceTable() {
  const tbody = document.querySelector("#invoices .data-table tbody")
  if (!tbody) return

  tbody.innerHTML = ""

  invoiceData.forEach((invoice) => {
    const row = document.createElement("tr")
    row.className = "invoice-row"
    row.setAttribute("data-invoice", invoice.id)

    row.innerHTML = `
            <td>${invoice.id}</td>
            <td>${invoice.vendor}</td>
            <td>$${invoice.amount.toLocaleString()}</td>
            <td>${invoice.date}</td>
            <td><span class="status-badge ${invoice.status}">${formatStatus(invoice.status)}</span></td>
            <td><span class="priority-badge ${invoice.priority}">${formatPriority(invoice.priority)}</span></td>
            <td>
                <button class="btn-action" onclick="openInvoiceModal('${invoice.id}')">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-action" onclick="downloadInvoice('${invoice.id}')">
                    <i class="fas fa-download"></i>
                </button>
            </td>
        `

    tbody.appendChild(row)
  })
}

function renderPaymentCards() {
  const container = document.querySelector(".payment-grid")
  if (!container) return

  container.innerHTML = ""

  paymentData.forEach((payment) => {
    const card = document.createElement("div")
    card.className = "payment-card"

    card.innerHTML = `
            <div class="payment-header">
                <span class="payment-id">${payment.id}</span>
                <span class="payment-amount">$${payment.amount.toLocaleString()}</span>
            </div>
            <div class="payment-details">
                <p><strong>Vendor:</strong> ${payment.vendor}</p>
                <p><strong>Description:</strong> ${payment.description}</p>
                <p><strong>Due Date:</strong> ${payment.dueDate}</p>
                <p><strong>Requested by:</strong> ${payment.requestedBy}</p>
            </div>
            <div class="payment-actions">
                <button class="btn-approve" data-payment="${payment.id}">
                    <i class="fas fa-check"></i>
                    Approve
                </button>
                <button class="btn-reject" data-payment="${payment.id}">
                    <i class="fas fa-times"></i>
                    Reject
                </button>
                <button class="btn-review" data-payment="${payment.id}">
                    <i class="fas fa-eye"></i>
                    Review
                </button>
            </div>
        `

    container.appendChild(card)
  })
}

function renderAuditTrail() {
  const container = document.querySelector(".audit-timeline")
  if (!container) return

  container.innerHTML = ""

  auditLog.forEach((entry) => {
    const item = document.createElement("div")
    item.className = "audit-item"

    item.innerHTML = `
            <div class="audit-time">${entry.timestamp}</div>
            <div class="audit-action ${entry.type}">${entry.action}</div>
            <div class="audit-user">by ${entry.user}</div>
        `

    container.appendChild(item)
  })
}

function updateBudgetDisplay() {
  // Update budget utilization
  const utilizationElements = document.querySelectorAll(".progress-fill")
  utilizationElements.forEach((element) => {
    const width = element.style.width
    // Animate the progress bar
    element.style.width = "0%"
    setTimeout(() => {
      element.style.width = width
    }, 100)
  })
}

function openInvoiceModal(invoiceId) {
  const invoice = invoiceData.find((inv) => inv.id === invoiceId)
  if (!invoice) return

  // Populate modal with invoice data
  document.getElementById("modalInvoiceNumber").textContent = invoice.id
  document.getElementById("modalVendor").textContent = invoice.vendor
  document.getElementById("modalAmount").textContent = `$${invoice.amount.toLocaleString()}`
  document.getElementById("modalDate").textContent = invoice.date

  // Show modal
  const modal = document.getElementById("invoiceModal")
  modal.style.display = "block"

  // Add event listeners for modal actions
  setupModalActions(invoice)
}

function setupModalActions(invoice) {
  const approveBtn = document.querySelector("#invoiceModal .btn-approve")
  const rejectBtn = document.querySelector("#invoiceModal .btn-reject")

  // Remove existing listeners
  approveBtn.replaceWith(approveBtn.cloneNode(true))
  rejectBtn.replaceWith(rejectBtn.cloneNode(true))

  // Add new listeners
  document.querySelector("#invoiceModal .btn-approve").addEventListener("click", () => {
    approveInvoice(invoice.id)
    closeModal(document.getElementById("invoiceModal"))
  })

  document.querySelector("#invoiceModal .btn-reject").addEventListener("click", () => {
    rejectInvoice(invoice.id)
    closeModal(document.getElementById("invoiceModal"))
  })
}

function closeModal(modal) {
  if (modal) {
    modal.style.display = "none"
  }
}

function handleApproval(e) {
  const button = e.target.closest(".btn-approve")
  const paymentId = button.getAttribute("data-payment")
  const invoiceId = button.getAttribute("data-invoice")

  if (paymentId) {
    approvePayment(paymentId)
  } else if (invoiceId) {
    approveInvoice(invoiceId)
  }
}

function handleRejection(e) {
  const button = e.target.closest(".btn-reject")
  const paymentId = button.getAttribute("data-payment")
  const invoiceId = button.getAttribute("data-invoice")

  if (paymentId) {
    rejectPayment(paymentId)
  } else if (invoiceId) {
    rejectInvoice(invoiceId)
  }
}

function handleReview(e) {
  const button = e.target.closest(".btn-review")
  const paymentId = button.getAttribute("data-payment")
  const invoiceId = button.getAttribute("data-invoice")

  if (paymentId) {
    reviewPayment(paymentId)
  } else if (invoiceId) {
    openInvoiceModal(invoiceId)
  }
}

function approveInvoice(invoiceId) {
  const invoice = invoiceData.find((inv) => inv.id === invoiceId)
  if (invoice) {
    invoice.status = "approved"

    // Add to audit log
    auditLog.unshift({
      timestamp: new Date().toLocaleString(),
      action: `Invoice ${invoiceId} approved`,
      user: "Finance Director",
      type: "approved",
    })

    // Show success notification
    showNotification("Invoice approved successfully", "success")

    // Refresh displays
    renderInvoiceTable()
    updateNotificationCount()
  }
}

function rejectInvoice(invoiceId) {
  const invoice = invoiceData.find((inv) => inv.id === invoiceId)
  if (invoice) {
    invoice.status = "rejected"

    // Add to audit log
    auditLog.unshift({
      timestamp: new Date().toLocaleString(),
      action: `Invoice ${invoiceId} rejected`,
      user: "Finance Director",
      type: "rejected",
    })

    // Show notification
    showNotification("Invoice rejected", "error")

    // Refresh displays
    renderInvoiceTable()
    updateNotificationCount()
  }
}

function approvePayment(paymentId) {
  const payment = paymentData.find((pay) => pay.id === paymentId)
  if (payment) {
    payment.status = "approved"

    // Add to audit log
    auditLog.unshift({
      timestamp: new Date().toLocaleString(),
      action: `Payment ${paymentId} approved`,
      user: "Finance Director",
      type: "approved",
    })

    // Show success notification
    showNotification("Payment approved successfully", "success")

    // Refresh displays
    renderPaymentCards()
  }
}

function rejectPayment(paymentId) {
  const payment = paymentData.find((pay) => pay.id === paymentId)
  if (payment) {
    payment.status = "rejected"

    // Add to audit log
    auditLog.unshift({
      timestamp: new Date().toLocaleString(),
      action: `Payment ${paymentId} rejected`,
      user: "Finance Director",
      type: "rejected",
    })

    // Show notification
    showNotification("Payment rejected", "error")

    // Refresh displays
    renderPaymentCards()
  }
}

function reviewPayment(paymentId) {
  // Open payment review modal or navigate to detailed view
  showNotification("Opening payment review...", "info")
}

function downloadInvoice(invoiceId) {
  // Simulate invoice download
  showNotification(`Downloading invoice ${invoiceId}...`, "info")
}

function handleSearch(query) {
  if (!query.trim()) {
    // Show all items if search is empty
    renderInvoiceTable()
    renderPaymentCards()
    return
  }

  // Filter data based on search query
  const filteredInvoices = invoiceData.filter(
    (invoice) =>
      invoice.id.toLowerCase().includes(query.toLowerCase()) ||
      invoice.vendor.toLowerCase().includes(query.toLowerCase()) ||
      invoice.description.toLowerCase().includes(query.toLowerCase()),
  )

  const filteredPayments = paymentData.filter(
    (payment) =>
      payment.id.toLowerCase().includes(query.toLowerCase()) ||
      payment.vendor.toLowerCase().includes(query.toLowerCase()) ||
      payment.description.toLowerCase().includes(query.toLowerCase()),
  )

  // Update displays with filtered data
  if (currentSection === "invoices") {
    renderFilteredInvoices(filteredInvoices)
  } else if (currentSection === "payments") {
    renderFilteredPayments(filteredPayments)
  }
}

function renderFilteredInvoices(filteredData) {
  const tbody = document.querySelector("#invoices .data-table tbody")
  if (!tbody) return

  tbody.innerHTML = ""

  if (filteredData.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #64748b;">No invoices found</td></tr>'
    return
  }

  filteredData.forEach((invoice) => {
    const row = document.createElement("tr")
    row.className = "invoice-row"
    row.setAttribute("data-invoice", invoice.id)

    row.innerHTML = `
            <td>${invoice.id}</td>
            <td>${invoice.vendor}</td>
            <td>$${invoice.amount.toLocaleString()}</td>
            <td>${invoice.date}</td>
            <td><span class="status-badge ${invoice.status}">${formatStatus(invoice.status)}</span></td>
            <td><span class="priority-badge ${invoice.priority}">${formatPriority(invoice.priority)}</span></td>
            <td>
                <button class="btn-action" onclick="openInvoiceModal('${invoice.id}')">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-action" onclick="downloadInvoice('${invoice.id}')">
                    <i class="fas fa-download"></i>
                </button>
            </td>
        `

    tbody.appendChild(row)
  })
}

function renderFilteredPayments(filteredData) {
  const container = document.querySelector(".payment-grid")
  if (!container) return

  container.innerHTML = ""

  if (filteredData.length === 0) {
    container.innerHTML =
      '<div style="text-align: center; padding: 2rem; color: #64748b; grid-column: 1 / -1;">No payments found</div>'
    return
  }

  filteredData.forEach((payment) => {
    const card = document.createElement("div")
    card.className = "payment-card"

    card.innerHTML = `
            <div class="payment-header">
                <span class="payment-id">${payment.id}</span>
                <span class="payment-amount">$${payment.amount.toLocaleString()}</span>
            </div>
            <div class="payment-details">
                <p><strong>Vendor:</strong> ${payment.vendor}</p>
                <p><strong>Description:</strong> ${payment.description}</p>
                <p><strong>Due Date:</strong> ${payment.dueDate}</p>
                <p><strong>Requested by:</strong> ${payment.requestedBy}</p>
            </div>
            <div class="payment-actions">
                <button class="btn-approve" data-payment="${payment.id}">
                    <i class="fas fa-check"></i>
                    Approve
                </button>
                <button class="btn-reject" data-payment="${payment.id}">
                    <i class="fas fa-times"></i>
                    Reject
                </button>
                <button class="btn-review" data-payment="${payment.id}">
                    <i class="fas fa-eye"></i>
                    Review
                </button>
            </div>
        `

    container.appendChild(card)
  })
}

function applyFilters() {
  // Get filter values
  const statusFilter = document.querySelector("#invoices .filter-select")?.value
  const amountFilter = document.querySelector("#payments .filter-select")?.value

  // Apply filters based on current section
  if (currentSection === "invoices" && statusFilter) {
    filterInvoicesByStatus(statusFilter)
  } else if (currentSection === "payments" && amountFilter) {
    filterPaymentsByAmount(amountFilter)
  }
}

function filterInvoicesByStatus(status) {
  let filteredData = invoiceData

  if (status !== "All Invoices") {
    const statusMap = {
      "Pending Approval": "pending",
      Approved: "approved",
      Rejected: "rejected",
      Urgent: "urgent",
    }

    filteredData = invoiceData.filter((invoice) => invoice.status === statusMap[status])
  }

  renderFilteredInvoices(filteredData)
}

function filterPaymentsByAmount(amountRange) {
  let filteredData = paymentData

  if (amountRange !== "All Amounts") {
    switch (amountRange) {
      case "Under $1,000":
        filteredData = paymentData.filter((payment) => payment.amount < 1000)
        break
      case "$1,000 - $10,000":
        filteredData = paymentData.filter((payment) => payment.amount >= 1000 && payment.amount <= 10000)
        break
      case "Over $10,000":
        filteredData = paymentData.filter((payment) => payment.amount > 10000)
        break
    }
  }

  renderFilteredPayments(filteredData)
}

function updateNotificationCount() {
  const pendingInvoices = invoiceData.filter(
    (invoice) => invoice.status === "pending" || invoice.status === "urgent",
  ).length

  const notificationBadge = document.querySelector(".notification-badge")
  const sidebarBadge = document.querySelector('.nav-link[data-section="invoices"] .badge')

  if (notificationBadge) {
    notificationBadge.textContent = pendingInvoices
    notificationBadge.style.display = pendingInvoices > 0 ? "block" : "none"
  }

  if (sidebarBadge) {
    sidebarBadge.textContent = pendingInvoices
    sidebarBadge.style.display = pendingInvoices > 0 ? "block" : "none"
  }
}

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

function formatStatus(status) {
  const statusMap = {
    pending: "Pending",
    approved: "Approved",
    rejected: "Rejected",
    urgent: "Urgent Review",
  }
  return statusMap[status] || status
}

function formatPriority(priority) {
  const priorityMap = {
    high: "High",
    medium: "Medium",
    low: "Low",
  }
  return priorityMap[priority] || priority
}

// Add CSS animations
const style = document.createElement("style")
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex: 1;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.25rem;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 0.25rem;
        transition: background-color 0.2s ease;
    }
    
    .notification-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }
`
document.head.appendChild(style)

// Export functions for global access
window.openInvoiceModal = openInvoiceModal
window.downloadInvoice = downloadInvoice
