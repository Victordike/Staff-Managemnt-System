<?php
$pageTitle = 'Manage Users';
require_once 'includes/head.php';

// Check if user is superadmin
if ($userRole !== 'superadmin') {
    http_response_code(403);
    die('Access denied');
}
?>

<div class="container mx-auto">
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Manage Admin Users</h2>
        <p class="text-gray-600 dark:text-gray-300">View, edit, search, and manage all admin user accounts</p>
    </div>

    <!-- Controls Bar -->
    <div class="card mb-6">
        <div class="flex flex-col md:flex-row gap-4 mb-4">
            <!-- Search Box -->
            <div class="flex-1">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="Search by name, email, staff ID..." 
                           class="input-field pl-10 w-full" onkeyup="filterTable()">
                </div>
            </div>
            
            <!-- Sort Dropdown -->
            <div>
                <select id="sortBy" onchange="sortTable()" class="input-field">
                    <option value="name">Sort by Name</option>
                    <option value="email">Sort by Email</option>
                    <option value="position">Sort by Position</option>
                    <option value="department">Sort by Department</option>
                    <option value="date">Sort by Date (Newest)</option>
                </select>
            </div>
            
            <!-- Export CSV Button -->
            <button onclick="exportToCSV()" class="btn-secondary whitespace-nowrap">
                <i class="fas fa-download mr-2"></i>Export CSV
            </button>
            
            <!-- Add New User Button -->
            <button onclick="openAddUserModal()" class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i>Add New User
            </button>
        </div>
        
        <!-- Results Count -->
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <span id="resultCount">0</span> user(s)
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full" id="usersTable">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Photo</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Full Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Staff ID</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Email</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Position</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Department</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Loading users...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-6 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white" id="modalTitle">Add New User</h3>
            <button onclick="closeUserModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <form id="userForm" class="p-6 space-y-4">
            <input type="hidden" id="userId" value="">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">First Name *</label>
                    <input type="text" id="firstName" class="input-field" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Last Name *</label>
                    <input type="text" id="lastName" class="input-field" required>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Staff ID *</label>
                <input type="text" id="staffId" class="input-field" required>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Email *</label>
                <input type="email" id="email" class="input-field" required>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Position *</label>
                    <input type="text" id="position" class="input-field" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Department *</label>
                    <input type="text" id="department" class="input-field" required>
                </div>
            </div>
            
            <div id="passwordField" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Password *</label>
                <input type="password" id="password" class="input-field">
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeUserModal()" class="btn-secondary flex-1">Cancel</button>
                <button type="submit" class="btn-primary flex-1">
                    <i class="fas fa-save mr-2"></i>Save User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View User Modal -->
<div id="viewUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-6 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">User Details</h3>
            <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <div id="viewUserContent" class="p-6">
            <!-- Dynamically populated -->
        </div>
    </div>
</div>

<script>
let usersData = [];
let filteredData = [];

// Load users on page load
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
});

// Handle form submission
document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveUser();
});

function loadUsers() {
    fetch('api/get_users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                usersData = data.users || [];
                filteredData = [...usersData];
                displayUsers(filteredData);
            } else {
                showDialog('Error', data.message, 'error');
            }
        })
        .catch(error => showDialog('Error', 'Failed to load users', 'error'));
}

function displayUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-inbox text-3xl mb-2"></i>
                    <p>No users found</p>
                </td>
            </tr>
        `;
        document.getElementById('resultCount').textContent = '0';
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <td class="px-6 py-4">
                <div class="w-10 h-10 rounded-full overflow-hidden bg-blue-600 flex items-center justify-center text-white font-bold text-sm">
                    ${user.profile_picture && user.profile_picture !== '' ? `<img src="${user.profile_picture}" alt="Profile" class="w-full h-full object-cover">` : `${user.firstname.charAt(0)}${user.surname.charAt(0)}`}
                </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200 font-semibold">
                ${user.firstname} ${user.surname}
            </td>
            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">${user.staff_id}</td>
            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">${user.official_email}</td>
            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">${user.position}</td>
            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">${user.department}</td>
            <td class="px-6 py-4 text-sm">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${user.is_active ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'}">
                    ${user.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td class="px-6 py-4 text-sm space-x-2">
                <button onclick="viewUser(${user.id})" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
                    <i class="fas fa-eye"></i>
                </button>
                <button onclick="editUser(${user.id})" class="text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 transition">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteUser(${user.id})" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    document.getElementById('resultCount').textContent = users.length;
}

function openAddUserModal() {
    document.getElementById('userId').value = '';
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('passwordField').classList.remove('hidden');
    document.getElementById('userForm').reset();
    document.getElementById('userModal').classList.remove('hidden');
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

function closeViewModal() {
    document.getElementById('viewUserModal').classList.add('hidden');
}

function viewUser(userId) {
    const user = usersData.find(u => u.id === userId);
    if (!user) return;
    
    const profileImg = user.profile_picture && user.profile_picture !== '' 
        ? `<img src="${user.profile_picture}" alt="Profile" class="w-full h-full object-cover rounded-lg">`
        : `<div class="w-full h-32 rounded-lg bg-blue-600 flex items-center justify-center text-white text-4xl font-bold">${user.firstname.charAt(0)}${user.surname.charAt(0)}</div>`;
    
    const content = `
        <div class="mb-6">
            <div class="w-32 h-32">
                ${profileImg}
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><strong class="text-gray-700 dark:text-gray-200">First Name:</strong><p class="text-gray-600 dark:text-gray-400">${user.firstname}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Last Name:</strong><p class="text-gray-600 dark:text-gray-400">${user.surname}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Staff ID:</strong><p class="text-gray-600 dark:text-gray-400">${user.staff_id}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Email:</strong><p class="text-gray-600 dark:text-gray-400">${user.official_email}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Position:</strong><p class="text-gray-600 dark:text-gray-400">${user.position}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Department:</strong><p class="text-gray-600 dark:text-gray-400">${user.department}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Phone:</strong><p class="text-gray-600 dark:text-gray-400">${user.phone_number}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Status:</strong><p class="text-gray-600 dark:text-gray-400">${user.is_active ? 'Active' : 'Inactive'}</p></div>
        </div>
        <div class="mt-6 flex gap-3">
            <button onclick="editUser(${user.id}); closeViewModal();" class="btn-primary flex-1">
                <i class="fas fa-edit mr-2"></i>Edit
            </button>
            <button onclick="closeViewModal()" class="btn-secondary flex-1">Close</button>
        </div>
    `;
    document.getElementById('viewUserContent').innerHTML = content;
    document.getElementById('viewUserModal').classList.remove('hidden');
}

function editUser(userId) {
    const user = usersData.find(u => u.id === userId);
    if (!user) return;
    
    document.getElementById('userId').value = userId;
    document.getElementById('firstName').value = user.firstname;
    document.getElementById('lastName').value = user.surname;
    document.getElementById('staffId').value = user.staff_id;
    document.getElementById('email').value = user.official_email;
    document.getElementById('position').value = user.position;
    document.getElementById('department').value = user.department;
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('passwordField').classList.add('hidden');
    document.getElementById('userModal').classList.remove('hidden');
}

function saveUser() {
    const userId = document.getElementById('userId').value;
    const userData = {
        firstname: document.getElementById('firstName').value,
        surname: document.getElementById('lastName').value,
        staff_id: document.getElementById('staffId').value,
        official_email: document.getElementById('email').value,
        position: document.getElementById('position').value,
        department: document.getElementById('department').value,
        password: document.getElementById('password').value
    };
    
    const url = userId ? 'api/update_user.php' : 'api/create_user.php';
    if (userId) userData.id = userId;
    
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(userData)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showDialog('Success', data.message, 'success');
            setTimeout(() => { closeUserModal(); loadUsers(); }, 1500);
        } else {
            showDialog('Error', data.message, 'error');
        }
    })
    .catch(e => showDialog('Error', 'Failed to save user', 'error'));
}

function deleteUser(userId) {
    const user = usersData.find(u => u.id === userId);
    if (!user) return;
    
    if (confirm(`Are you sure you want to delete ${user.firstname} ${user.surname}?`)) {
        fetch('api/delete_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: userId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showDialog('Success', 'User deleted successfully', 'success');
                setTimeout(() => loadUsers(), 1500);
            } else {
                showDialog('Error', data.message, 'error');
            }
        })
        .catch(e => showDialog('Error', 'Failed to delete user', 'error'));
    }
}

function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    filteredData = usersData.filter(user => 
        user.firstname.toLowerCase().includes(search) ||
        user.surname.toLowerCase().includes(search) ||
        user.official_email.toLowerCase().includes(search) ||
        user.staff_id.toLowerCase().includes(search)
    );
    sortTable();
}

function sortTable() {
    const sortBy = document.getElementById('sortBy').value;
    
    filteredData.sort((a, b) => {
        let aVal, bVal;
        
        if (sortBy === 'name') {
            aVal = (a.firstname + a.surname).toLowerCase();
            bVal = (b.firstname + b.surname).toLowerCase();
        } else if (sortBy === 'email') {
            aVal = a.official_email.toLowerCase();
            bVal = b.official_email.toLowerCase();
        } else if (sortBy === 'position') {
            aVal = a.position.toLowerCase();
            bVal = b.position.toLowerCase();
        } else if (sortBy === 'department') {
            aVal = a.department.toLowerCase();
            bVal = b.department.toLowerCase();
        } else if (sortBy === 'date') {
            aVal = new Date(b.created_at);
            bVal = new Date(a.created_at);
            return aVal - bVal;
        }
        
        return aVal.localeCompare(bVal);
    });
    
    displayUsers(filteredData);
}

// Dialog helper (same as manage_admin_roles.php)
function showDialog(title, message, type = 'info') {
    const dialog = document.createElement('div');
    dialog.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[999]';
    
    const bgColor = type === 'success' ? 'bg-green-50 dark:bg-green-900' : type === 'error' ? 'bg-red-50 dark:bg-red-900' : 'bg-blue-50 dark:bg-blue-900';
    const textColor = type === 'success' ? 'text-green-800 dark:text-green-200' : type === 'error' ? 'text-red-800 dark:text-red-200' : 'text-blue-800 dark:text-blue-200';
    const borderColor = type === 'success' ? 'border-green-400' : type === 'error' ? 'border-red-400' : 'border-blue-400';
    const icon = type === 'success' ? 'fas fa-check-circle text-green-600' : type === 'error' ? 'fas fa-exclamation-circle text-red-600' : 'fas fa-info-circle text-blue-600';
    
    dialog.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md w-full mx-4 border-l-4 ${borderColor}">
            <div class="flex items-start mb-4">
                <i class="${icon} text-2xl mr-3"></i>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">${title}</h3>
            </div>
            <p class="${textColor} mb-6">${message}</p>
            <div class="flex justify-end">
                <button onclick="this.closest('.fixed').remove()" class="btn-primary px-4 py-2">OK</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(dialog);
    dialog.addEventListener('click', function(e) {
        if (e.target === this) this.remove();
    });
}

// Export users to CSV
function exportToCSV() {
    window.location.href = 'api/export_users_csv.php';
}
</script>

<?php require_once 'includes/foot.php'; ?>
