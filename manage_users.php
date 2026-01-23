<?php
$pageTitle = 'Manage Users';
require_once 'includes/head.php';
require_once 'includes/nigeria_lga.php';

// Faculty/School options with departments
$faculties = [
    'School of Applied Sciences' => ['Computer Science', 'Library and Information Science', 'Science Laboratory Technology', 'Statistics'],
    'School of Business Studies' => ['Accountancy', 'Business Administration and Management', 'Maritime Transport and Business Studies', 'Petroleum Marketing and Business Studies', 'Public Administration'],
    'School of Engineering Technology' => ['Chemical Engineering Technology', 'Electrical Electronics Engineering Technology', 'Industrial Safety and Environmental Engineering Technology', 'Mechanical Engineering Technology', 'Welding and Fabrication Technology', 'Mineral and Petroleum Resource Engineering Technology'],
    'Administrative and Support Services' => ['Rectorate', 'Registry', 'Bursary', 'Internal Audit', 'Physical Planning', 'Works and Services', 'Security Unit', 'Medical Center', 'Library (Admin)', 'Student Affairs', 'Academic Planning', 'Information and Communication Technology (ICT)']
];

// Check if user is superadmin or has specific admin roles
$allowed_roles = ['Rector', 'Bursar', 'Registrar', 'Establishment Unit'];
$is_authorized = $userRole === 'superadmin';

if ($userRole === 'admin') {
    // Check if admin has one of the allowed roles
    try {
        $db = Database::getInstance();
        $admin_data = $db->fetchOne(
            "SELECT position FROM admin_users WHERE id = ?",
            [$_SESSION['user_id']]
        );
        if ($admin_data && in_array($admin_data['position'], $allowed_roles)) {
            $is_authorized = true;
        }
    } catch (Exception $e) {
        $is_authorized = false;
    }
}

