<?php
session_start();

$python = "python"; // or full path if needed
$script = escapeshellarg(__DIR__ . "/../python/run_scoring.py");

$output = shell_exec("$python $script 2>&1");

if ($output === null) {
    echo "❌ Error: Python execution failed";
} else {
    echo "✅ Success: Marks calculated successfully<br><pre>$output</pre>";
}
?>
