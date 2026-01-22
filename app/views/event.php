<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hanthana Events</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/general.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/navbar.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/event-feed.css">
    <link rel="stylesheet" href="/NewHanthaneAfterProperMVC/public/css/event-modal.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-left">
                <div class="logo">
                    <a href="index.html">Hanthana</a>
                </div>
            </div>
            
            <div class="nav-center">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="search" placeholder="Search events...">
                </div>
            </div>
            
            <div class="nav-right">
                <div class="calendar-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <div class="notification-count">3</div>
                </div>
                
                <div class="profile-picture">
                    <img src="https://via.placeholder.com/40" alt="Profile">
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="container">
            <!-- Left Sidebar -->
            <div class="left">
                <div class="profile">
                    <div class="profile-picture">
                        <img src="https://via.placeholder.com/40" alt="Profile">
                    </div>
                    <div class="handle">
                        <h4>John Doe</h4>
                        <p>Computer Science Student</p>
                    </div>
                </div>

                <div class="side-bar">
                    <a href="#" class="menu-item active">
                        <i class="fas fa-calendar"></i>
                        <h3>All Events</h3>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fas fa-user"></i>
                        <h3>My Events</h3>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fas fa-bookmark"></i>
                        <h3>Bookmarked</h3>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fas fa-clock"></i>
                        <h3>Past Events</h3>
                    </a>
                </div>

                <button class="btn btn-primary" id="createEventBtn">
                    <i class="fas fa-plus"></i> Create Event
                </button>

                <!-- Categories -->
                <div class="joined-groups">
                    <h4>Categories</h4>
                    <div class="group-list">
                        <div class="group category-btn active" data-category="all">
                            <div class="group-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="group-info">
                                <h5>All Events</h5>
                            </div>
                        </div>
                        <div class="group category-btn" data-category="academic">
                            <div class="group-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="group-info">
                                <h5>Academic</h5>
                            </div>
                        </div>
                        <div class="group category-btn" data-category="workshop">
                            <div class="group-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div class="group-info">
                                <h5>Workshops</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle - Events Feed -->
            <div class="middle">
                <div class="events-header">
                    <h1>Upcoming Events</h1>
                    <div class="filter-controls">
                        <button class="btn btn-secondary">
                            <i class="fas fa-filter"></i>
                            <span>Filter</span>
                        </button>
                    </div>
                </div>

                <div class="events-feed" id="eventsFeed">
                    <!-- Events will be loaded here -->
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="right">
                <div class="trending-card">
                    <h3>
                        <i class="fas fa-trending-up"></i>
                        Trending This Week
                    </h3>
                    <div class="trending-list" id="trendingEvents">
                        <!-- Trending events will be loaded here -->
                    </div>
                </div>

                <div class="suggested-card">
                    <h3>
                        <i class="fas fa-star"></i>
                        Suggested for You
                    </h3>
                    <div class="suggested-list" id="suggestedEvents">
                        <!-- Suggested events will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Event Creation Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Event</h3>
                <button class="close-modal" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <div class="form-group">
                        <label for="eventTitle">Event Title</label>
                        <input type="text" id="eventTitle" required>
                    </div>
                    <div class="form-group">
                        <label for="eventDescription">Description</label>
                        <textarea id="eventDescription" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="eventDate">Date</label>
                        <input type="date" id="eventDate" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Create Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="/NewHanthaneAfterProperMVC/public/js/navbar.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/events.js"></script>
    <script src="/NewHanthaneAfterProperMVC/public/js/event-modal.js"></script>
</body>
</html>