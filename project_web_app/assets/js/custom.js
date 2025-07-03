/**
 * Custom JavaScript
 * Fungsi-fungsi JavaScript umum untuk aplikasi
 */

// Global App Object
window.App = {
    config: window.APP_CONFIG || {},
    
    // Initialize application
    init: function() {
        this.initializeComponents();
        this.bindEvents();
        this.setupAjax();
        
        console.log('App initialized successfully');
    },
    
    // Initialize various components
    initializeComponents: function() {
        this.initDataTables();
        this.initFormValidation();
        this.initTooltips();
        this.initPopovers();
        this.initDatePickers();
        this.initFileUploads();
    },
    
    // Bind global events
    bindEvents: function() {
        // Auto-dismiss alerts
        this.setupAlertAutoDismiss();
        
        // Confirm delete actions
        this.setupDeleteConfirmation();
        
        // Form submission loading states
        this.setupFormLoadingStates();
        
        // CSRF token refresh
        this.setupCSRFRefresh();
    },
    
    // Setup AJAX defaults
    setupAjax: function() {
        // Set CSRF token for all AJAX requests
        if (typeof $ !== 'undefined') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': this.config.CSRF_TOKEN
                },
                error: function(xhr, status, error) {
                    App.handleAjaxError(xhr, status, error);
                }
            });
        }
        
        // Setup fetch defaults
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            if (args[1] && args[1].headers) {
                args[1].headers['X-CSRF-TOKEN'] = App.config.CSRF_TOKEN;
            } else if (args[1]) {
                args[1].headers = { 'X-CSRF-TOKEN': App.config.CSRF_TOKEN };
            } else {
                args[1] = { headers: { 'X-CSRF-TOKEN': App.config.CSRF_TOKEN } };
            }
            return originalFetch.apply(this, args);
        };
    },
    
    // Initialize DataTables
    initDataTables: function() {
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.data-table').each(function() {
                const table = $(this);
                const options = {
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "No entries found",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        },
                        emptyTable: "No data available",
                        zeroRecords: "No matching records found"
                    },
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                         '<"row"<"col-sm-12"tr>>' +
                         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    ...table.data('options') || {}
                };
                
                table.DataTable(options);
            });
        }
    },
    
    // Initialize form validation
    initFormValidation: function() {
        // Bootstrap validation
        document.querySelectorAll('.needs-validation').forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
        
        // Custom validation rules
        this.setupCustomValidation();
    },
    
    // Setup custom validation
    setupCustomValidation: function() {
        // Email validation
        document.querySelectorAll('input[type="email"]').forEach(input => {
            input.addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value && !emailRegex.test(this.value)) {
                    this.setCustomValidity('Please enter a valid email address');
                } else {
                    this.setCustomValidity('');
                }
            });
        });
        
        // Password confirmation
        const passwordFields = document.querySelectorAll('input[name="password_confirmation"]');
        passwordFields.forEach(confirmField => {
            const passwordField = document.querySelector('input[name="password"]');
            if (passwordField) {
                confirmField.addEventListener('input', function() {
                    if (this.value !== passwordField.value) {
                        this.setCustomValidity('Passwords do not match');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        });
    },
    
    // Initialize tooltips
    initTooltips: function() {
        if (typeof bootstrap !== 'undefined') {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
                new bootstrap.Tooltip(element);
            });
        }
    },
    
    // Initialize popovers
    initPopovers: function() {
        if (typeof bootstrap !== 'undefined') {
            document.querySelectorAll('[data-bs-toggle="popover"]').forEach(element => {
                new bootstrap.Popover(element);
            });
        }
    },
    
    // Initialize date pickers
    initDatePickers: function() {
        // Add date picker functionality if needed
        document.querySelectorAll('input[type="date"]').forEach(input => {
            // Set max date to today for birthdate fields
            if (input.name.includes('birth') || input.name.includes('dob')) {
                input.max = new Date().toISOString().split('T')[0];
            }
        });
    },
    
    // Initialize file uploads
    initFileUploads: function() {
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file size (5MB limit)
                    if (file.size > 5 * 1024 * 1024) {
                        App.showAlert('File size must be less than 5MB', 'error');
                        this.value = '';
                        return;
                    }
                    
                    // Show file name
                    const label = this.nextElementSibling;
                    if (label && label.classList.contains('custom-file-label')) {
                        label.textContent = file.name;
                    }
                    
                    // Preview image if it's an image file
                    if (file.type.startsWith('image/')) {
                        App.previewImage(this, file);
                    }
                }
            });
        });
    },
    
    // Preview uploaded image
    previewImage: function(input, file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let preview = input.parentNode.querySelector('.image-preview');
            if (!preview) {
                preview = document.createElement('img');
                preview.className = 'image-preview mt-2';
                preview.style.maxWidth = '200px';
                preview.style.maxHeight = '200px';
                preview.style.objectFit = 'cover';
                preview.style.border = '1px solid #ddd';
                preview.style.borderRadius = '5px';
                input.parentNode.appendChild(preview);
            }
            preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    },
    
    // Setup alert auto-dismiss
    setupAlertAutoDismiss: function() {
        setTimeout(() => {
            document.querySelectorAll('.alert-dismissible').forEach(alert => {
                if (typeof bootstrap !== 'undefined') {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) bsAlert.close();
                }
            });
        }, 5000);
    },
    
    // Setup delete confirmation
    setupDeleteConfirmation: function() {
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('[data-action="delete"]');
            if (deleteBtn) {
                e.preventDefault();
                
                const itemName = deleteBtn.dataset.itemName || 'this item';
                const confirmMessage = `Are you sure you want to delete ${itemName}? This action cannot be undone.`;
                
                if (confirm(confirmMessage)) {
                    // If it's a form, submit it
                    const form = deleteBtn.closest('form');
                    if (form) {
                        form.submit();
                    } else if (deleteBtn.href) {
                        window.location.href = deleteBtn.href;
                    }
                }
            }
        });
    },
    
    // Setup form loading states
    setupFormLoadingStates: function() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                }
            });
        });
    },
    
    // Setup CSRF token refresh
    setupCSRFRefresh: function() {
        // Refresh CSRF token every 30 minutes
        setInterval(() => {
            this.refreshCSRFToken();
        }, 30 * 60 * 1000);
    },
    
    // Refresh CSRF token
    refreshCSRFToken: function() {
        fetch('/auth/check_session.php')
            .then(response => response.json())
            .then(data => {
                if (data.logged_in && data.csrf_token) {
                    this.config.CSRF_TOKEN = data.csrf_token;
                    
                    // Update meta tag
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    if (metaTag) {
                        metaTag.content = data.csrf_token;
                    }
                }
            })
            .catch(error => {
                console.error('CSRF token refresh failed:', error);
            });
    },
    
    // Handle AJAX errors
    handleAjaxError: function(xhr, status, error) {
        let message = 'An error occurred';
        
        if (xhr.status === 401) {
            message = 'Session expired. Please login again.';
            setTimeout(() => {
                window.location.href = '/auth/login.php';
            }, 2000);
        } else if (xhr.status === 403) {
            message = 'Access denied';
        } else if (xhr.status === 404) {
            message = 'Resource not found';
        } else if (xhr.status === 500) {
            message = 'Server error occurred';
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        }
        
        this.showAlert(message, 'error');
    },
    
    // Show alert message
    showAlert: function(message, type = 'info', duration = 5000) {
        const alertId = 'alert_' + Date.now();
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${icons[type] || icons.info} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        let container = document.getElementById('flashMessagesContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'flashMessagesContainer';
            container.className = 'position-fixed';
            container.style.cssText = 'top: 80px; right: 20px; z-index: 1050; max-width: 400px;';
            document.body.appendChild(container);
        }
        
        container.insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                const alertElement = document.getElementById(alertId);
                if (alertElement && typeof bootstrap !== 'undefined') {
                    const alert = bootstrap.Alert.getOrCreateInstance(alertElement);
                    if (alert) alert.close();
                }
            }, duration);
        }
    },
    
    // Show loading overlay
    showLoading: function() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'flex';
        }
    },
    
    // Hide loading overlay
    hideLoading: function() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    },
    
    // Format currency
    formatCurrency: function(amount, currency = 'IDR') {
        const formatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
        return formatter.format(amount);
    },
    
    // Format date
    formatDate: function(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        const formatter = new Intl.DateTimeFormat('id-ID', { ...defaultOptions, ...options });
        return formatter.format(new Date(date));
    },
    
    // Debounce function
    debounce: function(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func.apply(this, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(this, args);
        };
    },
    
    // Throttle function
    throttle: function(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    // Local storage helpers
    storage: {
        set: function(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
                return true;
            } catch (e) {
                console.error('localStorage not available:', e);
                return false;
            }
        },
        
        get: function(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (e) {
                console.error('localStorage not available:', e);
                return defaultValue;
            }
        },
        
        remove: function(key) {
            try {
                localStorage.removeItem(key);
                return true;
            } catch (e) {
                console.error('localStorage not available:', e);
                return false;
            }
        },
        
        clear: function() {
            try {
                localStorage.clear();
                return true;
            } catch (e) {
                console.error('localStorage not available:', e);
                return false;
            }
        }
    },
    
    // API helpers
    api: {
        get: function(url, options = {}) {
            return fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': App.config.CSRF_TOKEN,
                    ...options.headers || {}
                },
                ...options
            }).then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
        },
        
        post: function(url, data = {}, options = {}) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': App.config.CSRF_TOKEN,
                    ...options.headers || {}
                },
                body: JSON.stringify(data),
                ...options
            }).then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
        },
        
        put: function(url, data = {}, options = {}) {
            return fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': App.config.CSRF_TOKEN,
                    ...options.headers || {}
                },
                body: JSON.stringify(data),
                ...options
            }).then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
        },
        
        delete: function(url, options = {}) {
            return fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': App.config.CSRF_TOKEN,
                    ...options.headers || {}
                },
                ...options
            }).then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
        }
    },
    
    // Utility functions
    utils: {
        // Generate random string
        randomString: function(length = 10) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
           }
           return result;
       },
       
       // Capitalize first letter
       capitalize: function(str) {
           return str.charAt(0).toUpperCase() + str.slice(1);
       },
       
       // Truncate text
       truncate: function(str, length = 100, suffix = '...') {
           if (str.length <= length) return str;
           return str.substring(0, length) + suffix;
       },
       
       // Validate email
       isValidEmail: function(email) {
           const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
           return emailRegex.test(email);
       },
       
       // Validate phone number
       isValidPhone: function(phone) {
           const phoneRegex = /^(\+62|62|0)8[1-9][0-9]{6,9}$/;
           return phoneRegex.test(phone.replace(/\s+/g, ''));
       },
       
       // Format file size
       formatFileSize: function(bytes) {
           if (bytes === 0) return '0 Bytes';
           const k = 1024;
           const sizes = ['Bytes', 'KB', 'MB', 'GB'];
           const i = Math.floor(Math.log(bytes) / Math.log(k));
           return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
       },
       
       // Copy to clipboard
       copyToClipboard: function(text) {
           if (navigator.clipboard) {
               return navigator.clipboard.writeText(text).then(() => {
                   App.showAlert('Copied to clipboard!', 'success', 2000);
               });
           } else {
               // Fallback for older browsers
               const textArea = document.createElement('textarea');
               textArea.value = text;
               document.body.appendChild(textArea);
               textArea.select();
               document.execCommand('copy');
               document.body.removeChild(textArea);
               App.showAlert('Copied to clipboard!', 'success', 2000);
           }
       },
       
       // Scroll to element
       scrollTo: function(element, offset = 0) {
           const targetElement = typeof element === 'string' ? document.querySelector(element) : element;
           if (targetElement) {
               const targetPosition = targetElement.offsetTop - offset;
               window.scrollTo({
                   top: targetPosition,
                   behavior: 'smooth'
               });
           }
       }
   }
};

