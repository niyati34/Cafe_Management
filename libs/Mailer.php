<?php
/**
 * Email Library for Food Chef Cafe Management System
 * Handles email sending for notifications, confirmations, and alerts
 */

class Mailer {
    
    private $fromEmail;
    private $fromName;
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    
    public function __construct($config = []) {
        $this->fromEmail = $config['from_email'] ?? 'noreply@foodchef.com';
        $this->fromName = $config['from_name'] ?? 'Food Chef Cafe';
        $this->smtpHost = $config['smtp_host'] ?? 'localhost';
        $this->smtpPort = $config['smtp_port'] ?? 587;
        $this->smtpUsername = $config['smtp_username'] ?? '';
        $this->smtpPassword = $config['smtp_password'] ?? '';
    }
    
    /**
     * Send email using PHP mail() function
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param array $headers
     * @return bool
     */
    public function sendMail($to, $subject, $message, $headers = []) {
        $defaultHeaders = [
            'From' => $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To' => $this->fromEmail,
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Mailer' => 'Food Chef Cafe Management System'
        ];
        
        $headers = array_merge($defaultHeaders, $headers);
        $headerString = '';
        
        foreach ($headers as $key => $value) {
            $headerString .= "$key: $value\r\n";
        }
        
        return mail($to, $subject, $message, $headerString);
    }
    
    /**
     * Send reservation confirmation email
     * @param array $reservation
     * @return bool
     */
    public function sendReservationConfirmation($reservation) {
        $subject = 'Reservation Confirmation - Food Chef Cafe';
        
        $message = $this->getReservationEmailTemplate($reservation);
        
        return $this->sendMail($reservation['email'], $subject, $message);
    }
    
    /**
     * Send reservation reminder email
     * @param array $reservation
     * @return bool
     */
    public function sendReservationReminder($reservation) {
        $subject = 'Reservation Reminder - Food Chef Cafe';
        
        $message = $this->getReminderEmailTemplate($reservation);
        
        return $this->sendMail($reservation['email'], $subject, $message);
    }
    
    /**
     * Send contact form notification
     * @param array $contactData
     * @return bool
     */
    public function sendContactNotification($contactData) {
        $subject = 'New Contact Message - Food Chef Cafe';
        
        $message = $this->getContactEmailTemplate($contactData);
        
        return $this->sendMail($this->fromEmail, $subject, $message);
    }
    
    /**
     * Send admin notification
     * @param string $subject
     * @param string $message
     * @param string $adminEmail
     * @return bool
     */
    public function sendAdminNotification($subject, $message, $adminEmail) {
        $fullMessage = $this->getAdminEmailTemplate($subject, $message);
        
        return $this->sendMail($adminEmail, $subject, $fullMessage);
    }
    
    /**
     * Get reservation confirmation email template
     * @param array $reservation
     * @return string
     */
    private function getReservationEmailTemplate($reservation) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Reservation Confirmation</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: #FF6B35; color: white; padding: 20px; text-align: center;'>
                    <h1>Food Chef Cafe</h1>
                    <p>Reservation Confirmation</p>
                </div>
                
                <div style='padding: 20px; background: #f9f9f9;'>
                    <h2>Hello {$reservation['name']},</h2>
                    
                    <p>Thank you for your reservation at Food Chef Cafe. Here are your reservation details:</p>
                    
                    <div style='background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #FF6B35;'>
                        <p><strong>Date:</strong> {$reservation['reservation_date']}</p>
                        <p><strong>Time:</strong> {$reservation['reservation_time']}</p>
                        <p><strong>Number of Guests:</strong> {$reservation['guests']}</p>
                        <p><strong>Reservation ID:</strong> #{$reservation['id']}</p>
                    </div>
                    
                    <p>We look forward to serving you!</p>
                    
                    <p>If you need to make any changes to your reservation, please contact us at least 24 hours in advance.</p>
                    
                    <p>Best regards,<br>
                    The Food Chef Team</p>
                </div>
                
                <div style='background: #2C3E50; color: white; padding: 20px; text-align: center;'>
                    <p>Food Chef Cafe<br>
                    123 Restaurant Street<br>
                    Phone: (555) 123-4567<br>
                    Email: info@foodchef.com</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get reservation reminder email template
     * @param array $reservation
     * @return string
     */
    private function getReminderEmailTemplate($reservation) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Reservation Reminder</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: #F7931E; color: white; padding: 20px; text-align: center;'>
                    <h1>Food Chef Cafe</h1>
                    <p>Reservation Reminder</p>
                </div>
                
                <div style='padding: 20px; background: #f9f9f9;'>
                    <h2>Hello {$reservation['name']},</h2>
                    
                    <p>This is a friendly reminder about your upcoming reservation:</p>
                    
                    <div style='background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #F7931E;'>
                        <p><strong>Date:</strong> {$reservation['reservation_date']}</p>
                        <p><strong>Time:</strong> {$reservation['reservation_time']}</p>
                        <p><strong>Number of Guests:</strong> {$reservation['guests']}</p>
                        <p><strong>Reservation ID:</strong> #{$reservation['id']}</p>
                    </div>
                    
                    <p>We're excited to welcome you to Food Chef Cafe!</p>
                    
                    <p>If you need to cancel or modify your reservation, please contact us as soon as possible.</p>
                    
                    <p>Best regards,<br>
                    The Food Chef Team</p>
                </div>
                
                <div style='background: #2C3E50; color: white; padding: 20px; text-align: center;'>
                    <p>Food Chef Cafe<br>
                    123 Restaurant Street<br>
                    Phone: (555) 123-4567<br>
                    Email: info@foodchef.com</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get contact form email template
     * @param array $contactData
     * @return string
     */
    private function getContactEmailTemplate($contactData) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>New Contact Message</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: #E74C3C; color: white; padding: 20px; text-align: center;'>
                    <h1>New Contact Message</h1>
                    <p>Food Chef Cafe Website</p>
                </div>
                
                <div style='padding: 20px; background: #f9f9f9;'>
                    <h2>Contact Details:</h2>
                    
                    <div style='background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #E74C3C;'>
                        <p><strong>Name:</strong> {$contactData['name']}</p>
                        <p><strong>Email:</strong> {$contactData['email']}</p>
                        <p><strong>Subject:</strong> {$contactData['subject']}</p>
                        <p><strong>Message:</strong></p>
                        <p style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>{$contactData['message']}</p>
                    </div>
                    
                    <p><strong>Received:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get admin email template
     * @param string $subject
     * @param string $message
     * @return string
     */
    private function getAdminEmailTemplate($subject, $message) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$subject}</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: #2C3E50; color: white; padding: 20px; text-align: center;'>
                    <h1>Food Chef Cafe</h1>
                    <p>Admin Notification</p>
                </div>
                
                <div style='padding: 20px; background: #f9f9f9;'>
                    <h2>{$subject}</h2>
                    
                    <div style='background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #2C3E50;'>
                        {$message}
                    </div>
                    
                    <p><strong>Sent:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Test email configuration
     * @param string $testEmail
     * @return bool
     */
    public function testEmail($testEmail) {
        $subject = 'Test Email - Food Chef Cafe';
        $message = 'This is a test email from Food Chef Cafe Management System.';
        
        return $this->sendMail($testEmail, $subject, $message);
    }
}
?>
