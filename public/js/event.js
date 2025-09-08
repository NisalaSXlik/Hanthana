document.addEventListener('DOMContentLoaded', function() {
    // Sample events data
    const events = [
        {
            title: "AI Workshop",
            description: "Learn about artificial intelligence and machine learning",
            date: "2024-04-15",
            category: "workshop",
            image: "https://via.placeholder.com/400x200"
        },
        {
            title: "Career Fair",
            description: "Meet with top companies hiring students",
            date: "2024-04-20",
            category: "career",
            image: "https://via.placeholder.com/400x200"
        }
    ];

    // Load events into the feed
    const eventsFeed = document.getElementById('eventsFeed');
    
    events.forEach(event => {
        const eventCard = document.createElement('div');
        eventCard.className = 'event-card';
        eventCard.innerHTML = `
            <div class="event-image">
                <img src="${event.image}" alt="${event.title}">
            </div>
            <div class="event-content">
                <div class="event-date">
                    <i class="fas fa-calendar"></i>
                    ${new Date(event.date).toLocaleDateString()}
                </div>
                <h3 class="event-title">${event.title}</h3>
                <p class="event-description">${event.description}</p>
                <div class="event-meta">
                    <span class="event-category">${event.category}</span>
                    <span><i class="fas fa-users"></i> 50+</span>
                </div>
            </div>
        `;
        eventsFeed.appendChild(eventCard);
    });

    // Category filtering
    const categoryBtns = document.querySelectorAll('.category-btn');
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            categoryBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const category = this.dataset.category;
            filterEvents(category);
        });
    });

    function filterEvents(category) {
        const eventCards = document.querySelectorAll('.event-card');
        eventCards.forEach(card => {
            if (category === 'all') {
                card.style.display = 'block';
            } else {
                const cardCategory = card.querySelector('.event-category').textContent.toLowerCase();
                card.style.display = cardCategory.includes(category) ? 'block' : 'none';
            }
        });
    }
});