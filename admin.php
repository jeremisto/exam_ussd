<?php
require 'db.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$perPage = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$startAt = ($page - 1) * $perPage;

$statusFilter = $_GET['status'] ?? '';
$searchRegno = $_GET['search'] ?? '';

$params = [];
$where = "";

if ($statusFilter && in_array($statusFilter, ['pending', 'under review', 'resolved'])) {
    $where .= " AND a.status = ?";
    $params[] = $statusFilter;
}

if ($searchRegno) {
    $where .= " AND s.regno LIKE ?";
    $params[] = "%$searchRegno%";
}

$sql = "SELECT a.id, s.name AS student_name, s.regno, m.module_name, a.reason, a.status, mk.mark
        FROM appeals a 
        JOIN students s ON a.student_regno = s.regno 
        JOIN modules m ON a.module_id = m.id 
        LEFT JOIN marks mk ON mk.student_regno = s.regno AND mk.module_id = m.id
        WHERE 1 $where
        LIMIT $startAt, $perPage";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appeals = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT COUNT(*) 
                            FROM appeals a 
                            JOIN students s ON a.student_regno = s.regno 
                            WHERE 1 $where");
$countStmt->execute($params);
$totalAppeals = $countStmt->fetchColumn();
$totalPages = ceil($totalAppeals / $perPage);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Appeals Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background-color: #eef2f7;
            color: #333;
        }

        header {
            background-color: #0061a8;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            margin: 0;
        }

        .container {
            padding: 30px;
        }

        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        input[type="text"], select, button {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button {
            background-color: #0061a8;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #004f86;
        }

        table {
            width: 100%;
            background-color: white;
            border-collapse: collapse;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 700;
        }

        td form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pagination {
            margin-top: 25px;
            text-align: center;
        }

        .pagination a {
            padding: 8px 14px;
            margin: 0 5px;
            background-color: #dbe4ed;
            color: #333;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
        }

        .pagination a:hover {
            background-color: #bcccdc;
        }
    </style>
</head>
<body>

<header>
    <h1><center>Appeals Dashboard</h1></center>
    <div>
        <span>Welcome</span>
    </div>
</header>

<div class="container">

    <table>
        <tr>
            <th>#</th>
            <th>Student</th>
            <th>Module</th>
            <th>Marks</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($appeals as $i => $a): ?>
        <tr>
            <td><?= $i+1 + $startAt ?></td>
            <td><?= htmlspecialchars($a['student_name']) ?> (<?= $a['regno'] ?>)</td>
            <td><?= htmlspecialchars($a['module_name']) ?></td>
            <td><?= is_numeric($a['mark']) ? $a['mark'] : 'N/A' ?></td>
            <td><?= htmlspecialchars($a['reason']) ?></td>
            <td><?= ucfirst($a['status']) ?></td>
            <td>
                <form method="post" action="update_status.php">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <select name="status">
                        <option <?= $a['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option <?= $a['status'] == 'under review' ? 'selected' : '' ?>>Rejected</option>
                        <option <?= $a['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    </select>
                    <button type="submit">Update</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($searchRegno) ?>">Page <?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

</body>
</html>
