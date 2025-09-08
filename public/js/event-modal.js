document.addEventListener('DOMContentLoaded', function() {
    const createEventBtn = document.getElementById('createEventBtn');
    const eventModal = document.getElementById('eventModal');
    const closeModal = document.getElementById('closeModal');
    const eventForm = document.getElementById('eventForm');

    // Show modal when Create Event button is clicked
    createEventBtn.addEventListener('click', function() {
        eventModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    // Hide modal when close button is clicked
    closeModal.addEventListener('click', function() {
        eventModal.classList.remove('active');
        document.body.style.overflow = '';
    });

    // Hide modal when clicking outside the modal
    eventModal.addEventListener('click', function(e) {
        if (e.target === eventModal) {
            eventModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Form submission
    eventForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form values
        const title = document.getElementById('eventTitle').value;
        const description = document.getElementById('eventDescription').value;
        const date = document.getElementById('eventDate').value;
        
        // In a real app, you would send this data to a server
        console.log('New event:', { title, description, date });
        
        // Show success message
        alert('Event created successfully!');
        
        // Close modal and reset form
        eventModal.classList.remove('active');
        document.body.style.overflow = '';
        eventForm.reset();
    });
});