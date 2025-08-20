//Handle login/signup button clicks
document.querySelector('.btn-login').addEventListener('click', () => {
    window.location.href = 'login.html';
});

document.querySelector('.btn-primary').addEventListener('click', () => {
    window.location.href = 'register.html';
});

function protectedContentLoad() {
    console.log("HEllo");
    // Get modal element
    const signupModal = document.getElementById('signupModal');
    const closeModal = document.getElementById('closeModal');
    const loginBtn = document.getElementById('loginBtn');
    const signupBtn = document.getElementById('signupBtn');



    
    // Function to show modal
    function showModal(e) {
        e.preventDefault();
        e.stopPropagation();
        signupModal.style.display = 'flex'; // Change display to flex
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
    
    // Function to hide modal
    function hideModal() {
        signupModal.style.display = 'none'; // Change display to none
        document.body.style.overflow = ''; // Re-enable scrolling
    }
    
    // Elements that should trigger the modal
    const protectedElements = [
        // Navbar buttons
        document.querySelector('.nav-right .btn-primary'), // Create button
        document.querySelector('.calendar-icon'), // Calendar
        document.querySelector('.notification'), // Notification
        
        // Post interaction buttons (need to handle multiple elements)
        ...document.querySelectorAll('.discover-item .uil-heart'), // Like buttons
        ...document.querySelectorAll('.discover-item .uil-comment'), // Comment buttons
        
        // Follow buttons
        ...document.querySelectorAll('.follow-btn')
    ];
    
    // Add click handlers to all protected elements

    console.log(protectedElements);
    protectedElements.forEach(element => {
        if (element) {
            // For single elements
            if (element.addEventListener) {
                element.addEventListener('click', showModal);
            } 
            // For NodeLists (querySelectorAll results)
            else if (element.length) {
                element.forEach(el => el.addEventListener('click', showModal));
            }
        }
    });
    
    // Close modal handlers
    if (closeModal) {
        closeModal.addEventListener('click', hideModal);
    }
    
    // Close when clicking outside modal content
    signupModal.addEventListener('click', function(e) {
        if (e.target === signupModal) {
            hideModal();
        }
    });
    
    // Close with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && signupModal.style.display === 'flex') {
            hideModal();
        }
    });
};