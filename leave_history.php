<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$pageTitle = 'Leave History';
require_once 'includes/head.php';

$db = Database::getInstance();
$adminId = $_SESSION['admin_id'];

// Real-time check to complete any ended leave
checkAndCompleteLeave($adminId);

// Fetch leave applications
$applications = $db->fetchAll(
    "SELECT la.*, lt.name as leave_type 
     FROM leave_applications la 
     JOIN leave_types lt ON la.leave_type_id = lt.id 
     WHERE la.admin_id = ? 
     ORDER BY la.created_at DESC",
    [$adminId]
);

$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'hod_recommended' => 'bg-blue-100 text-blue-800 border-blue-200',
    'hod_rejected' => 'bg-red-100 text-red-800 border-red-200',
    'dean_cleared' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
    'dean_rejected' => 'bg-red-100 text-red-800 border-red-200',
    'establishment_verified' => 'bg-purple-100 text-purple-800 border-purple-200',
    'establishment_rejected' => 'bg-red-100 text-red-800 border-red-200',
    'approved' => 'bg-green-100 text-green-800 border-green-200',
    'rejected' => 'bg-red-100 text-red-800 border-red-200',
    'completed' => 'bg-gray-100 text-gray-800 border-gray-200'
];
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Leave History</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Track the status of your leave applications</p>
    </div>
    <a href="apply_leave.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition flex items-center">
        <i class="fas fa-plus mr-2"></i>Apply for Leave
    </a>
</div>