// Admin Panel specific functions
window.AdminPanel = {
   // Toggle sidebar
   toggleSidebar: function() {
       const sidebar = document.querySelector('.admin-sidebar');
       const content = document.querySelector('.admin-content');
       const overlay = document.querySelector('.sidebar-overlay');
       
       if (window.innerWidth > 991) {
           // Desktop: collapse/expand
           sidebar.classList.toggle('collapsed');
           content.classList.toggle('expanded');
       } else {
           // Mobile: show/hide
           sidebar.classList.toggle('show');
           if (overlay) {
               overlay.classList.toggle('show');
           }
       }
   },
   
   // Initialize admin dashboard
   initDashboard: function() {
       this.setupSidebarToggle();
       this.setupMobileOverlay();
       this.loadDashboardStats();
       this.initCharts();
   },
   
   // Setup sidebar toggle
   setupSidebarToggle: function() {
       const toggleBtn = document.querySelector('.sidebar-toggle');
       if (toggleBtn) {
           toggleBtn.addEventListener('click', this.toggleSidebar);
       }
   },
   
   // Setup mobile overlay
   setupMobileOverlay: function() {
       const overlay = document.querySelector('.sidebar-overlay');
       if (overlay) {
           overlay.addEventListener('click', this.toggleSidebar);
       }
       
       // Close sidebar on window resize to desktop
       window.addEventListener('resize', () => {
           if (window.innerWidth > 991) {
               const sidebar = document.querySelector('.admin-sidebar');
               const overlay = document.querySelector('.sidebar-overlay');
               
               if (sidebar) sidebar.classList.remove('show');
               if (overlay) overlay.classList.remove('show');
           }
       });
   },
   
   // Load dashboard statistics
   loadDashboardStats: function() {
       // This would typically fetch from an API
       const statCards = document.querySelectorAll('.stat-card-admin');
       statCards.forEach(card => {
           card.addEventListener('click', function() {
               const link = this.dataset.link;
               if (link) {
                   window.location.href = link;
               }
           });
       });
   },
   
   // Initialize charts
   initCharts: function() {
       // Check if Chart.js is available
       if (typeof Chart !== 'undefined') {
           this.initDashboardCharts();
       }
   },
   
   // Initialize dashboard charts
   initDashboardCharts: function() {
       // Sample chart initialization
       const chartElements = document.querySelectorAll('.chart-container canvas');
       chartElements.forEach(canvas => {
           const type = canvas.dataset.chartType || 'line';
           const ctx = canvas.getContext('2d');
           
           // Sample data - would be loaded from API in real application
           new Chart(ctx, {
               type: type,
               data: {
                   labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                   datasets: [{
                       label: 'Sample Data',
                       data: [12, 19, 3, 5, 2, 3],
                       borderColor: '#667eea',
                       backgroundColor: 'rgba(102, 126, 234, 0.1)',
                       tension: 0.4
                   }]
               },
               options: {
                   responsive: true,
                   maintainAspectRatio: false,
                   plugins: {
                       legend: {
                           display: false
                       }
                   },
                   scales: {
                       y: {
                           beginAtZero: true
                       }
                   }
               }
           });
       });
   }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
   // Initialize main app
   App.init();
   
   // Initialize admin panel if we're on admin pages
   if (document.querySelector('.admin-container')) {
       AdminPanel.initDashboard();
   }
   
   // Global keyboard shortcuts
   document.addEventListener('keydown', function(e) {
       // Ctrl/Cmd + K for search
       if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
           e.preventDefault();
           const searchInput = document.querySelector('input[type="search"], .search-box input');
           if (searchInput) {
               searchInput.focus();
           }
       }
       
       // ESC to close modals
       if (e.key === 'Escape') {
           const openModal = document.querySelector('.modal.show');
           if (openModal && typeof bootstrap !== 'undefined') {
               const modal = bootstrap.Modal.getInstance(openModal);
               if (modal) modal.hide();
           }
       }
   });
});

// Export for use in other scripts
window.App = App;
window.AdminPanel = AdminPanel;