<?php
$pageTitle = 'Manage Admin Roles';
require_once 'includes/head.php';

// Check if user is superadmin
if ($userRole !== 'superadmin') {
    http_response_code(403);
    die('Access denied');
}

$db = Database::getInstance()->getConnection();

// Fetch all admin users with their assigned roles
$admins = $db->prepare("
    SELECT 
        au.id,
        au.firstname,
        au.surname,
        au.staff_id,
        au.position,
        au.department,
        GROUP_CONCAT(ar.role_name SEPARATOR ', ') as assigned_roles
    FROM admin_users au
    LEFT JOIN admin_roles ar ON au.id = ar.admin_id AND ar.removed_at IS NULL
    WHERE au.is_active = TRUE
    GROUP BY au.id, au.firstname, au.surname, au.staff_id, au.position, au.department
    ORDER BY au.firstname ASC
");
$admins->execute();
$adminsList = $admins->fetchAll();

// Define available roles
$availableRoles = [
    'Rector',
    'Deputy Rector',
    'Registrar',
    'Bursar',
    'Academic Dean',
    'Dean',
    'HOD',
    'Director of ICT',
    'Provost',
    'Establishment'
];
?>

<div class="container mx-auto">
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Manage Admin Roles</h2>
        <p class="text-gray-600 dark:text-gray-300">Assign and manage institutional roles for admin users</p>
    </div>

    <!-- Admin Users List -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Staff Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Staff ID</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Position</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Assigned Roles</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($adminsList as $admin): ?>
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                            <?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['surname']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                            <?php echo htmlspecialchars($admin['staff_id']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                            <?php echo htmlspecialchars($admin['position']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <?php if ($admin['assigned_roles']): ?>
                                <span class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-xs font-semibold">
                                    <?php echo htmlspecialchars($admin['assigned_roles']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-500 dark:text-gray-400">No roles assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <button class="btn-primary text-xs py-1 px-3" onclick="openRoleModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['surname']); ?>')">
                                <i class="fas fa-edit mr-1"></i>Manage
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Role Management Modal -->
<div id="roleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">
            Manage Roles - <span id="adminNameInModal" class="text-blue-600 dark:text-blue-400"></span>
        </h3>
        
        <input type="hidden" id="adminIdInModal" value="">
        
        <!-- Available Roles -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Available Roles:</label>
            <div id="rolesCheckboxContainer" class="space-y-2">
                <!-- Dynamically populated -->
            </div>
        </div>
        
        <!-- Current Roles -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Currently Assigned:</label>
            <div id="currentRolesDisplay" class="bg-gray-100 dark:bg-gray-700 p-3 rounded text-sm text-gray-700 dark:text-gray-200">
                Loading...
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex gap-3">
            <button onclick="closeRoleModal()" class="btn-secondary flex-1">Cancel</button>
            <button onclick="saveRoles()" class="btn-primary flex-1">
                <i class="fas fa-save mr-1"></i>Save Changes
            </button>
        </div>
    </div>
</div>

<script>
const availableRoles = <?php echo json_encode($availableRoles); ?>;

function showDialog(title, message, type = 'info') {
    const dialog = document.createElement('div');
    dialog.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[999]';
    
    const bgColor = type === 'success' ? 'bg-green-50 dark:bg-green-900' : 
                   type === 'error' ? 'bg-red-50 dark:bg-red-900' : 
                   'bg-blue-50 dark:bg-blue-900';
    
    const textColor = type === 'success' ? 'text-green-800 dark:text-green-200' : 
                     type === 'error' ? 'text-red-800 dark:text-red-200' : 
                     'text-blue-800 dark:text-blue-200';
    
    const borderColor = type === 'success' ? 'border-green-400' : 
                       type === 'error' ? 'border-red-400' : 
                       'border-blue-400';
    
    const icon = type === 'success' ? 'fas fa-check-circle text-green-600' : 
                type === 'error' ? 'fas fa-exclamation-circle text-red-600' : 
                'fas fa-info-circle text-blue-600';
    
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
        if (e.target === this) {
            this.remove();
        }
    });
}

function openRoleModal(adminId, adminName) {
    document.getElementById('adminIdInModal').value = adminId;
    document.getElementById('adminNameInModal').textContent = adminName;
    
    // Fetch current roles for this admin
    fetch('api/get_admin_roles.php?admin_id=' + adminId)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showDialog('Error', 'Failed to load roles: ' + data.message, 'error');
                return;
            }
            
            const currentRoles = data.roles || [];
            
            // Display current roles
            if (currentRoles.length === 0) {
                document.getElementById('currentRolesDisplay').textContent = 'No roles assigned';
            } else {
                document.getElementById('currentRolesDisplay').innerHTML = currentRoles.map(role => 
                    '<span class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs mr-2 mb-2">' + role + '</span>'
                ).join('');
            }
            
            // Build checkboxes
            const container = document.getElementById('rolesCheckboxContainer');
            container.innerHTML = '';
            availableRoles.forEach(role => {
                const isChecked = currentRoles.includes(role);
                container.innerHTML += `
                    <label class="flex items-center">
                        <input type="checkbox" class="role-checkbox" value="${role}" ${isChecked ? 'checked' : ''} 
                               class="w-4 h-4 rounded dark:bg-gray-700">
                        <span class="ml-2 text-gray-700 dark:text-gray-300">${role}</span>
                    </label>
                `;
            });
            
            // Show modal
            document.getElementById('roleModal').classList.remove('hidden');
        })
        .catch(error => {
            showDialog('Error', 'Failed to load roles. Please try again.', 'error');
        });
}

function closeRoleModal() {
    document.getElementById('roleModal').classList.add('hidden');
}

function saveRoles() {
    const adminId = document.getElementById('adminIdInModal').value;
    const checkboxes = document.querySelectorAll('.role-checkbox');
    const selectedRoles = [];
    
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            selectedRoles.push(checkbox.value);
        }
    });
    
    fetch('api/update_admin_roles.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            admin_id: adminId,
            roles: selectedRoles
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showDialog('Success', 'Roles updated successfully!', 'success');
            setTimeout(() => {
                closeRoleModal();
                location.reload();
            }, 1500);
        } else {
            showDialog('Error', data.message, 'error');
        }
    })
    .catch(error => {
        showDialog('Error', 'Failed to save roles. Please try again.', 'error');
    });
}

// Close modal when clicking outside
document.getElementById('roleModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRoleModal();
    }
});
</script>

<?php require_once 'includes/foot.php'; ?>
