/*

    E-VOTING SYSTEM JAVASCRIPT


*/

/**
 * VOTING PAGE FUNCTIONALITY
 * Handles candidate selection and vote submission
 */

// Wait for the page to fully load before running any code
document.addEventListener('DOMContentLoaded', function() {
    console.log('E-Voting System initialized successfully');
    
    // Initialize all functionality
    initializeVotingSystem();
    initializeFormValidation();
    initializeAnimations();
});

/**
 * Initialize the main voting system functionality
 */
function initializeVotingSystem() {
    console.log('Setting up voting interface...');
    
    // Find the voting form on the page
    const votingForm = document.getElementById('votingForm');
    if (votingForm) {
        setupVotingForm(votingForm);
    }
    
    // Find all candidate cards
    const candidateCards = document.querySelectorAll('.candidate-card');
    if (candidateCards.length > 0) {
        setupCandidateSelection(candidateCards);
    }
}

/**
 * Setup candidate card selection functionality
 * @param {NodeList} candidateCards - All candidate cards on the page
 */
function setupCandidateSelection(candidateCards) {
    candidateCards.forEach(card => {
        // Add click event to each candidate card
        card.addEventListener('click', function() {
            // Get the position this candidate belongs to
            const positionSection = card.closest('.position-section');
            
            if (positionSection) {
                // Get the position name from the header
                const positionName = positionSection.querySelector('h4').textContent.trim();
                
                // Handle the candidate selection
                selectCandidate(card, positionSection, positionName);
            }
        });
        
        // Add hover effects for better user experience
        card.addEventListener('mouseenter', function() {
            if (!card.classList.contains('selected')) {
                card.style.transform = 'translateY(-2px)';
                card.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            if (!card.classList.contains('selected')) {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '';
            }
        });
    });
}

/**
 * Handle candidate selection logic
 * @param {Element} selectedCard - The card that was clicked
 * @param {Element} positionSection - The position section container
 * @param {string} positionName - Name of the position
 */
function selectCandidate(selectedCard, positionSection, positionName) {
    console.log(`Selecting candidate for position: ${positionName}`);
    
    // Remove selection from all other candidates in this position
    const allCardsInPosition = positionSection.querySelectorAll('.candidate-card');
    allCardsInPosition.forEach(card => {
        card.classList.remove('selected');
        const radio = card.querySelector('.candidate-radio');
        if (radio) {
            radio.checked = false;
        }
    });
    
    // Select the clicked candidate
    selectedCard.classList.add('selected');
    const selectedRadio = selectedCard.querySelector('.candidate-radio');
    if (selectedRadio) {
        selectedRadio.checked = true;
    }
    
    // Get candidate information for feedback
    const candidateName = selectedCard.querySelector('h6').textContent;
    console.log(`Selected: ${candidateName} for ${positionName}`);
    
    // Update the submit button state
    updateSubmitButton();
    
    // Show selection feedback
    showSelectionFeedback(candidateName, positionName);
}

/**
 * Update the submit button based on current selections
 */
function updateSubmitButton() {
    const submitButton = document.getElementById('submitBtn');
    if (!submitButton) return;
    
    // Count how many candidates are selected
    const selectedCandidates = document.querySelectorAll('.candidate-radio:checked');
    const selectedCount = selectedCandidates.length;
    
    if (selectedCount > 0) {
        // Enable the button and show count
        submitButton.disabled = false;
        submitButton.innerHTML = `
            <i class="fas fa-paper-plane me-2"></i>
            Submit My Votes (${selectedCount} selected)
        `;
        submitButton.classList.remove('btn-secondary');
        submitButton.classList.add('btn-success');
    } else {
        // Disable the button
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <i class="fas fa-paper-plane me-2"></i>
            Submit My Votes
        `;
        submitButton.classList.remove('btn-success');
        submitButton.classList.add('btn-secondary');
    }
}

/**
 * Show visual feedback when a candidate is selected
 * @param {string} candidateName - Name of selected candidate
 * @param {string} positionName - Name of the position
 */
function showSelectionFeedback(candidateName, positionName) {
    // Create a temporary notification
    const notification = document.createElement('div');
    notification.className = 'alert alert-success position-fixed';
    notification.style.cssText = `
        top: 100px; 
        right: 20px; 
        z-index: 9999; 
        min-width: 300px;
        animation: slideIn 0.3s ease-out;
    `;
    notification.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        Selected <strong>${candidateName}</strong> for ${positionName}
    `;
    
    document.body.appendChild(notification);
    
    // Remove the notification after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

/**
 * Setup voting form submission with validation
 * @param {Element} form - The voting form element
 */
function setupVotingForm(form) {
    form.addEventListener('submit', function(event) {
        console.log('Vote submission attempted');
        
        // Check if any candidates are selected
        const selectedCandidates = form.querySelectorAll('.candidate-radio:checked');
        
        if (selectedCandidates.length === 0) {
            event.preventDefault();
            showAlert('Please select at least one candidate before submitting your vote.', 'warning');
            return false;
        }
        
        // Show confirmation dialog
        const confirmMessage = `
            You are about to submit your votes for ${selectedCandidates.length} position(s).
            
            This action cannot be undone. Are you sure you want to proceed?
        `;
        
        if (!confirm(confirmMessage)) {
            event.preventDefault();
            return false;
        }
        
        // Show loading state
        showLoadingState();
        
        console.log(`Submitting ${selectedCandidates.length} votes`);
        
        // Let the form submit normally
        return true;
    });
}

/**
 * FORM VALIDATION FUNCTIONALITY
 * Handles validation for login and registration forms
 */
function initializeFormValidation() {
    // Setup login form validation
    const loginForm = document.querySelector('form[action*="login"]');
    if (loginForm) {
        setupLoginValidation(loginForm);
    }
    
    // Setup registration form validation
    const registerForm = document.querySelector('form[action*="register"]');
    if (registerForm) {
        setupRegistrationValidation(registerForm);
    }
}

/**
 * Setup login form validation
 * @param {Element} form - Login form element
 */
function setupLoginValidation(form) {
    form.addEventListener('submit', function(event) {
        const username = form.querySelector('input[name="username"]');
        const id = form.querySelector('input[name="ID"]');
        const password = form.querySelector('input[name="password"]');
        
        let isValid = true;
        let errorMessage = '';
        
        // Validate username
        if (!username || username.value.trim() === '') {
            isValid = false;
            errorMessage += 'Username is required.\n';
        }
        
        // Validate National ID
        if (!id || id.value.trim() === '') {
            isValid = false;
            errorMessage += 'National ID is required.\n';
        }
        
        // Validate password
        if (!password || password.value.trim() === '') {
            isValid = false;
            errorMessage += 'Password is required.\n';
        }
        
        if (!isValid) {
            event.preventDefault();
            showAlert(errorMessage, 'error');
            return false;
        }
        
        showLoadingState();
    });
}

/**
 * Setup registration form validation
 * @param {Element} form - Registration form element
 */
function setupRegistrationValidation(form) {
    form.addEventListener('submit', function(event) {
        const username = form.querySelector('input[name="username"]');
        const id = form.querySelector('input[name="ID"]');
        const email = form.querySelector('input[name="email"]');
        const password1 = form.querySelector('input[name="password_1"]');
        const password2 = form.querySelector('input[name="password_2"]');
        
        let isValid = true;
        let errorMessage = '';
        
        // Validate all required fields
        if (!username || username.value.trim() === '') {
            isValid = false;
            errorMessage += 'Username is required.\n';
        }
        
        if (!id || id.value.trim() === '') {
            isValid = false;
            errorMessage += 'National ID is required.\n';
        }
        
        if (!email || email.value.trim() === '') {
            isValid = false;
            errorMessage += 'Email is required.\n';
        } else if (!isValidEmail(email.value)) {
            isValid = false;
            errorMessage += 'Please enter a valid email address.\n';
        }
        
        if (!password1 || password1.value.trim() === '') {
            isValid = false;
            errorMessage += 'Password is required.\n';
        } else if (password1.value.length < 6) {
            isValid = false;
            errorMessage += 'Password must be at least 6 characters long.\n';
        }
        
        if (password1 && password2 && password1.value !== password2.value) {
            isValid = false;
            errorMessage += 'Passwords do not match.\n';
        }
        
        if (!isValid) {
            event.preventDefault();
            showAlert(errorMessage, 'error');
            return false;
        }
        
        showLoadingState();
    });
}

/**
 * UTILITY FUNCTIONS
 * Helper functions used throughout the application
 */

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} - True if email is valid
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Show alert messages to the user
 * @param {string} message - Message to display
 * @param {string} type - Type of alert (success, error, warning, info)
 */
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.custom-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type === 'error' ? 'danger' : type} custom-alert position-fixed`;
    alert.style.cssText = `
        top: 100px; 
        left: 50%; 
        transform: translateX(-50%); 
        z-index: 9999; 
        min-width: 400px;
        text-align: center;
        animation: slideDown 0.3s ease-out;
    `;
    
    // Set icon based on type
    let icon = 'fa-info-circle';
    if (type === 'success') icon = 'fa-check-circle';
    else if (type === 'error') icon = 'fa-exclamation-circle';
    else if (type === 'warning') icon = 'fa-exclamation-triangle';
    
    alert.innerHTML = `
        <i class="fas ${icon} me-2"></i>
        ${message.replace(/\n/g, '<br>')}
        <button type="button" class="btn-close float-end" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.style.animation = 'slideUp 0.3s ease-in';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 300);
        }
    }, 5000);
}

