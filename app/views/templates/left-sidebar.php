<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Popup with Media</title>
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <style>

    </style>
</head>
<body>
<div class="left">
                <div class="profile">
                    <div class="profile-picture">
                            <img src="../../public/images/4.jpg">
                    </div>
                    <div class="handle">
                        <h4>Lithmal Perera</h4>
                        <p>@lithmal</p>
                    </div>
                </div>

                <div class="side-bar">
                    <a class="menu-item active" data-target="feed">
                        <i class="uil uil-home"></i>
                        <h3>My Feed</h3>
                    </a>
                    <a href="discover.php" class="menu-item" data-target="discover">
                        <i class="uil uil-compass"></i>
                        <h3>Discover</h3>
                    </a>
                    <a class="menu-item" data-target="events">
                        <i class="uil uil-calendar-alt"></i>
                        <h3>Events</h3>
                    </a>
                    <a class="menu-item" data-target="groups">
                        <i class="uil uil-users-alt"></i>
                        <h3>popular</h3>
                    </a>
                </div>

                <div class="joined-groups">
                    <div class="joined-groups-header">
                        <h4>Groups</h4>
                        <button class="btn-add-group" title="Create Group">
                            <i class="uil uil-plus"></i>
                        </button>
                    </div>
                    <div class="group-list">
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-users-alt"></i>
                            </div>
                            <div class="group-info">
                                <h5>Colombo Foodies</h5>
                                <p>12.5k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-camera"></i>
                            </div>
                            <div class="group-info">
                                <h5>SL Photography Club</h5>
                                <p>8.2k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="group-icon">
                                <i class="uil uil-mountains"></i>
                            </div>
                            <div class="group-info">
                                <h5>Hiking Sri Lanka</h5>
                                <p>5.7k members</p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-secondary">See All Groups</button>
                </div>
            </div>

            <!-- Create Group Modal -->
            <div id="createGroupModal" class="modal-overlay">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Create New Group</h3>
                        <button class="modal-close" id="closeGroupModal">
                            <i class="uil uil-times"></i>
                        </button>
                    </div>
                    <form id="createGroupForm" class="modal-body">
                        <div class="form-group">
                            <label for="groupName">Group Name <span class="required">*</span></label>
                            <input type="text" id="groupName" name="name" required maxlength="255" placeholder="Enter group name">
                        </div>

                        <div class="form-group">
                            <label for="groupTag">Group Tag (Optional)</label>
                            <input type="text" id="groupTag" name="tag" maxlength="50" placeholder="@unique-tag">
                            <small>Must be unique (e.g., @colombo-foodies)</small>
                        </div>

                        <div class="form-group">
                            <label for="groupDescription">Description</label>
                            <textarea id="groupDescription" name="description" rows="3" placeholder="Describe what your group is about..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="groupFocus">Focus/Category</label>
                            <input type="text" id="groupFocus" name="focus" maxlength="100" placeholder="e.g., Photography, Food, Travel">
                        </div>

                        <div class="form-group">
                            <label for="groupPrivacy">Privacy <span class="required">*</span></label>
                            <select id="groupPrivacy" name="privacy_status" required>
                                <option value="public">Public - Anyone can see and join</option>
                                <option value="private">Private - Anyone can see, must request to join</option>
                                <option value="secret">Secret - Only members can see</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="groupRules">Group Rules (Optional)</label>
                            <textarea id="groupRules" name="rules" rows="3" placeholder="Set guidelines for members..."></textarea>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" id="cancelGroupBtn">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Group</button>
                        </div>
                    </form>
                </div>
            </div>
        </body>
    </html>

