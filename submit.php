<?php
header('Content-Type: application/json');

$errors = [];
$values = [];

// Validation functions
function validateText($value) { 
    return !empty(trim($value)); 
}
function validateEmail($value) { 
    return filter_var($value, FILTER_VALIDATE_EMAIL); 
}
function validatePassword($value) { 
    return preg_match("/^(?=.*[0-9]).{6,}$/", $value); 
}
function validatePhone($value) {
    // Must be exactly 10 digits
    return preg_match("/^\d{10}$/", trim($value));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get all hidden labels
    $labels = [];
    foreach ($_POST as $key => $val) {
        if (str_ends_with($key, "_label")) {
            $labels[str_replace("_label", "", $key)] = $val;
        }
    }

    // Loop through all submitted fields
    foreach ($labels as $field => $labelName) {
        $value = $_POST[$field] ?? null;
        $values[$field] = $value;

        // REQUIRED field check
        if (
            !isset($value) || 
            (is_array($value) && count(array_filter($value, fn($v) => trim($v) !== "")) === 0) || 
            (!is_array($value) && trim($value) === "")
        ) {
            $errors[$field] = "$labelName is required.";
            continue;
        }

        // Specific validations
        if (!is_array($value)) {
            switch (true) {
                case stripos($labelName, "email") !== false:
                    if (!validateEmail($value)) $errors[$field] = "Invalid email format!";
                    break;
                case stripos($labelName, "password") !== false:
                    if (!validatePassword($value)) $errors[$field] = "Password must be at least 6 chars & contain a number.";
                    break;
                case stripos($labelName, "phone") !== false:
                    if (!validatePhone($value)) $errors[$field] = "Phone number must be exactly 10 digits and contain only numbers!";
                    break;
            }
        }
    }

    // If no errors, save submission
    if (empty($errors)) {
        $data = "------ New Submission ------\n";
        foreach ($values as $k => $v) {
            $label = $labels[$k] ?? $k;
            if (is_array($v)) {
                $v = array_filter($v, fn($x) => trim($x) !== "");
                $v = implode(", ", $v);
            }
            $data .= "$label: $v\n";
        }
        file_put_contents("submissions.txt", $data, FILE_APPEND);
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "errors" => $errors]);
    }

} else {
    echo json_encode(["success" => false, "errors" => ["general" => "Invalid access."]]);
}
?>
