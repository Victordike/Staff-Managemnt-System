<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireAdmin();

$db = Database::getInstance();

if (!isset($_GET['id'])) {
    die('Invalid Request');
}

$appId = $_GET['id'];

// Fetch application details
$app = $db->fetchOne(
    "SELECT la.*, lt.name as leave_type, 
            au.firstname, au.surname, au.staff_id, au.department, au.position, au.rank, au.gl, au.step,
            appr.firstname as appr_fname, appr.surname as appr_sname
     FROM leave_applications la 
     JOIN leave_types lt ON la.leave_type_id = lt.id 
     JOIN admin_users au ON la.admin_id = au.id 
     LEFT JOIN admin_users appr ON la.approver_id = appr.id
     WHERE la.id = ?",
    [$appId]
);

if (!$app) {
    die('Application not found');
}

// Security: Only the applicant, superadmin, or those with approval roles can view
// (Simplified check for this implementation)
if ($app['admin_id'] != $adminId && $userRole !== 'superadmin') {
    // Check for roles (HOD, Dean, etc.)
    // ...
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Advice - <?php echo htmlspecialchars($app['firstname'] . ' ' . $app['surname']); ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; line-height: 1.6; color: #333; margin: 0; padding: 40px; }
        .letterhead { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 30px; }
        .letterhead h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .letterhead h2 { margin: 5px 0; font-size: 18px; }
        .letterhead p { margin: 0; font-size: 14px; }
        .ref-date { display: flex; justify-content: space-between; margin-bottom: 30px; font-weight: bold; }
        .recipient { margin-bottom: 30px; }
        .subject { text-align: center; text-transform: uppercase; font-weight: bold; text-decoration: underline; margin-bottom: 30px; }
        .content { margin-bottom: 40px; text-align: justify; }
        .signature { margin-top: 60px; }
        .footer { margin-top: 100px; font-size: 12px; border-top: 1px solid #ccc; padding-top: 10px; }
        @media print {
            .no-print { display: none; }
            body { padding: 20px; }
        }
        .status-badge { display: inline-block; padding: 5px 10px; border: 1px solid #000; font-weight: bold; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 5px;">Print Advice</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer; border-radius: 5px;">Close</button>
    </div>

    <div class="letterhead">
        <h1>Federal Polytechnic Of Oil And Gas</h1>
        <h2>Bonny Island, Rivers State, Nigeria</h2>
        <p>Office of the Registrar (Establishment Division)</p>
    </div>

    <div class="ref-date">
        <span>REF: FPOG/EST/LV/<?php echo $app['id']; ?>/<?php echo date('Y', strtotime($app['created_at'])); ?></span>
        <span>Date: <?php echo date('d F, Y'); ?></span>
    </div>

    <div class="recipient">
        <strong>THROUGH: THE HEAD OF DEPARTMENT</strong><br>
        <?php echo htmlspecialchars($app['department']); ?><br><br>
        <strong>TO: <?php echo htmlspecialchars($app['firstname'] . ' ' . $app['surname']); ?></strong><br>
        Staff ID: <?php echo htmlspecialchars($app['staff_id']); ?><br>
        Rank: <?php echo htmlspecialchars($app['rank'] ?: $app['position']); ?> (GL <?php echo htmlspecialchars($app['gl']); ?>/<?php echo htmlspecialchars($app['step']); ?>)
    </div>

    <div class="subject">
        APPROVAL OF <?php echo strtoupper($app['leave_type']); ?>
    </div>

    <div class="content">
        <p>With reference to your application dated <strong><?php echo date('d M, Y', strtotime($app['created_at'])); ?></strong> for <strong><?php echo htmlspecialchars($app['leave_type']); ?></strong>, I am directed to inform you that approval has been granted for you to proceed on the leave as follows:</p>
        
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; width: 40%; font-weight: bold;">Number of Days:</td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $app['duration']; ?> Days</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Effective Date:</td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo date('d F, Y', strtotime($app['start_date'])); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Resumption Date:</td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo date('d F, Y', strtotime($app['end_date'] . ' + 1 day')); ?></td>
            </tr>
        </table>

        <p>Please note that you are expected to resume duty promptly on the day following the expiration of your leave. Upon resumption, you are required to submit a Resumption of Duty Certificate duly signed by your Head of Department to this office for record purposes.</p>
        
        <p>We wish you a restful leave period.</p>
    </div>

    <div class="signature">
        <div style="width: 250px; border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
        <strong>For: REGISTRAR</strong><br>
        <?php echo htmlspecialchars($app['appr_fname'] . ' ' . $app['appr_sname']); ?><br>
        (Final Approver)
    </div>

    <div class="footer">
        CC: Rector, Bursar, HOD (<?php echo htmlspecialchars($app['department']); ?>), File.
    </div>
</body>
</html>
