<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$pageTitle = 'Manage Leave Balances';
require_once 'includes/head.php';

$db = Database::getInstance();

// Check if user is Establishment or Superadmin
$isEst = false;
$roles = $db->fetchAll("SELECT role_name FROM admin_roles WHERE admin_id = ? AND removed_at IS NULL", [$adminId]);
foreach ($roles as $role) {
    if ($role['role_name'] === 'Establishment') $isEst = true;
}

if (!$isEst && $userRole !== 'superadmin') {
    die('Access denied');
}

$currentYear = date('Y');

// Handle Batch Initialization
if (isset($_POST['action']) && $_POST['action'] === 'initialize_all') {
    $staffList = $db->fetchAll("SELECT id, sex FROM admin_users");
    $leaveTypes = $db->fetchAll("SELECT * FROM leave_types WHERE is_active = TRUE");
    $count = 0;
    
    try {
        foreach ($staffList as $staff) {
            $userGender = $staff['sex'] ?? 'Any';
            foreach ($leaveTypes as $type) {
                // Filter by gender
                $typeGender = $type['gender'];
                if ($typeGender === 'Female' && strtolower($userGender) !== 'female') continue;
                if ($typeGender === 'Male' && strtolower($userGender) !== 'male') continue;

                // Check if already exists for this staff, type and year
                $exists = $db->fetchOne("SELECT id FROM leave_balances WHERE admin_id = ? AND leave_type_id = ? AND year = ?", [$staff['id'], $type['id'], $currentYear]);
                if (!$exists) {
                    $db->query(
                        "INSERT INTO leave_balances (admin_id, leave_type_id, year, entitled_days, used_days, remaining_days) 
                         VALUES (?, ?, ?, ?, 0, ?)",
                        [$staff['id'], $type['id'], $currentYear, $type['default_days'], $type['default_days']]
                    );
                    $count++;
                }
            }
        }
        setFlashMessage("Batch initialization completed. $count new balance records created.", 'success');
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Handle Balance Initialization
if (isset($_POST['action']) && $_POST['action'] === 'initialize') {
    $staffId = $_POST['staff_id'];
    $staffInfo = $db->fetchOne("SELECT sex FROM admin_users WHERE id = ?", [$staffId]);
    $userGender = $staffInfo['sex'] ?? 'Any';
    $leaveTypes = $db->fetchAll("SELECT * FROM leave_types WHERE is_active = TRUE");
    
    try {
        foreach ($leaveTypes as $type) {
            // Filter by gender
            $typeGender = $type['gender'];
            if ($typeGender === 'Female' && strtolower($userGender) !== 'female') continue;
            if ($typeGender === 'Male' && strtolower($userGender) !== 'male') continue;

            $db->query(
                "INSERT IGNORE INTO leave_balances (admin_id, leave_type_id, year, entitled_days, used_days, remaining_days) 
                 VALUES (?, ?, ?, ?, 0, ?)",
                [$staffId, $type['id'], $currentYear, $type['default_days'], $type['default_days']]
            );
        }
        setFlashMessage('Leave balances initialized successfully', 'success');
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Handle Manual Update
if (isset($_POST['action']) && $_POST['action'] === 'update_balance') {
    $balanceId = $_POST['balance_id'];
    $entitled = $_POST['entitled_days'];
    $used = $_POST['used_days'];
    $remaining = $entitled - $used;
    
    try {
        $db->query(
            "UPDATE leave_balances SET entitled_days = ?, used_days = ?, remaining_days = ? WHERE id = ?",
            [$entitled, $used, $remaining, $balanceId]
        );
        setFlashMessage('Balance updated successfully', 'success');
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Fetch all staff and their balances for the current year
$staffList = $db->fetchAll("
    SELECT au.id, au.staff_id, au.firstname, au.surname, au.department 
    FROM admin_users au 
    ORDER BY au.surname ASC
");

// Fetch leave types for reference
$leaveTypes = $db->fetchAll("SELECT * FROM leave_types WHERE is_active = TRUE");
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Manage Leave Balances</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Initialize and adjust leave entitlements for staff (Year: <?php echo $currentYear; ?>)</p>
    </div>
    <div class="flex gap-3">
        <form method="POST" onsubmit="return confirm('Are you sure you want to initialize balances for ALL staff members who haven\'t been set up yet?')">
            <input type="hidden" name="action" value="initialize_all">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition flex items-center">
                <i class="fas fa-users-cog mr-2"></i> Batch Initialize All
            </button>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="p-0 overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Staff Details</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Balances</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($staffList as $staff): ?>
                    <?php 
                        $balances = $db->fetchAll("
                            SELECT lb.*, lt.name as leave_type 
                            FROM leave_balances lb 
                            JOIN leave_types lt ON lb.leave_type_id = lt.id 
                            WHERE lb.admin_id = ? AND lb.year = ?
                        ", [$staff['id'], $currentYear]);
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-700 font-bold mr-3">
                                    <?php echo strtoupper($staff['firstname'][0] . $staff['surname'][0]); ?>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($staff['firstname'] . ' ' . $staff['surname']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($staff['staff_id']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($staff['department']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if (empty($balances)): ?>
                                <p class="text-xs text-red-500 italic text-center">Not initialized</p>
                            <?php else: ?>
                                <div class="flex flex-wrap gap-2 justify-center">
                                    <?php foreach ($balances as $bal): ?>
                                        <div class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-[10px] flex flex-col items-center min-w-[60px]" title="<?php echo $bal['leave_type']; ?>">
                                            <span class="font-bold text-gray-500 uppercase"><?php echo substr($bal['leave_type'], 0, 3); ?></span>
                                            <span class="text-blue-600 font-black"><?php echo $bal['remaining_days']; ?>/<?php echo $bal['entitled_days']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <?php if (empty($balances)): ?>
                                    <form method="POST">
                                        <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                        <button type="submit" name="action" value="initialize" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-1.5 px-3 rounded transition">
                                            Initialize
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button onclick='openEditModal(<?php echo json_encode($staff); ?>, <?php echo json_encode($balances); ?>)' class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-bold text-xs uppercase tracking-widest">
                                        Edit
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Balance Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="bg-indigo-600 p-6 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white" id="modalTitle">Edit Leave Balances</h3>
            <button onclick="closeModal()" class="text-white hover:text-gray-200"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-8">
            <div id="modalStaffInfo" class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                <!-- Staff info injected here -->
            </div>
            
            <div id="balancesList" class="space-y-4 max-h-[400px] overflow-y-auto pr-2">
                <!-- Balances forms injected here -->
            </div>
        </div>
    </div>
</div>

<script>
function openEditModal(staff, balances) {
    $('#modalTitle').text('Leave Balances: ' + staff.firstname + ' ' + staff.surname);
    $('#modalStaffInfo').html(`
        <div class="flex justify-between items-center">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Staff ID</p>
                <p class="font-black text-gray-800 dark:text-white">${staff.staff_id}</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Department</p>
                <p class="font-black text-gray-800 dark:text-white">${staff.department}</p>
            </div>
        </div>
    `);
    
    let html = '';
    balances.forEach(bal => {
        html += `
            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900">
                <p class="text-sm font-bold text-indigo-600 mb-3">${bal.leave_type}</p>
                <form method="POST" class="flex items-end gap-4">
                    <input type="hidden" name="balance_id" value="${bal.id}">
                    <input type="hidden" name="action" value="update_balance">
                    <div class="flex-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Entitled</label>
                        <input type="number" name="entitled_days" value="${bal.entitled_days}" class="w-full p-2 border border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:text-white rounded-lg">
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Used</label>
                        <input type="number" name="used_days" value="${bal.used_days}" class="w-full p-2 border border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:text-white rounded-lg">
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Remaining</label>
                        <input type="number" readonly value="${bal.remaining_days}" class="w-full p-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-500 rounded-lg cursor-not-allowed">
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-lg transition">
                        <i class="fas fa-save"></i>
                    </button>
                </form>
            </div>
        `;
    });
    
    $('#balancesList').html(html);
    $('#editModal').removeClass('hidden').addClass('flex');
}

function closeModal() {
    $('#editModal').addClass('hidden').removeClass('flex');
}

$(document).ready(function() {
    // Close modal on click outside
    $('#editModal').on('click', function(e) {
        if (e.target === this) closeModal();
    });
});
</script>

<?php require_once 'includes/foot.php'; ?>
