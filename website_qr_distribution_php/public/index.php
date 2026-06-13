<?php
$validAnswers = [
    'gamma',
	'gamma radiation',
	'gamma rad',

];

$stateFile = __DIR__ . '/../data/state.json';
$csvFile   = __DIR__ . '/../data/codes.csv';

function normalize($text)
{
    return mb_strtolower(trim($text), 'UTF-8');
}

function getNextQr($stateFile, $csvFile)
{
    $fp = fopen($stateFile, 'c+');

    if (!$fp) {
        throw new Exception("Cannot open state file.");
    }

    flock($fp, LOCK_EX);

    $content = stream_get_contents($fp);
    $state = json_decode($content, true);

    if (!$state) {
        $state = ['current_index' => 0];
    }

    $index = (int)$state['current_index'];

    $codes = [];

    if (($h = fopen($csvFile, 'r')) !== false) {

        fgetcsv($h);

        while (($row = fgetcsv($h)) !== false) {

            if ($row[0] === 'USER') {
                $codes[] = $row[1];
            }
        }

        fclose($h);
    }

    if ($index >= count($codes)) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return null;
    }

    $state['current_index'] = $index + 1;

    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($state));
    fflush($fp);

    flock($fp, LOCK_UN);
    fclose($fp);

    return [
    'number' => $index + 1,
    'code'   => $codes[$index],
    'file'   => sprintf("%03d.png", $index + 1)
    ];
}

$error = '';
$qrFile = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $answer = normalize($_POST['answer'] ?? '');

    $ok = false;

    foreach ($validAnswers as $valid) {
        if ($answer === normalize($valid)) {
            $ok = true;
            break;
        }
    }

    if (!$ok) {
        $error = 'Incorrect answer.';
    } else {
        $qrFile = getNextQr($stateFile, $csvFile);

        if (!$qrFile) {
            $error = 'No QR codes remaining.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Get Your Chocolate</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 20px;
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f5f5;
    color: #222;
}

.container {
    max-width: 700px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,.1);
}

h1, h2 {
    text-align: center;
    margin-top: 0;
}

.question {
    font-size: 1.2rem;
    line-height: 1.5;
    margin-bottom: 25px;
}

input[type=text] {
    width: 100%;
    padding: 18px;
    font-size: 20px;
    border: 2px solid #ccc;
    border-radius: 8px;
}

button {
    width: 100%;
    margin-top: 15px;
    padding: 18px;
    font-size: 22px;
    border: none;
    border-radius: 8px;
    background: #0a66c2;
    color: white;
    cursor: pointer;
}

button:hover {
    background: #084f97;
}

.error {
    color: #c00000;
    font-weight: bold;
    text-align: center;
    margin-top: 20px;
}

.success {
    text-align: center;
}

.success h2 {
    color: #008000;
}

.qr-code {
    width: 100%;
    max-width: 400px;
    height: auto;
    display: block;
    margin: 20px auto;
}

.code-number {
    font-size: 2rem;
    font-weight: bold;
    text-align: center;
    margin: 15px 0;
}

.download-btn {
    display: block;
    width: 100%;
    text-align: center;
    text-decoration: none;
    background: #28a745;
    color: white;
    padding: 18px;
    border-radius: 8px;
    font-size: 22px;
    font-weight: bold;
}

.download-btn:hover {
    background: #1f8135;
}

.admin-link {
    display: block;
    text-align: center;
    margin-top: 30px;
    color: #666;
    text-decoration: none;
}

@media (max-width: 600px) {

    .container {
        padding: 18px;
    }

    .question {
        font-size: 1.05rem;
    }

    button,
    .download-btn {
        font-size: 20px;
    }
}

</style>
</head>
<body>

<div class="container">

<?php if (!$qrFile): ?>

<h1>🍫 Get Your Chocolate</h1>

<div class="question">
What is the name of the most energetic form of electromagnetic radiation?
</div>

<form method="post">

    <input
        type="text"
        name="answer"
        autocomplete="off"
        autofocus
        required>

    <button type="submit">
        Submit Answer
    </button>

</form>

<?php endif; ?>

<?php if ($error): ?>
<div class="error">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<?php if ($qrFile): ?>

<div class="success">

    <h2>🎉 Congratulations!</h2>

    <p>
        You answered correctly and earned a chocolate.
    </p>

    <div class="code-number">
        <?= htmlspecialchars($qrFile['code']) ?>
    </div>

    <img
        class="qr-code"
        src="../qr_codes/<?= htmlspecialchars($qrFile['file']) ?>"
        alt="QR Code">

    <a
        class="download-btn"
        href="../qr_codes/<?= htmlspecialchars($qrFile['file']) ?>"
        download>
        Download QR Code
    </a>

</div>

<?php endif; ?>

<a class="admin-link" href="reset.php">
Admin Reset
</a>

</div>

</body>
</html>
