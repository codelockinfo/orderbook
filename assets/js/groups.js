// Groups JavaScript
const API_BASE = 'api/';

let groups = [];
let currentGroupId = null;

// DOM Elements
const groupsGrid = document.getElementById('groupsGrid');
const noGroups = document.getElementById('noGroups');
const createGroupBtn = document.getElementById('createGroupBtn');
const viewAvailableBtn = document.getElementById('viewAvailableBtn');
const myRequestsBtn = document.getElementById('myRequestsBtn');
const createGroupModal = document.getElementById('createGroupModal');
const viewGroupModal = document.getElementById('viewGroupModal');
const inviteUserModal = document.getElementById('inviteUserModal');
const availableGroupsModal = document.getElementById('availableGroupsModal');
const myRequestsModal = document.getElementById('myRequestsModal');
const deleteConfirmModal = document.getElementById('deleteConfirmModal');
const myRequestsList = document.getElementById('myRequestsList');
const requestCountBadge = document.getElementById('requestCountBadge');
const createGroupForm = document.getElementById('createGroupForm');
const groupDetailsContent = document.getElementById('groupDetailsContent');
const availableGroupsContent = document.getElementById('availableGroupsContent');
const deleteGroupId = document.getElementById('deleteGroupId');
const deleteGroupName = document.getElementById('deleteGroupName');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

// Close buttons
const closeButtons = document.querySelectorAll('.close');

// Load groups on page load
document.addEventListener('DOMContentLoaded', function() {
    loadGroups();
    loadRequestCount();
    
    // Modal event listeners
    createGroupBtn?.addEventListener('click', () => {
        createGroupModal.style.display = 'flex';
        createGroupForm.reset();
    });
    
    viewAvailableBtn?.addEventListener('click', () => {
        loadAvailableGroups();
        availableGroupsModal.style.display = 'flex';
    });
    
    myRequestsBtn?.addEventListener('click', () => {
        loadMyRequests();
        myRequestsModal.style.display = 'flex';
    });
    
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });
    
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
    
    // Form submissions
    createGroupForm?.addEventListener('submit', handleCreateGroup);
    
    // Delete confirmation modal handlers
    confirmDeleteBtn?.addEventListener('click', handleConfirmDelete);
    cancelDeleteBtn?.addEventListener('click', () => {
        deleteConfirmModal.style.display = 'none';
    });
});

