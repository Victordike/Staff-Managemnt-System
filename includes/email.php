<?php

class EmailService {
    private $from_email;
    private $from_name;
    
    public function __construct() {
        $this->from_email = getenv('SMTP_FROM_EMAIL') ?: 'noreply@fpog.edu.ng';
        $this->from_name = getenv('SMTP_FROM_NAME') ?: 'FPOG Staff Manager';
    }
    
    public function sendDocumentApprovedEmail($user_email, $user_name, $document_name, $approval_comments = '') {
        $subject = 'Document Approved - ' . htmlspecialchars($document_name);
        
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #4CAF50; color: white; padding: 20px; border-radius: 5px 5px 0 0;'>
                    <h2 style='margin: 0;'>Document Approved ✓</h2>
                </div>
                
                <div style='background-color: #f5f5f5; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px;'>
                    <p>Dear <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
                    
                    <p>Good news! Your document <strong>\"" . htmlspecialchars($document_name) . "\"</strong> has been successfully approved and is now complete.</p>
                    
                    <div style='background-color: #e8f5e9; padding: 15px; border-left: 4px solid #4CAF50; margin: 15px 0;'>
                        <p style='margin: 0;'><strong>Status:</strong> Final Approval Completed</p>
                    </div>
        ";
        
        if (!empty($approval_comments)) {
            $body .= "
                    <div style='background-color: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 15px 0;'>
                        <p style='margin: 0; font-weight: bold; color: #1976D2;'>Comments from Registrar:</p>
                        <p style='margin: 10px 0 0 0; color: #333;'>" . nl2br(htmlspecialchars($approval_comments)) . "</p>
                    </div>
            ";
        }
        
        $body .= "
                    <p>You can view your document and its details by logging into the system.</p>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    
                    <p style='font-size: 12px; color: #666;'>
                        This is an automated email. Please do not reply to this message. If you have any questions, contact the administration.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->send($user_email, $subject, $body);
    }
    
    public function sendDocumentRejectedEmail($user_email, $user_name, $document_name, $rejection_reason, $rejection_comments = '') {
        $subject = 'Document Requires Revision - ' . htmlspecialchars($document_name);
        
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #f44336; color: white; padding: 20px; border-radius: 5px 5px 0 0;'>
                    <h2 style='margin: 0;'>Document Review - Action Required</h2>
                </div>
                
                <div style='background-color: #f5f5f5; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px;'>
                    <p>Dear <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
                    
                    <p>Your document <strong>\"" . htmlspecialchars($document_name) . "\"</strong> requires revision before it can be approved.</p>
                    
                    <div style='background-color: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin: 15px 0;'>
                        <p style='margin: 0; font-weight: bold; color: #c62828;'>Reason for Rejection:</p>
                        <p style='margin: 10px 0 0 0; color: #333;'>" . nl2br(htmlspecialchars($rejection_reason)) . "</p>
                    </div>
        ";
        
        if (!empty($rejection_comments)) {
            $body .= "
                    <div style='background-color: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 15px 0;'>
                        <p style='margin: 0; font-weight: bold; color: #1976D2;'>Additional Feedback:</p>
                        <p style='margin: 10px 0 0 0; color: #333;'>" . nl2br(htmlspecialchars($rejection_comments)) . "</p>
                    </div>
            ";
        }
        
        $body .= "
                    <div style='background-color: #fffde7; padding: 15px; border-left: 4px solid #FBC02D; margin: 15px 0;'>
                        <p style='margin: 0; font-weight: bold; color: #F57F17;'>What to do next:</p>
                        <ol style='margin: 10px 0 0 0; padding-left: 20px; color: #333;'>
                            <li>Review the feedback carefully</li>
                            <li>Make the necessary corrections</li>
                            <li>Resubmit your document through the portal</li>
                        </ol>
                    </div>
                    
                    <p>You can resubmit your document by logging into the system.</p>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    
                    <p style='font-size: 12px; color: #666;'>
                        This is an automated email. Please do not reply to this message. If you have any questions, contact the administration.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->send($user_email, $subject, $body);
    }
    
    private function send($to_email, $subject, $body) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: " . $this->from_name . " <" . $this->from_email . ">" . "\r\n";
        $headers .= "Reply-To: " . $this->from_email . "\r\n";
        
        try {
            $result = mail($to_email, $subject, $body, $headers);
            
            if (!$result) {
                error_log("Email failed to send to: " . $to_email);
                return false;
            }
            
            error_log("Email sent successfully to: " . $to_email);
            return true;
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
}
?>
