<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$db = Database::getInstance();
$adminId = $_SESSION['admin_id'];
$currentYear = date('Y');

// Fetch user info including gender
$userInfo = $db->fetchOne("SELECT sex FROM admin_users WHERE id = ?", [$adminId]);
$userGender = $userInfo['sex'] ?? 'Any';

// Fetch user balances for the current year
$userBalancesRaw = $db->fetchAll("
    SELECT lb.*, lt.name as leave_type, lt.gender as type_gender 
    FROM leave_balances lb 
    JOIN leave_types lt ON lb.leave_type_id = lt.id 
    WHERE lb.admin_id = ? AND lb.year = ?
", [$adminId, $currentYear]);

// Filter balances by gender
$userBalances = array_filter($userBalancesRaw, function($bal) use ($userGender) {
    $typeGender = $bal['type_gender'];
    if ($typeGender === 'Female' && strtolower($userGender) !== 'female') return false;
    if ($typeGender === 'Male' && strtolower($userGender) !== 'male') return false;
    return true;
});

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveTypeId = $_POST['leave_type_id'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $reason = $_POST['reason'];
    
    // Calculate duration using central function
    $duration = calculateLeaveDuration($startDate, $endDate, $db);
    
    if ($duration <= 0) {
        setFlashMessage('End date must be after start date', 'error');
    } else {
        // Check balance if leave type has a limit (Study Leave and Examination Leave might be 0/unlimited in setup)
        $balance = $db->fetchOne("SELECT * FROM leave_balances WHERE admin_id = ? AND leave_type_id = ? AND year = ?", [$adminId, $leaveTypeId, $currentYear]);
        
        $canProceed = true;
        if ($balance) {
            if ($duration > $balance['remaining_days']) {
                setFlashMessage("Insufficient leave balance. You have only {$balance['remaining_days']} days remaining for this leave type.", 'error');
                $canProceed = false;
            }
        } else {
            // Check if it's one of the types with 0 default days (Study/Exam)
            $typeInfo = $db->fetchOne("SELECT * FROM leave_types WHERE id = ?", [$leaveTypeId]);
            if ($typeInfo && $typeInfo['default_days'] > 0) {
                setFlashMessage('Your leave balance has not been initialized for this year. Please contact the Establishment division.', 'error');
                $canProceed = false;
            }
        }

        if ($canProceed) {
            try {
                $db->query(
                    "INSERT INTO leave_applications (admin_id, leave_type_id, start_date, end_date, duration, reason, status) 
                     VALUES (?, ?, ?, ?, ?, ?, 'pending')",
                    [$adminId, $leaveTypeId, $startDate, $endDate, $duration, $reason]
                );
                setFlashMessage('Leave application submitted successfully', 'success');
                header('Location: leave_history.php');
                exit;
            } catch (Exception $e) {
                setFlashMessage('Error submitting application: ' . $e->getMessage(), 'error');
            }
        }
    }
}

$pageTitle = 'Apply for Leave';
require_once 'includes/head.php';

// Fetch leave types - Filter by gender
$allLeaveTypes = $db->fetchAll("SELECT * FROM leave_types WHERE is_active = TRUE");
$leaveTypes = array_filter($allLeaveTypes, function($type) use ($userGender) {
    $typeGender = $type['gender'];
    if ($typeGender === 'Female' && strtolower($userGender) !== 'female') return false;
    if ($typeGender === 'Male' && strtolower($userGender) !== 'male') return false;
    return true;
});

// Fetch holidays for JS
$holidaysForJS = $db->fetchAll("SELECT holiday_date FROM holidays WHERE year IN (?, ?)", [$currentYear, $currentYear + 1]);
$holidayDates = array_column($holidaysForJS, 'holiday_date');
?>

<div class="max-w-5xl mx-auto space-y-6">
    <!-- Leave Balances Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <?php if (empty($userBalances)): ?>
            <div class="col-span-full bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Your leave balances for <strong><?php echo $currentYear; ?></strong> have not been initialized. 
                            Please contact the Establishment division to set up your entitlements.
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($userBalances as $bal): ?>
                <div class="card p-4 flex flex-col items-center justify-center border-t-4 border-blue-500">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest text-center"><?php echo htmlspecialchars($bal['leave_type']); ?></p>
                    <p class="text-2xl font-black text-gray-800 dark:text-white my-1"><?php echo $bal['remaining_days']; ?></p>
                    <p class="text-[10px] text-gray-500">Days Remaining</p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card shadow-lg border-0 rounded-xl overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-paper-plane mr-3"></i>New Leave Application
            </h2>
            <p class="text-blue-100 mt-1">Please fill in the details below to request for leave.</p>
        </div>
        
        <form method="POST" class="p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Leave Type -->
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Type of Leave</label>
                    <select name="leave_type_id" id="leave_type_id" required class="w-full p-3 rounded-lg border border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">Select Leave Type</option>
                        <?php foreach ($leaveTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>">
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Blank for spacing -->
                <div></div>
                
                <!-- Start Date -->
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Start Date</label>
                    <input type="date" name="start_date" required id="startDate" class="w-full p-3 rounded-lg border border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 transition">
                </div>
                
                <!-- End Date -->
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">End Date</label>
                    <input type="date" name="end_date" required id="endDate" class="w-full p-3 rounded-lg border border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>
            
            <!-- Duration Info -->
            <div id="durationInfo" class="hidden p-4 bg-blue-50 dark:bg-blue-900 dark:bg-opacity-20 rounded-lg border border-blue-100 dark:border-blue-800">
                <div class="flex justify-between items-center">
                    <p class="text-blue-800 dark:text-blue-300 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        Total Duration: <span id="durationDays" class="font-bold mx-1">0</span> days
                    </p>
                    <p id="balanceWarning" class="hidden text-red-600 font-bold text-sm">
                        <i class="fas fa-exclamation-circle mr-1"></i> Insufficient balance!
                    </p>
                </div>
            </div>
            
            <!-- Reason -->
            <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Reason for Leave</label>
                <textarea name="reason" required rows="4" class="w-full p-3 rounded-lg border border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 transition" placeholder="Please provide a brief reason for your leave request..."></textarea>
            </div>
            
            <!-- Submit Button -->
            <div class="flex justify-end pt-4">
                <button type="submit" id="submitBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-1">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Application
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    const balances = <?php echo json_encode(array_column($userBalances, 'remaining_days', 'leave_type_id')); ?>;
    const holidays = <?php echo json_encode($holidayDates); ?>;
    
    function calculateDuration() {
        const start = $('#startDate').val();
        const end = $('#endDate').val();
        const leaveTypeId = $('#leave_type_id').val();
        
        if (start && end) {
            const startDate = new Date(start);
            const endDate = new Date(end);
            let diffDays = 0;
            let current = new Date(startDate);
            
            while (current <= endDate) {
                const dayOfWeek = current.getDay();
                const dateStr = current.toISOString().split('T')[0];
                
                if (dayOfWeek !== 0 && dayOfWeek !== 6 && !holidays.includes(dateStr)) { // Not Sunday (0) or Saturday (6) and not a holiday
                    diffDays++;
                }
                current.setDate(current.getDate() + 1);
            }
            
            if (diffDays > 0) {
                $('#durationDays').text(diffDays);
                $('#durationInfo').removeClass('hidden');
                
                // Check against balance if selected
                if (leaveTypeId && balances[leaveTypeId] !== undefined) {
                    const remaining = parseInt(balances[leaveTypeId]);
                    if (diffDays > remaining) {
                        $('#balanceWarning').removeClass('hidden');
                        $('#submitBtn').attr('disabled', true).addClass('opacity-50 cursor-not-allowed');
                    } else {
                        $('#balanceWarning').addClass('hidden');
                        $('#submitBtn').attr('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                    }
                } else {
                    $('#balanceWarning').addClass('hidden');
                    $('#submitBtn').attr('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                }
            } else {
                $('#durationInfo').addClass('hidden');
            }
        }
    }
    
    $('#startDate, #endDate, #leave_type_id').on('change', calculateDuration);
});
</script>

<?php require_once 'includes/foot.php'; ?>