// Load groups
async function loadGroups() {
    try {
        const response = await fetch(`${API_BASE}groups.php?action=list`);
        const data = await response.json();
        
        if (data.success) {
            groups = data.groups;
            renderGroups();
        } else {
            showAlert('Error loading groups: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading groups:', error);
        showAlert('Failed to load groups', 'error');
    }
}

// Render groups
function renderGroups() {
    groupsGrid.innerHTML = '';
    
    if (groups.length === 0) {
        noGroups.style.display = 'block';
        groupsGrid.style.display = 'none';
        return;
    }
    
    noGroups.style.display = 'none';
    groupsGrid.style.display = 'grid';
    
    groups.forEach(group => {
        const card = document.createElement('div');
        card.className = 'group-card';
        
        const pendingText = group.pending_count > 0 ? ` • <span class="pending-badge">${group.pending_count} pending</span>` : '';
        
        card.innerHTML = `
            <div class="group-card-header">
                <h3 class="group-card-title">
                    <i class="fas fa-users"></i>
                    ${escapeHtml(group.name)}
                </h3>
                ${group.user_role === 'ADMIN' ? '<span class="group-badge">ADMIN</span>' : ''}
            </div>
            ${group.description ? `<div class="group-description">${escapeHtml(group.description)}</div>` : ''}
            <div class="group-meta">
                <span><i class="fas fa-users"></i> ${group.member_count || 0} members${pendingText}</span>
            </div>
            <div class="group-actions">
                ${group.user_role === 'ADMIN' ? `
                    <button class="btn btn-primary btn-sm" onclick="openInviteModal(${group.id})">
                        <i class="fas fa-user-plus"></i> Invite Users
                    </button>
                ` : ''}
                <button class="btn btn-secondary btn-sm" onclick="viewGroupDetails(${group.id})">
                    <i class="fas fa-eye"></i> View Details
                </button>
                ${group.user_role === 'ADMIN' ? `
                    <button class="btn btn-danger btn-sm" onclick="deleteGroup(${group.id}, '${escapeHtml(group.name)}')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                ` : ''}
            </div>
        `;
        
        groupsGrid.appendChild(card);
    });
}

// Create group
async function handleCreateGroup(e) {
    e.preventDefault();
    
    const formData = {
        name: document.getElementById('groupName').value.trim(),
        description: document.getElementById('groupDescription').value.trim()
    };
    
    if (!formData.name) {
        showAlert('Group name is required', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}groups.php?action=create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Group created successfully!', 'success');
            createGroupModal.style.display = 'none';
            createGroupForm.reset();
            loadGroups();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error creating group:', error);
        showAlert('Failed to create group', 'error');
    }
}

// View group details
async function viewGroupDetails(groupId) {
    try {
        const response = await fetch(`${API_BASE}groups.php?action=get&id=${groupId}`);
        const data = await response.json();
        
        if (data.success) {
            const group = data.group;
            currentGroupId = groupId;
            
            let membersHtml = '';
            if (group.members && group.members.length > 0) {
                membersHtml = '<h3 style="font-size: 18px; color: #333; margin-bottom: 15px; font-weight: 600;">Members</h3><div class="members-list">';
                group.members.forEach(member => {
                    membersHtml += `
                        <div class="member-item">
                            <div class="member-info">
                                <strong>${escapeHtml(member.username)}</strong>
                                ${member.status === 'pending' ? '<span style="color: #ff9800; font-weight: 600; font-size: 12px; margin-left: 10px;">Pending</span>' : ''}
                            </div>
                            <span class="member-role">${member.role}</span>
                        </div>
                    `;
                });
                membersHtml += '</div>';
            } else {
                membersHtml = '<p style="color: #999; text-align: center; padding: 20px;">No members yet.</p>';
            }
            
            let requestsHtml = '';
            if (group.user_role === 'ADMIN' && group.pending_requests && group.pending_requests.length > 0) {
                requestsHtml = '<h3 style="font-size: 18px; color: #333; margin-top: 30px; margin-bottom: 15px; font-weight: 600;">Pending Join Requests</h3><div class="members-list">';
                group.pending_requests.forEach(request => {
                    requestsHtml += `
                        <div class="request-item">
                            <div class="member-info">
                                <div>
                                    <strong style="display: block; color: #333; margin-bottom: 4px;">${escapeHtml(request.username)}</strong>
                                    <span style="color: #666; font-size: 14px;">${escapeHtml(request.email)}</span>
                                </div>
                            </div>
                            <div class="request-actions">
                                <button class="btn btn-success btn-sm" onclick="respondToRequest(${request.id}, 'accepted')">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="respondToRequest(${request.id}, 'rejected')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </div>
                    `;
                });
                requestsHtml += '</div>';
            }
            
            groupDetailsContent.innerHTML = `
                <div>
                    <h3 style="font-size: 24px; color: #333; margin-bottom: 15px; font-weight: 700;">${escapeHtml(group.name)}</h3>
                    ${group.description ? `<p style="color: #666; margin-bottom: 15px; line-height: 1.6;">${escapeHtml(group.description)}</p>` : ''}
                    <div style="margin-bottom: 20px;">
                        <p style="color: #666; margin-bottom: 8px;"><strong style="color: #333;">Created by:</strong> ${escapeHtml(group.creator_name)}</p>
                        <p style="color: #666;"><strong style="color: #333;">Created on:</strong> ${new Date(group.created_at).toLocaleDateString()}</p>
                    </div>
                    <hr style="border: none; border-top: 2px solid #f0f0f0; margin: 25px 0;">
                    ${membersHtml}
                    ${requestsHtml}
                </div>
            `;
            
            viewGroupModal.style.display = 'flex';
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading group details:', error);
        showAlert('Failed to load group details', 'error');
    }
}

// Respond to join request
async function respondToRequest(requestId, response) {
    if (!confirm(`Are you sure you want to ${response === 'accepted' ? 'accept' : 'reject'} this request?`)) {
        return;
    }
    
    try {
        const fetchResponse = await fetch(`${API_BASE}groups.php?action=respond-request`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                request_id: requestId,
                response: response
            })
        });
        
        const data = await fetchResponse.json();
        
        if (data.success) {
            showAlert(`Request ${response} successfully!`, 'success');
            if (currentGroupId) {
                viewGroupDetails(currentGroupId);
            }
            loadGroups();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error responding to request:', error);
        showAlert('Failed to process request', 'error');
    }
}

// Open delete confirmation modal
function deleteGroup(groupId, groupName) {
    deleteGroupId.value = groupId;
    deleteGroupName.textContent = `"${groupName}"`;
    deleteConfirmModal.style.display = 'flex';
}

// Handle confirm delete
async function handleConfirmDelete() {
    const groupId = deleteGroupId.value;
    
    if (!groupId) {
        return;
    }
    
    // Disable button during deletion
    confirmDeleteBtn.disabled = true;
    confirmDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    
    try {
        const response = await fetch(`${API_BASE}groups.php?action=delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                group_id: groupId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Group deleted successfully!', 'success');
            deleteConfirmModal.style.display = 'none';
            loadGroups();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error deleting group:', error);
        showAlert('Failed to delete group', 'error');
    } finally {
        // Re-enable button
        confirmDeleteBtn.disabled = false;
        confirmDeleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Group';
    }
}

// Open invite modal
async function openInviteModal(groupId) {
    document.getElementById('inviteGroupId').value = groupId;
    inviteUserModal.style.display = 'flex';
    
    // Load available users
    await loadAvailableUsers(groupId);
}

// Load available users for invitation
async function loadAvailableUsers(groupId) {
    const inviteUsersList = document.getElementById('inviteUsersList');
    inviteUsersList.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #999;">
            <i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i>
            <p>Loading users...</p>
        </div>
    `;
    
    try {
        const response = await fetch(`${API_BASE}groups.php?action=available-users&group_id=${groupId}`);
        const data = await response.json();
        
        if (data.success) {
            const users = data.users;
            
            if (users.length === 0) {
                inviteUsersList.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>No users available to invite. All users are already members or have pending requests.</p>
                    </div>
                `;
                return;
            }
            
            let usersHtml = '';
            users.forEach(user => {
                usersHtml += `
                    <div class="user-list-item">
                        <div class="user-list-info">
                            <strong>${escapeHtml(user.username)}</strong>
                            <span>${escapeHtml(user.email)}</span>
                        </div>
                        <button class="btn btn-primary invite-btn" onclick="sendInviteToUser(${groupId}, ${user.id}, '${escapeHtml(user.username)}')">
                            <i class="fas fa-user-plus"></i> Send Request
                        </button>
                    </div>
                `;
            });
            
            inviteUsersList.innerHTML = usersHtml;
        } else {
            inviteUsersList.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #f44336;">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <p>Error: ${escapeHtml(data.message)}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading available users:', error);
        inviteUsersList.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #f44336;">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>Failed to load users. Please try again.</p>
            </div>
        `;
    }
}

// Send invite to a specific user
async function sendInviteToUser(groupId, userId, username) {
    if (!confirm(`Send invitation to ${username}?`)) {
        return;
    }
    
    try {
        // Get user details first
        const response = await fetch(`${API_BASE}groups.php?action=invite`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                group_id: groupId,
                user_id: userId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(`Invitation sent to ${username} successfully!`, 'success');
            // Reload users list to remove the invited user
            await loadAvailableUsers(groupId);
            // Reload groups to update member counts
            loadGroups();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error sending invite:', error);
        showAlert('Failed to send invitation', 'error');
    }
}

// Load available groups
async function loadAvailableGroups() {
    try {
        const response = await fetch(`${API_BASE}groups.php?action=available`);
        const data = await response.json();
        
        if (data.success) {
            const groups = data.groups;
            availableGroupsContent.innerHTML = '';
            
            if (groups.length === 0) {
                availableGroupsContent.innerHTML = '<p style="text-align: center; padding: 40px;">No available groups to join.</p>';
                return;
            }
            
            groups.forEach(group => {
                const card = document.createElement('div');
                card.className = 'group-card';
                card.innerHTML = `
                    <div class="group-card-header">
                        <h3 class="group-card-title">
                            <i class="fas fa-users"></i>
                            ${escapeHtml(group.name)}
                        </h3>
                    </div>
                    ${group.description ? `<div class="group-description">${escapeHtml(group.description)}</div>` : ''}
                    <div class="group-meta">
                        <span><i class="fas fa-users"></i> ${group.member_count || 0} members</span>
                        <span><i class="fas fa-user"></i> Created by ${escapeHtml(group.creator_name)}</span>
                    </div>
                    <div class="group-actions">
                        <button class="btn btn-primary btn-sm" onclick="sendJoinRequest(${group.id}, '${escapeHtml(group.name)}')">
                            <i class="fas fa-user-plus"></i> Request to Join
                        </button>
                    </div>
                `;
                availableGroupsContent.appendChild(card);
            });
        } else {
            showAlert('Error loading available groups: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading available groups:', error);
        showAlert('Failed to load available groups', 'error');
    }
}

// Send join request
async function sendJoinRequest(groupId, groupName) {
    if (!confirm(`Send a join request to "${groupName}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}groups.php?action=join-request`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                group_id: groupId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Join request sent successfully!', 'success');
            availableGroupsModal.style.display = 'none';
            loadGroups();
            loadAvailableGroups();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error sending join request:', error);
        showAlert('Failed to send join request', 'error');
    }
}