<div class="card shadow-md overflow-hidden border-0">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Leave Type</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Submitted</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-folder-open text-4xl mb-3 block opacity-20"></i>
                            No leave applications found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-semibold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($app['leave_type']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded-md text-sm font-bold border border-blue-100">
                                    <?php echo $app['duration']; ?> Days
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex flex-col">
                                    <span>From: <?php echo date('M j, Y', strtotime($app['start_date'])); ?></span>
                                    <span>To: <?php echo date('M j, Y', strtotime($app['end_date'])); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 rounded-full text-xs font-bold border <?php echo $statusColors[$app['status']] ?? 'bg-gray-100'; ?>">
                                    <?php echo strtoupper(str_replace('_', ' ', $app['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo date('M j, Y', strtotime($app['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex gap-2">
                                    <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 font-bold" onclick="viewDetails(<?php echo $app['id']; ?>)">
                                        <i class="fas fa-eye mr-1"></i>Details
                                    </button>
                                    <?php if ($app['status'] === 'approved' || $app['status'] === 'completed'): ?>
                                        <a href="view_leave_advice.php?id=<?php echo $app['id']; ?>" target="_blank" class="text-green-600 hover:text-green-900 font-bold">
                                            <i class="fas fa-file-pdf mr-1"></i>Advice
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-8 max-w-2xl w-full mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Leave Application Details</h3>
            <button onclick="closeDetails()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="detailsContent" class="space-y-6">
            <!-- Dynamically populated -->
        </div>
        
        <div class="mt-8 flex justify-end">
            <button onclick="closeDetails()" class="px-6 py-2 bg-gray-200 text-gray-800 rounded-lg font-bold hover:bg-gray-300 transition">Close</button>
        </div>
    </div>
</div>

<script>
const applications = <?php echo json_encode($applications); ?>;

function viewDetails(id) {
    const app = applications.find(a => a.id == id);
    if (!app) return;
    
    const statusMap = {
        'pending': { text: 'Pending HOD Recommendation', color: 'text-yellow-600 bg-yellow-50' },
        'hod_recommended': { text: 'HOD Recommended - Awaiting Dean Clearance', color: 'text-blue-600 bg-blue-50' },
        'hod_rejected': { text: 'Rejected by HOD', color: 'text-red-600 bg-red-50' },
        'dean_cleared': { text: 'Dean Cleared - Awaiting Establishment Verification', color: 'text-indigo-600 bg-indigo-50' },
        'dean_rejected': { text: 'Rejected by Dean', color: 'text-red-600 bg-red-50' },
        'establishment_verified': { text: 'Verified by Establishment - Awaiting Final Approval', color: 'text-purple-600 bg-purple-50' },
        'establishment_rejected': { text: 'Rejected by Establishment', color: 'text-red-600 bg-red-50' },
        'approved': { text: 'Fully Approved', color: 'text-green-600 bg-green-50' },
        'rejected': { text: 'Rejected', color: 'text-red-600 bg-red-50' },
        'completed': { text: 'Completed', color: 'text-gray-600 bg-gray-50' }
    };
    
    const statusInfo = statusMap[app.status] || { text: app.status, color: 'text-gray-600 bg-gray-50' };
    
    let html = `
        <div class="grid grid-cols-2 gap-6">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Leave Type</p>
                <p class="text-lg font-bold text-gray-800 dark:text-white">${app.leave_type}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Status</p>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-black ${statusInfo.color} border border-current">${statusInfo.text.toUpperCase()}</span>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Duration</p>
                <p class="text-gray-800 dark:text-gray-200 font-bold">${app.duration} Days</p>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Period</p>
                <p class="text-gray-800 dark:text-gray-200 font-medium">${app.start_date} to ${app.end_date}</p>
            </div>
        </div>
        
        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Reason</p>
            <p class="text-gray-700 dark:text-gray-300 italic p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">"${app.reason}"</p>
        </div>
        
        <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <h4 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-widest">Workflow Trace</h4>
            <div class="relative pl-8 space-y-6 before:content-[''] before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-gray-200 dark:before:bg-gray-700">
                <div class="relative">
                    <div class="absolute -left-[30px] w-6 h-6 rounded-full bg-green-500 flex items-center justify-center text-white text-[10px] shadow-sm"><i class="fas fa-check"></i></div>
                    <p class="text-xs font-black text-gray-800 dark:text-white">Submitted</p>
                    <p class="text-[10px] text-gray-500">${app.created_at}</p>
                </div>
                
                <div class="relative">
                    <div class="absolute -left-[30px] w-6 h-6 rounded-full ${app.hod_recommended_at ? 'bg-green-500' : 'bg-gray-200'} flex items-center justify-center text-white text-[10px] shadow-sm"><i class="fas ${app.hod_recommended_at ? 'fa-check' : 'fa-clock'}"></i></div>
                    <p class="text-xs font-black text-gray-800 dark:text-white">HOD Recommendation</p>
                    <p class="text-[10px] text-gray-500">${app.hod_recommended_at || 'Pending'}</p>
                    ${app.hod_remarks ? `<p class="text-[10px] text-gray-600 dark:text-gray-400 italic mt-1 bg-gray-50 dark:bg-gray-900 p-2 rounded">"${app.hod_remarks}"</p>` : ''}
                </div>
                
                <div class="relative">
                    <div class="absolute -left-[30px] w-6 h-6 rounded-full ${app.dean_cleared_at ? 'bg-green-500' : 'bg-gray-200'} flex items-center justify-center text-white text-[10px] shadow-sm"><i class="fas ${app.dean_cleared_at ? 'fa-check' : 'fa-clock'}"></i></div>
                    <p class="text-xs font-black text-gray-800 dark:text-white">Dean Clearance</p>
                    <p class="text-[10px] text-gray-500">${app.dean_cleared_at || 'Pending'}</p>
                    ${app.dean_remarks ? `<p class="text-[10px] text-gray-600 dark:text-gray-400 italic mt-1 bg-gray-50 dark:bg-gray-900 p-2 rounded">"${app.dean_remarks}"</p>` : ''}
                </div>
                
                <div class="relative">
                    <div class="absolute -left-[30px] w-6 h-6 rounded-full ${app.establishment_verified_at ? 'bg-green-500' : 'bg-gray-200'} flex items-center justify-center text-white text-[10px] shadow-sm"><i class="fas ${app.establishment_verified_at ? 'fa-check' : 'fa-clock'}"></i></div>
                    <p class="text-xs font-black text-gray-800 dark:text-white">Establishment Verification</p>
                    <p class="text-[10px] text-gray-500">${app.establishment_verified_at || 'Pending'}</p>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('detailsContent').innerHTML = html;
    document.getElementById('detailsModal').classList.remove('hidden');
}

function closeDetails() {
    document.getElementById('detailsModal').classList.add('hidden');
}

// Close on outside click
document.getElementById('detailsModal').addEventListener('click', function(e) {
    if (e.target === this) closeDetails();
});
</script>

<?php require_once 'includes/foot.php'; ?>