if (!$is_authorized) {
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
        <div class="flex flex-col sm:grid sm:grid-cols-2 lg:flex lg:flex-row gap-2 md:gap-4 mb-4">
            <!-- Search Box -->
            <div class="sm:col-span-2 lg:flex-1">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="Search users..." 
                           class="input-field pl-10 w-full text-sm" onkeyup="filterTable()">
                </div>
            </div>
            
            <!-- Sort Dropdown -->
            <div class="sm:col-span-1">
                <select id="sortBy" onchange="sortTable()" class="input-field w-full text-sm">
                    <option value="name">Sort by Name</option>
                    <option value="email">Sort by Email</option>
                    <option value="position">Position</option>
                    <option value="department">Department</option>
                    <option value="date">Newest</option>
                </select>
            </div>
            
            <!-- Export CSV Button -->
            <button onclick="exportToCSV()" class="btn-secondary whitespace-nowrap text-sm py-2" title="Export">
                <i class="fas fa-download mr-1"></i><span class="hidden sm:inline">Export</span>
            </button>
            
            <!-- Add New User Button -->
            <button onclick="openAddUserModal()" class="btn-primary whitespace-nowrap text-sm py-2" title="Add">
                <i class="fas fa-plus mr-1"></i><span class="hidden sm:inline">Add</span>
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
            <table class="w-full text-xs sm:text-sm" id="usersTable">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Photo</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Name</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-left font-semibold text-gray-700 dark:text-gray-200 hidden md:table-cell">Staff ID</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-left font-semibold text-gray-700 dark:text-gray-200 hidden lg:table-cell">Email</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-left font-semibold text-gray-700 dark:text-gray-200 hidden xl:table-cell">Position</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-left font-semibold text-gray-700 dark:text-gray-200 hidden xl:table-cell">Dept</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Status</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Act</th>
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
<div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-2 sm:p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-3 sm:p-6 flex justify-between items-center">
            <h3 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white" id="modalTitle">Add New User</h3>
            <button onclick="closeUserModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <i class="fas fa-times text-xl sm:text-2xl"></i>
            </button>
        </div>
        
        <form id="userForm" class="p-3 sm:p-6 space-y-4">
            <input type="hidden" id="userId" value="">
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">First Name *</label>
                    <input type="text" id="firstName" class="input-field" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Last Name *</label>
                    <input type="text" id="lastName" class="input-field" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Other Name</label>
                    <input type="text" id="otherName" class="input-field">
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Staff ID *</label>
                    <input type="text" id="staffId" class="input-field" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Phone Number</label>
                    <input type="text" id="phoneNumber" class="input-field">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Email *</label>
                <input type="email" id="email" class="input-field" required>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Faculty/School *</label>
                    <select id="faculty" class="input-field" required onchange="updateDepartments()">
                        <option value="">Select Faculty</option>
                        <?php foreach (array_keys($faculties) as $faculty): ?>
                            <option value="<?php echo htmlspecialchars($faculty); ?>"><?php echo htmlspecialchars($faculty); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Department *</label>
                    <select id="department" class="input-field" required>
                        <option value="">Select Department</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Position *</label>
                    <input type="text" id="position" class="input-field" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Salary Structure</label>
                    <select id="salaryStructure" class="input-field">
                        <option value="">Select Structure</option>
                        <option value="CONPCASS">CONPCASS (Academic)</option>
                        <option value="CONTEDISS">CONTEDISS (Non-Academic)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Rank</label>
                    <input type="text" id="rank" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">GL</label>
                    <input type="text" id="gl" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Step</label>
                    <input type="text" id="step" class="input-field">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">State of Origin</label>
                    <select id="state" class="input-field" onchange="updateLGAs()">
                        <option value="">Select State</option>
                        <?php 
                        $statesLgas = getNigerianStatesWithLGAs();
                        foreach (array_keys($statesLgas) as $state): 
                        ?>
                            <option value="<?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($state); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">LGA of Origin</label>
                    <select id="lga" class="input-field">
                        <option value="">Select LGA</option>
                    </select>
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
const nigeriaStatesLGAs = <?php echo json_encode(getNigerianStatesWithLGAs()); ?>;
const facultiesData = <?php echo json_encode($faculties); ?>;

function updateLGAs() {
    const stateSelect = document.getElementById('state');
    const lgaSelect = document.getElementById('lga');
    const selectedState = stateSelect.value;
    
    // Clear current LGAs
    lgaSelect.innerHTML = '<option value="">Select LGA</option>';
    
    if (selectedState && nigeriaStatesLGAs[selectedState]) {
        nigeriaStatesLGAs[selectedState].forEach(lga => {
            const option = document.createElement('option');
            option.value = lga;
            option.textContent = lga;
            lgaSelect.appendChild(option);
        });
    }
}

function updateDepartments() {
    const facultySelect = document.getElementById('faculty');
    const departmentSelect = document.getElementById('department');
    const selectedFaculty = facultySelect.value;

    // Clear current options except first
    departmentSelect.innerHTML = '<option value="">Select Department</option>';

    if (selectedFaculty && facultiesData[selectedFaculty]) {
        facultiesData[selectedFaculty].forEach(dept => {
            const option = document.createElement('option');
            option.value = dept;
            option.textContent = dept;
            departmentSelect.appendChild(option);
        });
    }
}

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
            <td class="px-2 sm:px-4 py-2 sm:py-4">
                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full overflow-hidden bg-blue-600 flex items-center justify-center text-white font-bold text-xs sm:text-sm flex-shrink-0">
                    ${user.profile_picture && user.profile_picture !== '' ? `<img src="${user.profile_picture}" alt="Profile" class="w-full h-full object-cover">` : `${user.firstname.charAt(0)}${user.surname.charAt(0)}`}
                </div>
            </td>
            <td class="px-2 sm:px-4 py-2 sm:py-4 text-gray-800 dark:text-gray-200 font-semibold text-xs sm:text-sm">
                ${user.firstname}<br/><span class="text-xs text-gray-500 dark:text-gray-400">${user.surname}</span>
            </td>
            <td class="px-2 sm:px-4 py-2 sm:py-4 text-gray-800 dark:text-gray-200 hidden md:table-cell text-xs">${user.staff_id}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-4 text-gray-800 dark:text-gray-200 hidden lg:table-cell text-xs">${user.official_email}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-4 text-gray-800 dark:text-gray-200 hidden xl:table-cell text-xs">${user.position}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-4 text-gray-800 dark:text-gray-200 hidden xl:table-cell text-xs">${user.department}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-4 text-xs">
                <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold ${user.is_active ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'}">
                    ${user.is_active ? 'On' : 'Off'}
                </span>
            </td>
            <td class="px-2 sm:px-4 py-2 sm:py-4 text-xs space-x-1">
                <button onclick="viewUser(${user.id})" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition" title="View">
                    <i class="fas fa-eye"></i>
                </button>
                <button onclick="editUser(${user.id})" class="text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 transition" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteUser(${user.id})" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition" title="Delete">
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
    
    // Explicitly clear additional fields
    document.getElementById('otherName').value = '';
    document.getElementById('phoneNumber').value = '';
    document.getElementById('faculty').value = '';
    document.getElementById('department').innerHTML = '<option value="">Select Department</option>';
    document.getElementById('salaryStructure').value = '';
    document.getElementById('gl').value = '';
    document.getElementById('step').value = '';
    document.getElementById('rank').value = '';
    document.getElementById('state').value = '';
    document.getElementById('lga').innerHTML = '<option value="">Select LGA</option>';
    
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
            <div><strong class="text-gray-700 dark:text-gray-200">Full Name:</strong><p class="text-gray-600 dark:text-gray-400">${user.firstname} ${user.othername ? user.othername + ' ' : ''}${user.surname}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Staff ID:</strong><p class="text-gray-600 dark:text-gray-400">${user.staff_id}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Email:</strong><p class="text-gray-600 dark:text-gray-400">${user.official_email}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Faculty/School:</strong><p class="text-gray-600 dark:text-gray-400">${user.faculty || 'N/A'}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Department:</strong><p class="text-gray-600 dark:text-gray-400">${user.department}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Position:</strong><p class="text-gray-600 dark:text-gray-400">${user.position}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Phone:</strong><p class="text-gray-600 dark:text-gray-400">${user.phone_number}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">State of Origin:</strong><p class="text-gray-600 dark:text-gray-400">${user.state_origin || 'N/A'}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">LGA of Origin:</strong><p class="text-gray-600 dark:text-gray-400">${user.lga_origin || 'N/A'}</p></div>
            
            <div class="col-span-2 border-t border-gray-100 dark:border-gray-700 mt-2 pt-2 font-bold text-blue-600 dark:text-blue-400">Hierarchy Details</div>
            <div><strong class="text-gray-700 dark:text-gray-200">Salary Structure:</strong><p class="text-gray-600 dark:text-gray-400">${user.salary_structure || 'N/A'}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Grade Level:</strong><p class="text-gray-600 dark:text-gray-400">${user.gl || 'N/A'}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Step:</strong><p class="text-gray-600 dark:text-gray-400">${user.step || 'N/A'}</p></div>
            <div><strong class="text-gray-700 dark:text-gray-200">Rank:</strong><p class="text-gray-600 dark:text-gray-400">${user.rank || 'N/A'}</p></div>
            
            <div class="col-span-2 border-t border-gray-100 dark:border-gray-700 mt-2 pt-2">
                <strong class="text-gray-700 dark:text-gray-200">Status:</strong>
                <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold ${user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'} ml-2">
                    ${user.is_active ? 'Active' : 'Inactive'}
                </span>
            </div>
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
    document.getElementById('otherName').value = user.othername || '';
    document.getElementById('staffId').value = user.staff_id;
    document.getElementById('phoneNumber').value = user.phone_number || '';
    document.getElementById('email').value = user.official_email;
    document.getElementById('faculty').value = user.faculty || '';
    updateDepartments();
    document.getElementById('department').value = user.department;
    document.getElementById('position').value = user.position;
    document.getElementById('salaryStructure').value = user.salary_structure || '';
    document.getElementById('gl').value = user.gl || '';
    document.getElementById('step').value = user.step || '';
    document.getElementById('rank').value = user.rank || '';
    
    // Set State and LGA
    const stateSelect = document.getElementById('state');
    stateSelect.value = user.state_origin || '';
    updateLGAs();
    document.getElementById('lga').value = user.lga_origin || '';
    
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('passwordField').classList.add('hidden');
    document.getElementById('userModal').classList.remove('hidden');
}

function saveUser() {
    const userId = document.getElementById('userId').value;
    const submitBtn = document.querySelector('#userForm button[type="submit"]');
    const originalBtnContent = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

    const userData = {
        firstname: document.getElementById('firstName').value,
        surname: document.getElementById('lastName').value,
        othername: document.getElementById('otherName').value,
        staff_id: document.getElementById('staffId').value,
        phone_number: document.getElementById('phoneNumber').value,
        official_email: document.getElementById('email').value,
        faculty: document.getElementById('faculty').value,
        department: document.getElementById('department').value,
        position: document.getElementById('position').value,
        salary_structure: document.getElementById('salaryStructure').value,
        gl: document.getElementById('gl').value,
        step: document.getElementById('step').value,
        rank: document.getElementById('rank').value,
        state_origin: document.getElementById('state').value,
        lga_origin: document.getElementById('lga').value,
        password: document.getElementById('password').value
    };
    
    const url = userId ? 'api/update_user.php' : 'api/create_user.php';
    if (userId) userData.id = userId;
    
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(userData)
    })
    .then(async r => {
        const text = await r.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Server response was not valid JSON:', text);
            throw new Error('Invalid server response');
        }
    })
    .then(data => {
        if (data.success) {
            showDialog('Success', data.message, 'success');
            setTimeout(() => { closeUserModal(); loadUsers(); }, 1500);
        } else {
            showDialog('Error', data.message, 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnContent;
        }
    })
    .catch(e => {
        console.error('Save error:', e);
        showDialog('Error', e.message || 'Failed to save user', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnContent;
    });
}

function deleteUser(userId) {
    const user = usersData.find(u => u.id === userId);
    if (!user) return;
    
    showConfirmDialog(
        'Delete User',
        `Are you sure you want to delete ${user.firstname} ${user.surname}? This action cannot be undone.`,
        function() {
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
    );
}

function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase().trim();
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
