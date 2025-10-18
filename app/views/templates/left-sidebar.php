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

                <?php
                require_once __DIR__ . '/../../models/GroupModel.php';
                $groupModel = new GroupModel();
                $userId = $_SESSION['user_id'] ?? null;
                $createdGroups = $userId ? $groupModel->getGroupsCreatedBy($userId) : [];
                $joinedGroups = $userId ? $groupModel->getGroupsJoinedBy($userId) : [];
                ?>
                <div class="joined-groups">
                    <div class="joined-groups-header">
                        <h4>Groups</h4>
                        <button class="btn-add-group" title="Create Group">
                            <i class="uil uil-plus"></i>
                        </button>
                    </div>
                    <div class="group-list">
                        <?php if (!empty($createdGroups)) : ?>
                            <div class="sidebar-subsection">
                                <strong style="font-size:13px;">Created by you</strong>
                                <?php foreach ($createdGroups as $group): ?>
                                    <div class="group">
                                        <div class="group-icon">
                                            <i class="uil uil-users-alt"></i>
                                        </div>
                                        <div class="group-info">
                                            <h5><a href="../views/groupprofileview.php?group_id=<?php echo $group['group_id']; ?>"><?php echo htmlspecialchars($group['name']); ?></a></h5>
                                            <p><?php echo htmlspecialchars($group['member_count'] ?? ''); ?> members</p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($joinedGroups)) : ?>
                            <div class="sidebar-subsection">
                                <strong style="font-size:13px;">Joined Groups</strong>
                                <?php foreach ($joinedGroups as $group): ?>
                                    <?php if ($group['created_by'] != $userId): // Avoid duplicate ?>
                                    <div class="group">
                                        <div class="group-icon">
                                            <i class="uil uil-users-alt"></i>
                                        </div>
                                        <div class="group-info">
                                            <h5><a href="../views/groupprofileview.php?group_id=<?php echo $group['group_id']; ?>"><?php echo htmlspecialchars($group['name']); ?></a></h5>
                                            <p><?php echo htmlspecialchars($group['member_count'] ?? ''); ?> members</p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (empty($createdGroups) && empty($joinedGroups)) : ?>
                            <p style="padding:10px 0 0 10px;">You haven't joined or created any groups yet.</p>
                        <?php endif; ?>
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
                        <div id="groupErrorMsg" style="display:none;color:#d32f2f;font-weight:bold;margin-bottom:10px;"></div>
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

