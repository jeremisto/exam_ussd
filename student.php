<?php
$sessionId   = $_POST["sessionId"];
$serviceCode = $_POST["serviceCode"];
$phoneNumber = $_POST["phoneNumber"];
$text        = $_POST["text"];

require 'db.php';

$inputArray = explode("*", $text);
$level = count($inputArray);

if ($text == "") {
    $response  = "CON Welcome to the Marks Appeal System\n";
    $response .= "1. Check my marks\n";
    $response .= "2. Appeal my marks\n";
    $response .= "3. Check appeal status\n";
    $response .= "4. Exit";
} else if ($inputArray[0] == "1") {
    if ($level == 1) {
        $response = "CON Enter your Student Reg number:";
    } else {
        $studentRegno = $inputArray[1];
        $stmt = $pdo->prepare("SELECT m.module_name, mk.mark FROM marks mk JOIN modules m ON mk.module_id = m.id WHERE mk.student_regno = ?");
        $stmt->execute([$studentRegno]);
        $marks = $stmt->fetchAll();

        if ($marks) {
            $response = "END Your Marks:\n";
            foreach ($marks as $m) {
                $response .= "{$m['module_name']}: {$m['mark']}\n";
            }
        } else {
            $response = "END Student Reg number not found. Please try again.";
        }
    }
} else if ($inputArray[0] == "2") {
    if ($level == 1) {
        $response = "CON Enter your Student Reg number:";
    } else if ($level == 2) {
        $studentRegno = $inputArray[1];
        $stmt = $pdo->prepare("SELECT m.module_name, mk.mark, m.id FROM marks mk JOIN modules m ON mk.module_id = m.id WHERE mk.student_regno = ?");
        $stmt->execute([$studentRegno]);
        $modules = $stmt->fetchAll();

        if ($modules) {
            $response = "CON Select the module to appeal:\n";
            foreach ($modules as $index => $mod) {
                $response .= ($index + 1) . ". {$mod['module_name']}: {$mod['mark']}\n";
            }
        } else {
            $response = "END Student Reg number not found.";
        }
    } else if ($level == 3) {
        $moduleIndex = (int)$inputArray[2] - 1;
        $studentRegno = $inputArray[1];
        $stmt = $pdo->prepare("SELECT m.module_name, mk.mark, m.id FROM marks mk JOIN modules m ON mk.module_id = m.id WHERE mk.student_regno = ?");
        $stmt->execute([$studentRegno]);
        $modules = $stmt->fetchAll();
        $selectedModule = $modules[$moduleIndex] ?? null;

        if ($selectedModule) {
            $response = "CON Enter the reason for your appeal:";
        } else {
            $response = "END Invalid module selection.";
        }
    } else if ($level == 4) {
        $studentRegno = $inputArray[1];
        $moduleIndex = (int)$inputArray[2] - 1;
        $reason = trim(end($inputArray));

        try {
            $stmt = $pdo->prepare("SELECT m.id FROM marks mk JOIN modules m ON mk.module_id = m.id WHERE mk.student_regno = ?");
            $stmt->execute([$studentRegno]);
            $modules = $stmt->fetchAll();

            if (isset($modules[$moduleIndex])) {
                $moduleId = $modules[$moduleIndex]['id'];
                $stmt = $pdo->prepare("INSERT INTO appeals(student_regno, module_id, reason, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$studentRegno, $moduleId, $reason]);

                $response = "END Thank you. Your appeal has been submitted.";
            } else {
                $response = "END Invalid module selected.";
            }
        } catch (Exception $e) {
            $response = "END Sorry, a system error occurred. Please try again.";
        }
    }
} else if ($inputArray[0] == "3") {
    if ($level == 1) {
        $response = "CON Enter your Student Reg number:";
    } else {
        $studentRegno = $inputArray[1];
        $stmt = $pdo->prepare("SELECT m.module_name, a.status FROM appeals a JOIN modules m ON a.module_id = m.id WHERE a.student_regno = ?");
        $stmt->execute([$studentRegno]);
        $appeals = $stmt->fetchAll();

        if ($appeals) {
            $response = "END Appeal Status:\n";
            foreach ($appeals as $a) {
                $response .= "{$a['module_name']}: {$a['status']}\n";
            }
        } else {
            $response = "END No appeals found for this student.";
        }
    }
} else if ($inputArray[0] == "4") {
    $response = "END Thank you for using the system.";
} else {
    $response = "END Invalid input. Please try again.";
}

header('Content-type: text/plain');
echo $response;
?>