// Load my requests (invitations received)
async function loadMyRequests() {
    myRequestsList.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #999;">
            <i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i>
            <p>Loading invitations...</p>
        </div>
    `;
    
    try {
        const response = await fetch(`${API_BASE}groups.php?action=my-requests`);
        const data = await response.json();
        
        if (data.success) {
            const requests = data.requests;
            
            if (requests.length === 0) {
                myRequestsList.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-bell-slash" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>No pending invitations.</p>
                    </div>
                `;
                loadRequestCount();
                return;
            }
            
            let requestsHtml = '';
            requests.forEach(request => {
                requestsHtml += `
                    <div class="request-item" style="background: #e3f2fd; border: 2px solid #90caf9;">
                        <div class="member-info">
                            <div>
                                <strong style="display: block; color: #333; margin-bottom: 4px; font-size: 16px;">${escapeHtml(request.group_name)}</strong>
                                ${request.group_description ? `<span style="color: #666; font-size: 14px; display: block; margin-bottom: 4px;">${escapeHtml(request.group_description)}</span>` : ''}
                                <span style="color: #666; font-size: 12px;">Invited by: ${escapeHtml(request.inviter_name || 'Admin')}</span>
                            </div>
                        </div>
                        <div class="request-actions">
                            <button class="btn btn-success btn-sm" onclick="acceptInvitation(${request.id})">
                                <i class="fas fa-check"></i> Accept
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="rejectInvitation(${request.id})">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                    </div>
                `;
            });
            
            myRequestsList.innerHTML = requestsHtml;
            // Update count after loading requests
            loadRequestCount();
        } else {
            myRequestsList.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #f44336;">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <p>Error: ${escapeHtml(data.message)}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading requests:', error);
        myRequestsList.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #f44336;">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>Failed to load invitations. Please try again.</p>
            </div>
        `;
    }
}

// Accept invitation
async function acceptInvitation(requestId) {
    if (!confirm('Accept this group invitation?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}groups.php?action=accept-invitation`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                request_id: requestId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Invitation accepted! You are now a member of the group.', 'success');
            myRequestsModal.style.display = 'none';
            loadMyRequests();
            loadGroups();
            loadRequestCount();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error accepting invitation:', error);
        showAlert('Failed to accept invitation', 'error');
    }
}

// Reject invitation
async function rejectInvitation(requestId) {
    if (!confirm('Reject this group invitation?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}groups.php?action=reject-invitation`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                request_id: requestId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Invitation rejected', 'success');
            loadMyRequests();
            loadRequestCount();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error rejecting invitation:', error);
        showAlert('Failed to reject invitation', 'error');
    }
}

// Load request count badge
async function loadRequestCount() {
    try {
        const response = await fetch(`${API_BASE}groups.php?action=my-requests-count`);
        const data = await response.json();
        
        if (data.success) {
            const count = data.count || 0;
            if (count > 0) {
                requestCountBadge.textContent = count > 99 ? '99+' : count;
                requestCountBadge.style.display = 'flex';
            } else {
                requestCountBadge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading request count:', error);
    }
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showAlert(message, type = 'info') {
    // Create alert element
    const alert = document.createElement('div');
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    alert.textContent = message;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => alert.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

