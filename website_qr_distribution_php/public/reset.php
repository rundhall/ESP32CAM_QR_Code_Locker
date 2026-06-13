<?php

$resetCode = 'Reset123!5678'; // Change this to a secure code
$stateFile = __DIR__ . '/../data/state.json';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (trim($_POST['reset_code'] ?? '') === $resetCode) {

        file_put_contents(
            $stateFile,
            json_encode(['current_index' => 0])
        );

        $message = 'QR list reset.';

    } else {

        $message = 'Invalid reset code.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Reset</title>
</head>
<body>

<h2>Reset QR distribution</h2>

<form method="post">
    <input type="password" name="reset_code" required>
    <button type="submit">Reset</button>
</form>

<p><?= htmlspecialchars($message) ?></p>

<p><a href="index.php">Back</a></p>

</body>
</html>
