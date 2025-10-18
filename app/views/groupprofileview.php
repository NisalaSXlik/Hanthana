    <script>
    // ...existing modal logic and image preview...
    // Edit Group AJAX submit
    document.addEventListener('DOMContentLoaded', function() {
        const editGroupForm = document.getElementById('editGroupForm');
        if (!editGroupForm) return;
        editGroupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('action', 'edit');
            // Debug: log all form data
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            try {
                const response = await fetch('../../app/controllers/GroupController.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.message || 'Failed to update group.');
                }
            } catch (err) {
                alert('An error occurred.');
            }
        });
    });
    </script>
<?php
require_once __DIR__ . '/../models/GroupModel.php';
session_start();
$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
$group = null;
$isJoined = false;
if ($groupId) {
    $groupModel = new GroupModel();
    $group = $groupModel->getById($groupId);
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        // Check if user is a member of this group
        $joinedGroups = $groupModel->getGroupsJoinedBy($userId);
        foreach ($joinedGroups as $g) {
            if ($g['group_id'] == $groupId) {
                $isJoined = true;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $group ? htmlspecialchars($group['name']) : 'Group'; ?></title>
    <link rel="stylesheet" href="../../public/css/general.css">
    <link rel="stylesheet" href="../../public/css/groupprofileview.css">
    <link rel="stylesheet" href="../../public/css/navbar.css"> 
    <link rel="stylesheet" href="../../public/css/mediaquery.css">
    <link rel="stylesheet" href="../../public/css/calender.css">
    <link rel="stylesheet" href="../../public/css/post.css">
    <link rel="stylesheet" href="../../public/css/myfeed.css">
    <link rel="stylesheet" href="../../public/css/notificationpopup.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>
    <main>
        <div class="container">
            <!-- Left Sidebar -->
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
                    <a class="menu-item">
                        <i class="uil uil-home"></i>
                        <h3>My Feed</h3>
                    </a>
                    <a class="menu-item">
                        <i class="uil uil-compass"></i>
                        <h3>Discover</h3>
                    </a>
                    <a class="menu-item">
                        <i class="uil uil-calendar-alt"></i>
                        <h3>Events</h3>
                    </a>
                    <a class="menu-item">
                        <i class="uil uil-users-alt"></i>
                        <h3>Popular</h3>
                    </a>
                </div>

                <div class="joined-groups">
                    <h4>Related Groups</h4>
                    <div class="group-list">
                        <div class="group">
                            <div class="profile-picture">
                                <img src="../../public/images/gpvrelatedAC_dp.jpg">
                            </div>
                            <div class="group-info">
                                <h5>Anime Collectors</h5>
                                <p>12.5k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="profile-picture">
                                <img src="../../public/images/gpvrelatedME_dp.jpg">
                            </div>
                            <div class="group-info">
                                <h5>Manga Enthusiasts</h5>
                                <p>8.2k members</p>
                            </div>
                        </div>
                        <div class="group">
                            <div class="profile-picture">
                                <img src="../../public/images/gpvrelatedCS_dp.jpg">
                            </div>
                            <div class="group-info">
                                <h5>Cosplay Sri Lankaa</h5>
                                <p>5.7k members</p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-secondary">See All Groups</button>
                </div>
            </div>

            <div class="middle">

                <!-- Group Header -->
                <div class="profile-header">
                    <div class="profile-cover">
                        <img src="<?php
                            if ($group && !empty($group['cover_image'])) {
                                $coverPath = htmlspecialchars($group['cover_image']);
                                echo '../../public/' . ltrim($coverPath, '/');
                            } else {
                                echo '../../public/images/default_cover.jpg';
                            }
                        ?>" alt="Profile Cover">
                        <button class="edit-cover-btn">
                            <i class="uil uil-camera"></i> Edit Cover
                        </button>
                    </div>
                    <div class="profile-info">
                        <div class="profile-dp-container">
                            <div class="profile-dp">
                                <img src="<?php
                                    if ($group && !empty($group['display_picture'])) {
                                        $dpPath = htmlspecialchars($group['display_picture']);
                                        echo '../../public/' . ltrim($dpPath, '/');
                                    } else {
                                        echo '../../public/images/default_dp.jpg';
                                    }
                                ?>" alt="Profile DP">
                                <button class="edit-dp-btn">
                                    <i class="uil uil-camera"></i>
                                </button>
                            </div>
                        </div>
                        <div class="profile-details">
                            <p class="profile-name"><?php echo $group ? htmlspecialchars($group['name']) : 'Group Name'; ?></p>
                            <p class="profile-handle">@<?php echo $group ? htmlspecialchars($group['tag']) : 'grouptag'; ?></p>
                            <div class="profile-stats">
                                <div class="stat">
                                    <strong><?php echo $group ? htmlspecialchars($group['post_count']) : '0'; ?></strong>
                                    <span>Posts</span>
                                </div>
                                <div class="stat">
                                    <strong><?php echo $group ? htmlspecialchars($group['member_count']) : '0'; ?></strong>
                                    <span>Members</span>
                                </div>
                            </div>
                            <p class="profile-bio"><?php echo $group ? htmlspecialchars($group['description']) : 'No description provided.'; ?></p>
                            <div class="profile-actions">
                                <?php if ($isJoined): ?>
                                    <button class="btn btn-primary" disabled>Joined</button>
                                <?php else: ?>
                                    <button class="btn btn-primary">Join</button>
                                <?php endif; ?>
                                <button class="btn btn-secondary">Invite</button>
                                <button class="btn btn-icon" id="editGroupBtn">
                                    <i class="uil uil-ellipsis-h"></i>
                                </button>
        <!-- Edit Group Modal -->
        <div id="editGroupModal" class="modal-overlay" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Group</h3>
                    <button class="modal-close" id="closeEditGroupModal">
                        <i class="uil uil-times"></i>
                    </button>
                </div>
                <form id="editGroupForm" class="modal-body" enctype="multipart/form-data">
                    <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                    <div class="form-group">
                        <label for="editGroupName">Group Name</label>
                        <input type="text" id="editGroupName" name="name" maxlength="255" value="<?php echo htmlspecialchars($group['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="editGroupTag">Group Tag</label>
                        <input type="text" id="editGroupTag" name="tag" maxlength="50" value="<?php echo htmlspecialchars($group['tag'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="editGroupDescription">Description</label>
                        <textarea id="editGroupDescription" name="description" rows="3"><?php echo htmlspecialchars($group['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="editGroupFocus">Focus/Category</label>
                        <input type="text" id="editGroupFocus" name="focus" maxlength="100" value="<?php echo htmlspecialchars($group['focus'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="editGroupPrivacy">Privacy</label>
                        <select id="editGroupPrivacy" name="privacy_status">
                            <option value="public" <?php if (($group['privacy_status'] ?? '') === 'public') echo 'selected'; ?>>Public</option>
                            <option value="private" <?php if (($group['privacy_status'] ?? '') === 'private') echo 'selected'; ?>>Private</option>
                            <option value="secret" <?php if (($group['privacy_status'] ?? '') === 'secret') echo 'selected'; ?>>Secret</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editGroupRules">Group Rules</label>
                        <textarea id="editGroupRules" name="rules" rows="3"><?php echo htmlspecialchars($group['rules'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Cover Photo</label><br>
                        <label for="editGroupCover" class="image-upload-label"><i class="uil uil-image"></i> Choose Cover Photo</label>
                        <input type="file" id="editGroupCover" name="cover_image" accept="image/*">
                        <img id="coverPreview" class="image-preview" src="<?php
                            if (!empty($group['cover_image'])) {
                                $coverPath = htmlspecialchars($group['cover_image']);
                                echo '../../public/' . ltrim($coverPath, '/');
                            } else {
                                echo '';
                            }
                        ?>" alt="Cover Preview" <?php if (empty($group['cover_image'])) echo 'style="display:none;"'; ?> >
                    </div>
                    <div class="form-group">
                        <label>Display Picture</label><br>
                        <label for="editGroupDP" class="image-upload-label"><i class="uil uil-user"></i> Choose Display Picture</label>
                        <input type="file" id="editGroupDP" name="display_picture" accept="image/*">
                        <img id="dpPreview" class="image-preview" src="<?php
                            if (!empty($group['display_picture'])) {
                                $dpPath = htmlspecialchars($group['display_picture']);
                                echo '../../public/' . ltrim($dpPath, '/');
                            } else {
                                echo '';
                            }
                        ?>" alt="DP Preview" <?php if (empty($group['display_picture'])) echo 'style="display:none;"'; ?> >
                    </div>
    <script>
    // ...existing modal logic...
    // Image preview for cover and dp
    document.getElementById('editGroupCover').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('coverPreview');
        if (file) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                preview.src = evt.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.src = '';
            preview.style.display = 'none';
        }
    });
    document.getElementById('editGroupDP').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('dpPreview');
        if (file) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                preview.src = evt.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.src = '';
            preview.style.display = 'none';
        }
    });
    </script>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelEditGroupBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    <script>
    // Edit Group Modal logic
    const editBtn = document.getElementById('editGroupBtn');
    const editModal = document.getElementById('editGroupModal');
    const closeEditModal = document.getElementById('closeEditGroupModal');
    const cancelEditBtn = document.getElementById('cancelEditGroupBtn');
    if (editBtn && editModal) {
        editBtn.addEventListener('click', () => {
            editModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
        const close = () => {
            editModal.style.display = 'none';
            document.body.style.overflow = '';
        };
        if (closeEditModal) closeEditModal.addEventListener('click', close);
        if (cancelEditBtn) cancelEditBtn.addEventListener('click', close);
        editModal.addEventListener('click', (e) => { if (e.target === editModal) close(); });
    }
    </script>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <div class="profile-tabs">
                        <ul>
                            <li class="active">
                                <a href="#" data-tab="post">Posts</a>
                            </li>
                            <li>
                                <a href="#" data-tab="about">About</a>
                            </li>
                            <li>
                                <a href="#" data-tab="files">Files</a>
                            </li>
                            <li>
                                <a href="#" data-tab="events">Events</a>
                            </li>
                            <li>
                                <a href="#" data-tab="members">Members</a>
                            </li>
                            <li>
                                <a href="#" data-tab="photos">Photos</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Profile Content Area -->
                <div class="group-content">

                    <!-- Posts Tab Content -->
                    <div class="tab-content active" id="posts-content">
                        <!-- Post Creation -->
                        <div class="create-post">
                            <div class="post-input">
                                <img src="../../public/images/4.jpg" alt="Your profile">
                                <input type="text" placeholder="What's on your mind, Lithmal?">
                            </div>
                            <div class="post-options">
                                <button class="option">
                                    <i class="uil uil-image"></i>
                                    <span>Photo</span>
                                </button>
                                <button class="option">
                                    <i class="uil uil-video"></i>
                                    <span>Video</span>
                                </button>
                                <button class="option">
                                    <i class="uil uil-calendar-alt"></i>
                                    <span>Event</span>
                                </button>
                            </div>
                        </div>

                        <!-- FEED: Default Active Tab -->
                        <div class="posts-feed">
                            <div class="feed">
                                <div class="head">
                                    <div class="user">
                                        <div class="profile-picture">
                                            <img src="../../public/images/gpvpostTY_dp.jpg">
                                        </div>
                                        <div class="info">
                                            <h3>Tachi Yamamoto</h3>
                                            <small>Colombo, 15 mins ago</small>
                                        </div>
                                    </div>
                                    <i class="uil uil-ellipsis-h"></i>
                                </div>
                                <div class="photo">
                                    <img src="../../public/images/gpvpost_content1.jpg">
                                </div>
                                <div class="action-buttons">
                                    <div class="interaction-buttons">
                                        <i class="uil uil-heart"></i>
                                        <i class="uil uil-comment"></i>
                                        <i class="uil uil-share-alt"></i>
                                        <button class="add-to-calendar-btn"
                                            data-event='{"title":"Temple Visit","date":"2023-11-15T09:00:00","location":"Temple of the Tooth, Kandy","image":"../../public/images/2.jpg"}'>
                                            <i class="uil uil-calendar-alt"></i> Add to Calendar</button>
                                    </div>
                                    <i class="uil uil-bookmark"></i>
                                </div>
                                <div class="liked-by">
                                    <div class="liked-users">
                                        <img src="../../public/images/gpvpostNJ_dp.jpg">
                                        <img src="../../public/images/gpvpostfun_dp1.jpg">
                                        <img src="../../public/images/gpvpostfun_dp2.jpg">
                                    </div>
                                    <p>Liked by <b>Zanka</b> and <b>187 others</b></p>
                                </div>
                                <div class="caption">
                                    <p><b>Tachi Yamamoto</b> Yuji Itadori drip! Slapped a supreme drip on yuji😎! Inspiration for Supreme-level meme fits
                                        <br><p class="post-tags">#photoshop #itadori #dripvibes #funedit #animefit #justforfun</p></p>
                                </div>
                                <div class="comments">View all 42 comments</div>
                            </div>

                            <div class="feed">
                                <div class="head">
                                    <div class="user">
                                        <div class="profile-picture">
                                            <img src="../../public/images/gpvpostNJ_dp.jpg">
                                        </div>
                                        <div class="info">
                                            <h3>Nijou-Jou</h3>
                                            <small>Kandy, 1 hour ago</small>
                                        </div>
                                    </div>
                                    <i class="uil uil-ellipsis-h"></i>
                                </div>
                                <div class="photo">
                                    <img src="../../public/images/gpvpost_content2.jpg">
                                </div>
                                <div class="action-buttons">
                                    <div class="interaction-buttons">
                                        <i class="uil uil-heart"></i>
                                        <i class="uil uil-comment"></i>
                                        <i class="uil uil-share-alt"></i>
                                    </div>
                                    <i class="uil uil-bookmark"></i>
                                </div>
                                <div class="liked-by">
                                    <div class="liked-users">
                                        <img src="../../public/images/gpvpostTY_dp.jpg">
                                        <img src="../../public/images/gpvpostfun_dp3.jpg">
                                        <img src="../../public/images/gpvpostfun_dp4.jpg">
                                    </div>
                                    <p>Liked by <b>Tachi</b> and <b>243 others</b></p>
                                </div>
                                <div class="caption">
                                    <p><b>Nijou-Jou</b> Quick sketch practice ✏️
                                        <br><p class="post-tags">#drawing-wramup #schoolrumble</p></p>
                                </div>
                                <div class="comments">View all 56 comments</div>
                            </div>
                        </div>
                        <!-- More posts... -->
                    </div>
                </div>

                <!-- ABOUT TAB -->
                <div class="tab-content" id="about-content">
                    <h3>About This Group</h3>
                    <p><?php echo $group ? htmlspecialchars($group['description']) : 'No description provided.'; ?></p>
                    <ul>
                        <li><strong>Created:</strong> <?php echo $group && !empty($group['created_at']) ? date('M Y', strtotime($group['created_at'])) : 'Unknown'; ?></li>
                        <li><strong>Privacy:</strong> <?php echo $group ? ucfirst(htmlspecialchars($group['privacy_status'])) : 'Public'; ?></li>
                    </ul>
                </div>

                <!-- FILES TAB -->
                <div class="tab-content" id="files-content">
                    <h3>Shared Files</h3>
                    <ul>
                        <li><a href="#">Anime_Tutorial.pdf</a> <small>(uploaded by Lahiru F.)</small></li>
                        <li><a href="#">prize_list.docx</a> <small>(uploaded by Minthaka J.)</small></li>
                    </ul>
                </div>

                <!-- EVENTS TAB -->
                <div class="tab-content" id="events-content">
                    <h3>Upcoming Events</h3>
                    <div class="event-card">
                        <h4>Hayao Miyazaki - Conveying taste through anime art</h4>
                        <p><strong>Date:</strong> Aug 20, 2025</p>
                        <p><strong>Location:</strong>B.M.I.C.H</p>
                        <button class="btn btn-secondary">Interested</button>
                    </div>
                </div>

                <!-- MEMBERS TAB -->
                <div class="tab-content" id="members-content">
                    <h3>Members</h3>
                    <div class="member">
                        <div class="profile-picture"><img src="../../public/images/4.jpg"></div>
                        <h5>Lithmal Perera <small>(Admin)</small></h5>
                    </div>
                    <div class="member">
                        <div class="profile-picture"><img src="../../public/images/6.jpg"></div>
                        <h5>Minthaka Jayawardena</h5>
                    </div>
                    <!-- More members... -->
                </div>

                <!-- PHOTOS TAB -->
                <div class="tab-content" id="photos-content">
                    <div class="photo-grid">
                        <div class="photo-item">
                            <img src="../../public/images/gpvpost_content1.jpg" alt="Photo 1">
                        </div>
                        <div class="photo-item">
                            <img src="../../public/images/gpvpost_content2.jpg" alt="Photo 2">
                        </div>
                        <div class="photo-item">
                            <img src="../../public/images/gpvpost_content3.jpg" alt="Photo 3">
                        </div>
                        <div class="photo-item">
                            <img src="../../public/images/gpvpost_content4.jpg" alt="Photo 4">
                        </div>
                        <div class="photo-item">
                            <img src="../../public/images/gpvpost_content5.jpg" alt="Photo 5">
                        </div>
                        <div class="photo-item">
                            <img src="../../public/images/gpvpost_content6.jpg" alt="Photo 6">
                        </div>
                    </div>
                </div>
            </div>

            <div class="right">
                <!-- Group Details -->
                <div class="group-details">
                    <h4>Group Details</h4>
                    <div class="detail-list">
                        <div class="detail-item">
                            <i class="uil uil-user"></i>
                            <span><?php echo $group ? ucfirst(htmlspecialchars($group['privacy_status'])) . ' Group' : 'Group'; ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-compass"></i>
                            <span><?php echo $group && !empty($group['focus']) ? htmlspecialchars($group['focus']) : 'No focus'; ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-home"></i>
                            <span><?php echo $group && !empty($group['tag']) ? htmlspecialchars($group['tag']) : 'No tag'; ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-calendar-alt"></i>
                            <span>Created <?php echo $group && !empty($group['created_at']) ? date('F Y', strtotime($group['created_at'])) : 'Unknown'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Top Collaborators -->
                <div class="top-collaborators">
                    <div class="heading">
                        <h4>Top Collaborators</h4>
                        <a href="#" class="see-all">See all</a>
                    </div>
                    <div class="creator-list">
                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="../../public/images/gpvpostfun_dp1.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>Goth bunny</h5>
                                    <p class="creator-bio">12 mutual friends</p>
                                </div>
                            </div>
                            <button class="btn btn-primary">Add Friend</button>
                        </div>

                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="../../public/images/gpvpostfun_dp4.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>Naruuuuto</h5>
                                    <p class="creator-bio">8 mutual friends</p>
                                </div>
                            </div>
                            <button class="btn btn-primary">Add Friend</button>
                        </div>

                        <div class="creator-card">
                            <div class="creator-info">
                                <img src="../../public/images/gpvpostfun_dp3.jpg" class="creator-avatar">
                                <div class="creator-details">
                                    <h5>Ozamu Dazai</h5>
                                    <p class="creator-bio">15 mutual friends</p>
                                </div>
                            </div>
                            <button class="btn btn-primary">Add Friend</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <div class="calendar-popup" id="calendarPopup">
        <div class="calendar-popup-header">
            <h4>Events</h4>
            <span id="popup-date">--</span>
        </div>
        <div class="calendar-popup-body" id="calendarEvents">
            <div class="no-events">
                <i class="uil uil-calendar-slash"></i>
                <p>No events scheduled</p>
            </div>
        </div>
    </div>

    <script src="../../public/js/calender.js"></script>
    <script src="../../public/js/feed.js"></script>
    <!--- Add a friend type js for four side panels -->
    <script src="../../public/js/genral.js"></script>
    <script src="../../public/js/post.js"></script>
    <script src="../../public/js/notificationpopup.js"></script>
    <script src="../../public/js/navbar.js"></script>
    <script src="myFeed.js"></script>
</body>

</html>