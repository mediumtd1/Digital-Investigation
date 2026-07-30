<?php
function analyze_email_header($email_header) {
    
    $lines = explode("\n", $email_header);

    
    $header_fields = [];

    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        
        if (preg_match("/^From:/i", $line)) {
            $header_fields['From'] = trim(substr($line, 5)); 
        } elseif (preg_match("/^To:/i", $line)) {
            $header_fields['To'] = trim(substr($line, 3)); 
        } elseif (preg_match("/^Subject:/i", $line)) {
            $header_fields['Subject'] = trim(substr($line, 8)); 
        } elseif (preg_match("/^Date:/i", $line)) {
            $header_fields['Date'] = trim(substr($line, 5)); 
        } elseif (preg_match("/^Received:/i", $line)) {
            
            $header_fields['Received'][] = trim(substr($line, 9)); 
        }
    }

    
    echo "<div class='result'>";
    echo "<h2>Email Header Analysis</h2>";

    if (isset($header_fields['From'])) {
        echo "<strong>From:</strong> " . htmlspecialchars($header_fields['From']) . "<br>";
    }
    if (isset($header_fields['To'])) {
        echo "<strong>To:</strong> " . htmlspecialchars($header_fields['To']) . "<br>";
    }
    if (isset($header_fields['Subject'])) {
        echo "<strong>Subject:</strong> " . htmlspecialchars($header_fields['Subject']) . "<br>";
    }
    if (isset($header_fields['Date'])) {
        echo "<strong>Date:</strong> " . htmlspecialchars($header_fields['Date']) . "<br>";
    }

    if (isset($header_fields['Received'])) {
        echo "<strong>Received:</strong><br>";
        foreach ($header_fields['Received'] as $received) {
            echo htmlspecialchars($received) . "<br>";
        }
    }

    echo "</div>";
}
?>
