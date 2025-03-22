<?php
/**
 * Sai Chalam Academy - Application Form Processing
 * This script processes the student enrollment application form and sends an email notification
 */

// Set error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session for flash messages
session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Initialize response array
    $response = array(
        'success' => false,
        'message' => 'There was an error processing your application. Please try again.'
    );
    
    // Validate required fields
    $required_fields = array(
        'student_name', 'father_name', 'mother_name', 'aadhaar_no', 'gender', 'dob', 
        'religion', 'caste_category', 'door_no', 'city_village', 'state', 'district', 
        'pincode', 'mobile', 'email', 'ssc_board', 'ssc_year', 'ssc_percentage',
        'inter_board', 'inter_year', 'inter_percentage', 'degree_university', 
        'degree_year', 'degree_percentage', 'declaration'
    );
    
    $missing_fields = array();
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $response['message'] = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
        echo json_encode($response);
        exit;
    }
    
    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit;
    }
    
    // Validate Aadhaar number (12 digits)
    if (!preg_match('/^\d{12}$/', $_POST['aadhaar_no'])) {
        $response['message'] = 'Please enter a valid 12-digit Aadhaar number.';
        echo json_encode($response);
        exit;
    }
    
    // Validate phone number (10 digits)
    if (!preg_match('/^\d{10}$/', $_POST['mobile'])) {
        $response['message'] = 'Please enter a valid 10-digit mobile number.';
        echo json_encode($response);
        exit;
    }
    
    // Validate pincode (6 digits)
    if (!preg_match('/^\d{6}$/', $_POST['pincode'])) {
        $response['message'] = 'Please enter a valid 6-digit pincode.';
        echo json_encode($response);
        exit;
    }
    
    // Check if files were uploaded
    $required_files = array('photo', 'signature', 'aadhaar_card', 'degree_certificate');
    $missing_files = array();
    
    foreach ($required_files as $file) {
        if (!isset($_FILES[$file]) || $_FILES[$file]['error'] != UPLOAD_ERR_OK) {
            $missing_files[] = $file;
        }
    }
    
    if (!empty($missing_files)) {
        $response['message'] = 'Please upload all required documents: ' . implode(', ', $missing_files);
        echo json_encode($response);
        exit;
    }
    
    // Process file uploads
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $uploaded_files = array();
    
    foreach ($required_files as $file) {
        $file_ext = pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION);
        $new_filename = time() . '_' . $file . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        
        // Validate file size
        $max_size = ($file == 'photo' || $file == 'signature') ? 1048576 : 2097152; // 1MB or 2MB
        
        if ($_FILES[$file]['size'] > $max_size) {
            $response['message'] = 'File ' . $file . ' exceeds size limit. Please upload a smaller file.';
            echo json_encode($response);
            exit;
        }
        
        // Validate file type
        $allowed_types = array(
            'photo' => array('jpg', 'jpeg', 'png'),
            'signature' => array('jpg', 'jpeg', 'png'),
            'aadhaar_card' => array('jpg', 'jpeg', 'png', 'pdf'),
            'degree_certificate' => array('jpg', 'jpeg', 'png', 'pdf')
        );
        
        if (!in_array(strtolower($file_ext), $allowed_types[$file])) {
            $response['message'] = 'Invalid file type for ' . $file . '. Allowed types: ' . implode(', ', $allowed_types[$file]);
            echo json_encode($response);
            exit;
        }
        
        // Move uploaded file
        if (move_uploaded_file($_FILES[$file]['tmp_name'], $upload_path)) {
            $uploaded_files[$file] = $upload_path;
        } else {
            $response['message'] = 'Failed to upload ' . $file . '. Please try again.';
            echo json_encode($response);
            exit;
        }
    }
    
    // Prepare email content
    $to = 'projectsmail00@gmail.com'; // Replace with your email
    $subject = 'New B.Ed Application - ' . $_POST['student_name'];
    
    // Compose HTML email
    $message = '<html><body>';
    $message .= '<h2>New B.Ed Application Submission</h2>';
    $message .= '<h3>Applicant Details:</h3>';
    $message .= '<table style="border-collapse: collapse; width: 100%;" border="1" cellpadding="5">';
    $message .= '<tr><th style="text-align: left; width: 40%;">Field</th><th style="text-align: left; width: 60%;">Value</th></tr>';
    
    // Personal Information
    $message .= '<tr><th colspan="2" style="background-color: #f1f1f1;">Personal Information</th></tr>';
    $message .= '<tr><td>Name</td><td>' . htmlspecialchars($_POST['student_name']) . '</td></tr>';
    $message .= '<tr><td>Father\'s Name</td><td>' . htmlspecialchars($_POST['father_name']) . '</td></tr>';
    $message .= '<tr><td>Mother\'s Name</td><td>' . htmlspecialchars($_POST['mother_name']) . '</td></tr>';
    $message .= '<tr><td>Aadhaar Number</td><td>' . htmlspecialchars($_POST['aadhaar_no']) . '</td></tr>';
    $message .= '<tr><td>ABC ID</td><td>' . htmlspecialchars($_POST['abc_id']) . '</td></tr>';
    $message .= '<tr><td>Gender</td><td>' . htmlspecialchars($_POST['gender']) . '</td></tr>';
    $message .= '<tr><td>Date of Birth</td><td>' . htmlspecialchars($_POST['dob']) . '</td></tr>';
    $message .= '<tr><td>Religion</td><td>' . htmlspecialchars($_POST['religion']) . '</td></tr>';
    $message .= '<tr><td>Caste Category</td><td>' . htmlspecialchars($_POST['caste_category']) . '</td></tr>';
    
    // Method Information
    $message .= '<tr><th colspan="2" style="background-color: #f1f1f1;">Method Information</th></tr>';
    $message .= '<tr><td>Method I</td><td>' . htmlspecialchars($_POST['method_1']) . '</td></tr>';
    $message .= '<tr><td>Method II</td><td>' . htmlspecialchars($_POST['method_2']) . '</td></tr>';
    
    // Contact Information
    $message .= '<tr><th colspan="2" style="background-color: #f1f1f1;">Contact Information</th></tr>';
    $message .= '<tr><td>Door No</td><td>' . htmlspecialchars($_POST['door_no']) . '</td></tr>';
    $message .= '<tr><td>City/Town/Village</td><td>' . htmlspecialchars($_POST['city_village']) . '</td></tr>';
    $message .= '<tr><td>State</td><td>' . htmlspecialchars($_POST['state']) . '</td></tr>';
    $message .= '<tr><td>District</td><td>' . htmlspecialchars($_POST['district']) . '</td></tr>';
    $message .= '<tr><td>Pincode</td><td>' . htmlspecialchars($_POST['pincode']) . '</td></tr>';
    $message .= '<tr><td>Mobile</td><td>' . htmlspecialchars($_POST['mobile']) . '</td></tr>';
    $message .= '<tr><td>Email</td><td>' . htmlspecialchars($_POST['email']) . '</td></tr>';
    
    // Academic Information
    $message .= '<tr><th colspan="2" style="background-color: #f1f1f1;">Academic Information</th></tr>';
    $message .= '<tr><td>SSC/Matric Board</td><td>' . htmlspecialchars($_POST['ssc_board']) . '</td></tr>';
    $message .= '<tr><td>SSC/Matric Year</td><td>' . htmlspecialchars($_POST['ssc_year']) . '</td></tr>';
    $message .= '<tr><td>SSC/Matric Percentage</td><td>' . htmlspecialchars($_POST['ssc_percentage']) . '</td></tr>';
    $message .= '<tr><td>Inter/+2 Board</td><td>' . htmlspecialchars($_POST['inter_board']) . '</td></tr>';
    $message .= '<tr><td>Inter/+2 Year</td><td>' . htmlspecialchars($_POST['inter_year']) . '</td></tr>';
    $message .= '<tr><td>Inter/+2 Percentage</td><td>' . htmlspecialchars($_POST['inter_percentage']) . '</td></tr>';
    $message .= '<tr><td>Degree University</td><td>' . htmlspecialchars($_POST['degree_university']) . '</td></tr>';
    $message .= '<tr><td>Degree Year</td><td>' . htmlspecialchars($_POST['degree_year']) . '</td></tr>';
    $message .= '<tr><td>Degree Percentage</td><td>' . htmlspecialchars($_POST['degree_percentage']) . '</td></tr>';
    
    $message .= '</table>';
    
    $message .= '<p><strong>Note:</strong> Uploaded documents are available in the following paths:</p>';
    $message .= '<ul>';
    foreach ($uploaded_files as $file_name => $file_path) {
        $message .= '<li>' . ucfirst($file_name) . ': ' . $file_path . '</li>';
    }
    $message .= '</ul>';
    
    $message .= '<p><strong>Declaration:</strong> ' . ($_POST['declaration'] ? 'Accepted' : 'Not Accepted') . '</p>';
    $message .= '<p><strong>Application Date:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    $message .= '</body></html>';
    
    // Set email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . $_POST['email'] . "\r\n";
    $headers .= 'Reply-To: ' . $_POST['email'] . "\r\n";
    
    // Send email
    $mail_sent = mail($to, $subject, $message, $headers);
    
    if ($mail_sent) {
        $response['success'] = true;
        $response['message'] = 'Application submitted successfully! We will contact you shortly.';
        
        // Log application details to a file
        $log_file = 'applications.log';
        $log_entry = date('Y-m-d H:i:s') . ' | ' . $_POST['student_name'] . ' | ' . $_POST['email'] . ' | ' . $_POST['mobile'] . "\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        // Redirect with success message
        $_SESSION['application_status'] = 'success';
        $_SESSION['application_message'] = $response['message'];
        header('Location: admissions.html#enrollment-form');
        exit;
    } else {
        $response['message'] = 'Failed to send email notification. Your application data has been saved. Please contact us directly.';
        
        // Redirect with error message
        $_SESSION['application_status'] = 'error';
        $_SESSION['application_message'] = $response['message'];
        header('Location: admissions.html#enrollment-form');
        exit;
    }
} else {
    // If not a POST request, redirect to the application form
    header('Location: admissions.html');
    exit;
}
?>