// navbar.js - Handles navbar functionality
document.addEventListener('DOMContentLoaded', function() {
    // Profile dropdown
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileDropdown) {
        const dropdownMenu = profileDropdown.querySelector('.profile-dropdown');
        
        profileDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function() {
            dropdownMenu.classList.remove('show');
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                dropdownMenu.classList.remove('show');
            }
        });
    }
    
    
    // Menu navigation
    const menuItems = document.querySelectorAll('.menu-item');
    const contentSections = document.querySelectorAll('.content-section');
    
    menuItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const target = item.getAttribute('data-target');
            
            contentSections.forEach(section => {
                section.classList.remove('active');
            });
            
            document.getElementById(target).classList.add('active');
            
            menuItems.forEach(menuItem => {
                menuItem.classList.remove('active');
            });
            item.classList.add('active');
        });
    });
});

