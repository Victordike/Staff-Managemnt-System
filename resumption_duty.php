<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$pageTitle = 'Resumption of Duty';
require_once 'includes/head.php';

$db = Database::getInstance();
$adminId = $_SESSION['admin_id'];

// Handle Resumption Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'])) {
    $appId = $_POST['application_id'];
    $resumptionDate = $_POST['resumption_date'];
    $remarks = $_POST['remarks'];
    
    try {
        // Validation: Resumption cannot be before start date
        $checkApp = $db->fetchOne("SELECT start_date FROM leave_applications WHERE id = ? AND admin_id = ?", [$appId, $adminId]);
        if ($checkApp && strtotime($resumptionDate) < strtotime($checkApp['start_date'])) {
            throw new Exception('Actual resumption date cannot be earlier than the leave start date (' . date('M j, Y', strtotime($checkApp['start_date'])) . ').');
        }

        $db->query(
            "UPDATE leave_applications SET resumption_date = ?, resumption_remarks = ?, resumption_submitted_at = NOW(), status = 'completed' WHERE id = ? AND admin_id = ?",
            [$resumptionDate, $remarks, $appId, $adminId]
        );
        setFlashMessage('Resumption of duty submitted successfully', 'success');
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
}

// Fetch approved or automatically completed applications that haven't been "resumed" manually
$activeLeaves = $db->fetchAll(
    "SELECT la.*, lt.name as leave_type 
     FROM leave_applications la 
     JOIN leave_types lt ON la.leave_type_id = lt.id 
     WHERE la.admin_id = ? 
     AND (la.status = 'approved' OR (la.status = 'completed' AND la.resumption_date IS NULL))
     ORDER BY la.end_date ASC",
    [$adminId]
);
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Resumption of Duty</h2>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Submit your resumption of duty certificate after returning from leave</p>
</div>

<div class="grid grid-cols-1 gap-6">
    <?php if (empty($activeLeaves)): ?>
        <div class="card p-10 text-center text-gray-500">
            <i class="fas fa-walking text-5xl mb-3 block opacity-20"></i>
            You have no active or recently approved leaves awaiting resumption.
        </div>
    <?php else: ?>
        <?php foreach ($activeLeaves as $app): ?>
            <?php 
            $today = date('Y-m-d');
            $hasStarted = strtotime($today) >= strtotime($app['start_date']);
            ?>
            <div class="card shadow-md border-l-4 <?php echo $hasStarted ? 'border-green-500' : 'border-blue-400'; ?> overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($app['leave_type']); ?></h3>
                            <p class="text-sm text-gray-500">Approved Period: <?php echo date('M j, Y', strtotime($app['start_date'])); ?> to <?php echo date('M j, Y', strtotime($app['end_date'])); ?></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if (!$hasStarted): ?>
                                <span class="bg-blue-100 text-blue-800 border-blue-200 px-3 py-1 rounded-full text-xs font-bold border uppercase">
                                    Upcoming
                                </span>
                            <?php endif; ?>
                            <div class="px-3 py-1 rounded-full text-xs font-bold border uppercase <?php echo $app['status'] === 'approved' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-blue-100 text-blue-800 border-blue-200'; ?>">
                                Status: <?php echo strtoupper($app['status']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($hasStarted): ?>
                        <form method="POST" class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-4">
                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Actual Resumption Date</label>
                                    <input type="date" name="resumption_date" required value="<?php echo date('Y-m-d'); ?>" min="<?php echo $app['start_date']; ?>" class="w-full p-2 rounded border border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Resumption Remarks</label>
                                    <input type="text" name="remarks" placeholder="e.g. Returned to duty as scheduled" class="w-full p-2 rounded border border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                                    <i class="fas fa-check-circle mr-2"></i>Submit Resumption
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800 rounded-lg">
                            <p class="text-blue-800 dark:text-blue-200 text-sm flex items-center">
                                <i class="fas fa-info-circle mr-3 text-xl"></i>
                                <span>This leave has not yet started. The resumption form will be available starting <strong><?php echo date('M j, Y', strtotime($app['start_date'])); ?></strong>.</span>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/foot.php'; ?>