/**
 * Show loading state during form submissions
 */
function showLoadingState() {
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(button => {
        button.disabled = true;
        button.innerHTML = `
            <span class="spinner me-2"></span>
            Processing...
        `;
    });
}

/**
 * ANIMATIONS AND VISUAL EFFECTS
 * Initialize smooth animations and transitions
 */
function initializeAnimations() {
    // Add fade-in animation to main content
    const mainContent = document.querySelector('main');
    if (mainContent) {
        mainContent.classList.add('fade-in');
    }
    
    // Add smooth scroll behavior
    document.documentElement.style.scrollBehavior = 'smooth';
    
    // Initialize card hover effects
    initializeCardEffects();
}

/**
 * Initialize interactive card effects
 */
function initializeCardEffects() {
    const cards = document.querySelectorAll('.card, .dashboard-card');
    
    cards.forEach(card => {
        // Add subtle animation on hover
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
        
        // Add click ripple effect
        card.addEventListener('click', function(e) {
            createRippleEffect(e, this);
        });
    });
}

/**
 * Create ripple effect on click
 * @param {Event} event - Click event
 * @param {Element} element - Element that was clicked
 */
function createRippleEffect(event, element) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
    `;
    
    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

/**
 * DASHBOARD FUNCTIONALITY
 * Special functions for admin dashboard
 */

/**
 * Initialize admin dashboard features
 */
function initializeAdminDashboard() {
    const dashboardCards = document.querySelectorAll('.dash-main .card');
    
    dashboardCards.forEach(card => {
        const header = card.querySelector('h2');
        if (header) {
            header.addEventListener('click', function() {
                toggleDashboardPanel(card);
            });
        }
    });
}

/**
 * Toggle dashboard panel visibility
 * @param {Element} card - Dashboard card element
 */
function toggleDashboardPanel(card) {
    const panel = card.querySelector('.panel');
    if (panel) {
        panel.classList.toggle('open');
        
        // Rotate the icon if present
        const icon = card.querySelector('h2 i');
        if (icon) {
            icon.style.transform = panel.classList.contains('open') 
                ? 'rotate(180deg)' 
                : 'rotate(0deg)';
        }
    }
}

/**
 * ACCESSIBILITY FEATURES
 * Improve accessibility for all users
 */

// Add keyboard navigation support
document.addEventListener('keydown', function(event) {
    // Handle Enter key on candidate cards
    if (event.key === 'Enter' && event.target.classList.contains('candidate-card')) {
        event.target.click();
    }
    
    // Handle Escape key to close alerts
    if (event.key === 'Escape') {
        const alerts = document.querySelectorAll('.custom-alert');
        alerts.forEach(alert => alert.remove());
    }
});

// Add focus management for better keyboard navigation
document.addEventListener('DOMContentLoaded', function() {
    const focusableElements = document.querySelectorAll(
        'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    focusableElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.style.outline = '2px solid #2563eb';
            this.style.outlineOffset = '2px';
        });
        
        element.addEventListener('blur', function() {
            this.style.outline = '';
            this.style.outlineOffset = '';
        });
    });
});

/*
====================================================
    CUSTOM CSS ANIMATIONS FOR JAVASCRIPT
    These animations work with the JavaScript above
====================================================
*/

// Add custom animations via CSS
const customStyles = `
<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

@keyframes slideDown {
    from { transform: translate(-50%, -100%); opacity: 0; }
    to { transform: translate(-50%, 0); opacity: 1; }
}

@keyframes slideUp {
    from { transform: translate(-50%, 0); opacity: 1; }
    to { transform: translate(-50%, -100%); opacity: 0; }
}

@keyframes ripple {
    to { transform: scale(4); opacity: 0; }
}

.panel {
    transition: max-height 0.3s ease-in-out;
}

.panel.open {
    max-height: 200px;
}
</style>
`;

// Add the styles to the document head
if (document.head) {
    document.head.insertAdjacentHTML('beforeend', customStyles);
}

// Log successful initialization
console.log('E-Voting System JavaScript loaded successfully');

/*
====================================================
    END OF JAVASCRIPT FILE
    
    This file provides all the interactive functionality
    for the E-Voting system with clear, commented code
    that's easy to understand and modify.
====================================================
*/
