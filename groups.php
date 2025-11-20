<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#667eea">
    <title>My Groups - Order Book</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style2.css">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="assets/images/icon-192.png">
    <style>
        .groups-container {
            background: white;
            padding: 25px;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            justify-content: right;
        }
        
        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .group-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .group-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .group-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .group-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .group-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .group-description {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
            line-height: 1.5;
        }
        
        .group-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 15px 0;
            font-size: 14px;
            color: #666;
        }
        
        .group-meta i {
            color: #667eea;
        }
        
        .pending-badge {
            color: #ff9800;
            font-weight: 600;
        }
        
        .group-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .group-actions .btn {
            flex: auto;
            min-width: 100px;
            font-size: 13px;
            padding: 8px 12px;
        }
        
        .no-groups {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-groups i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .members-list {
            max-height: 400px;
            overflow-y: auto;
            margin: 20px 0;
        }
        
        .member-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .member-item:hover {
            background: #e9ecef;
        }
        
        .member-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .member-info strong {
            color: #333;
            font-size: 16px;
        }
        
        .member-role {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        #groupDetailsContent h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        #groupDetailsContent p {
            color: #666;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        #groupDetailsContent strong {
            color: #333;
            font-weight: 600;
        }
        
        .request-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #fff3cd;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .request-actions {
            display: flex;
            gap: 8px;
        }
        
        .user-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin: 12px 0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            gap: 15px;
        }
        
        .user-list-item:hover {
            background: #f8f9fa;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .user-list-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        
        .user-list-info strong {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-list-info span {
            color: #666;
            font-size: 14px;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .invite-btn {
            padding: 10px 18px;
            font-size: 13px;
            white-space: nowrap;
            flex-shrink: 0;
            min-width: auto;
            width: auto !important;
        }
        
        .user-list-item .btn-primary {
            width: auto !important;
            flex: 0 0 auto;
        }
        
        #inviteUsersList {
            padding-right: 5px;
        }
        
        #inviteUsersList::-webkit-scrollbar {
            display: none;
        }
        
        #inviteUsersList::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        #inviteUsersList::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }
        
        #inviteUsersList::-webkit-scrollbar-thumb:hover {
            background: #5568d3;
        }
        
        .request-count-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #6f41a1;
            color: white!important;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        @media (min-width: 925px) and (max-width: 1096px) {
            header {
                padding: 20px;
            }
            
            .header-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 20px;
                flex-direction: row;
            }
            
            .logo {
                justify-content: flex-start;
            }
            
            header h1 {
                font-size: 24px;
                text-align: left;
            }
            
            .user-info {
                width: auto;
                flex-direction: row;
                gap: 12px;
                flex: 1;
                justify-content: flex-end;
            }
            
            .button-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                width: auto;
            }
            
            .button-row .btn {
                padding: 10px 14px;
                font-size: 13px;
                min-width: auto;
            }
            
            .button-row .btn i {
                font-size: 13px;
            }
        }

        @media (min-width: 769px) and (max-width: 924px) {
            .button-row .btn {
                width: 45%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .user-info {
                width: 100%;
                flex-direction: column;
                gap: 10px;
            }
        }

        @media (max-width: 768px) {
            .groups-container {
                padding: 20px 15px;
            }
        }
        @media (max-width: 375px) {
            .groups-grid {
                grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <h1><i class="fas fa-users"></i> My Groups</h1>
                <div class="user-info">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong></span>
                    <div class="button-row">
                        <button id="createGroupBtn" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Group
                        </button>
                        <button id="myRequestsBtn" class="btn btn-secondary" style="position: relative;">
                            <i class="fas fa-bell"></i> My Invitations
                            <span id="requestCountBadge" class="request-count-badge" style="display: none;">0</span>
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <button id="logoutBtn" class="btn btn-secondary">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </div>
            </div>
        </header>
        
        <main>
            <div class="groups-container">
                <div id="groupsGrid" class="groups-grid">
                    <!-- Groups will be loaded here -->
                </div>
                
                <div id="noGroups" class="no-groups" style="display: none;">
                    <i class="fas fa-users"></i>
                    <h3>No Groups Yet</h3>
                    <p>Create your first group or join an existing one to get started!</p>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Create Group Modal -->
    <div id="createGroupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Create New Group</h2>
                <span class="close">&times;</span>
            </div>
            <form id="createGroupForm">
                <div class="form-group">
                    <label for="groupName">Group Name *</label>
                    <input type="text" id="groupName" name="name" required placeholder="Enter group name">
                </div>
                
                <div class="form-group">
                    <label for="groupDescription">Description</label>
                    <textarea id="groupDescription" name="description" rows="4" placeholder="Enter group description (optional)"></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelCreateBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Group</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Group Details Modal -->
    <div id="viewGroupModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Group Details</h2>
                <span class="close">&times;</span>
            </div>
            <div id="groupDetailsContent" style="padding: 25px;"></div>
        </div>
    </div>
    
    <!-- Invite User Modal -->
    <div id="inviteUserModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Invite User</h2>
                <span class="close">&times;</span>
            </div>
            <div style="padding: 20px; min-height: 200px;">
                <input type="hidden" id="inviteGroupId">
                <div id="inviteUsersList" style="max-height: 400px; overflow-y: auto; margin-bottom: 20px;">
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i>
                        <p>Loading users...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" id="cancelInviteBtn">Cancel</button>
            </div>
        </div>
    </div>
    
    <!-- Browse Available Groups Modal -->
    <div id="availableGroupsModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-search"></i> Available Groups</h2>
                <span class="close">&times;</span>
            </div>
            <div id="availableGroupsContent" class="groups-grid" style="padding: 20px;">
                <!-- Available groups will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- My Requests Modal -->
    <div id="myRequestsModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-bell"></i> My Group Invitations</h2>
                <span class="close">&times;</span>
            </div>
            <div style="padding: 20px;">
                <div id="myRequestsList" style="max-height: 500px; overflow-y: auto;">
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i>
                        <p>Loading invitations...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 style="color: #c62828;"><i class="fas fa-exclamation-triangle"></i> Delete Group</h2>
                <span class="close">&times;</span>
            </div>
            <div style="padding: 20px; text-align: center;">
                <div style="font-size: 64px; color: #ef5350; margin-bottom: 10px;">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h3 style="color: #333; margin-bottom: 10px; font-size: 20px;">Are you sure?</h3>
                <p style="color: #666; margin-bottom: 10px; line-height: 1.6;">
                    You are about to delete the group <strong id="deleteGroupName" style="color: #333;"></strong>
                </p>
                <p style="color: #f44336; font-weight: 600; margin-top: 15px;">
                    <i class="fas fa-exclamation-circle"></i> This action cannot be undone!
                </p>
                <input type="hidden" id="deleteGroupId">
            </div>
            <div class="modal-footer" style="justify-content: center; gap: 15px; padding: 20px 25px;">
                <button type="button" class="btn btn-secondary" id="cancelDeleteBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash-alt"></i> Delete Group
                </button>
            </div>
        </div>
    </div>
    
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 style="color: #c62828;"><i class="fas fa-sign-out-alt"></i> Confirm Logout</h2>
                <span class="close" id="closeLogoutModal">&times;</span>
            </div>
            <div style="padding: 20px; text-align: center;">
                <div style="font-size: 64px; color: #667eea; margin-bottom: 15px;">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3 style="color: #333; margin-bottom: 15px; font-size: 20px;">Are you sure you want to logout?</h3>
                <p style="color: #666; margin-bottom: 10px; line-height: 1.6;">
                    You will be redirected to the login page.
                </p>
            </div>
            <div class="modal-footer" style="justify-content: center; gap: 15px; padding: 20px 25px;">
                <button type="button" class="btn btn-secondary" id="cancelLogoutBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmLogoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/groups2.js"></script>
    <script>
        // Logout Modal Elements
        const logoutModal = document.getElementById('logoutModal');
        const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
        const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
        const closeLogoutModal = document.getElementById('closeLogoutModal');
        const logoutBtn = document.getElementById('logoutBtn');
        
        // Open Logout Confirmation Modal
        logoutBtn?.addEventListener('click', () => {
            logoutModal.classList.add('show');
        });
        
        // Close Logout Modal
        function closeLogoutModalFunc() {
            logoutModal.classList.remove('show');
        }
        
        closeLogoutModal?.addEventListener('click', closeLogoutModalFunc);
        cancelLogoutBtn?.addEventListener('click', closeLogoutModalFunc);
        
        // Confirm Logout
        confirmLogoutBtn?.addEventListener('click', async () => {
            try {
                const response = await fetch('api/auth.php?action=logout', {
                    method: 'POST'
                });
                const data = await response.json();
                if (data.success) {
                    window.location.href = 'login.php';
                } else {
                    // Force logout even if API fails
                    window.location.href = 'login.php';
                }
            } catch (error) {
                // Force logout on error
                window.location.href = 'login.php';
            }
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === logoutModal) {
                closeLogoutModalFunc();
            }
        });
    </script>
</body>
</html>

