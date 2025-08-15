<?php
// public/generator.php

require_once __DIR__ . '/../app/session.php';
require_login();
require_once __DIR__ . '/../app/db.php';

// Fetch all existing groups to populate the dropdown
$groups = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Could not fetch groups: " . $e->getMessage();
}

// Determine the base URL for the gate endpoint
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);
// Ensure path is clean, replacing backslashes and removing trailing slash if it's not the root
$path = rtrim(str_replace('\\', '/', $path), '/');
$gateUrl = "{$protocol}://{$host}{$path}/gate.php";


require_once __DIR__ . '/../app/partials/header.php';
?>

<style>
    .generator-container {
        background-color: #fff;
        padding: 2rem;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
        max-width: 800px;
    }
    #group-select {
        padding: 0.5rem;
        min-width: 200px;
        margin-right: 1rem;
    }
    #script-output {
        width: 100%;
        height: 300px;
        margin-top: 1rem;
        font-family: monospace;
        font-size: 0.9rem;
        background-color: #e9ecef;
        border: 1px solid #ced4da;
        border-radius: 3px;
        padding: 1rem;
        white-space: pre;
        overflow-x: auto;
    }
    .copy-button {
        padding: 0.5rem 1rem;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        margin-top: 1rem;
        display: block;
    }
    .copy-button:hover {
        background-color: #0069d9;
    }
</style>

<h1>PowerShell Script Generator</h1>

<div class="generator-container">
    <p>Select a group to generate the PowerShell check-in script.</p>

    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php elseif (empty($groups)): ?>
        <p>No groups found. Please <a href="groups.php">create a group</a> before generating a script.</p>
    <?php else: ?>
        <div>
            <label for="group-select">Select Group:</label>
            <select id="group-select">
                <option value="">-- Please choose a group --</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?php echo htmlspecialchars($group['id']); ?>">
                        <?php echo htmlspecialchars($group['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <pre id="script-output">Select a group to see the script here.</pre>
        <button id="copy-btn" class="copy-button" style="display: none;">Copy Script to Clipboard</button>
        <span id="copy-feedback" style="display: none; color: green; margin-left: 1rem;">Copied!</span>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const groupSelect = document.getElementById('group-select');
    const scriptOutput = document.getElementById('script-output');
    const copyBtn = document.getElementById('copy-btn');
    const copyFeedback = document.getElementById('copy-feedback');

    if (!groupSelect) return;

    groupSelect.addEventListener('change', function() {
        const groupId = this.value;
        if (groupId) {
            const gateUrl = '<?php echo $gateUrl; ?>';
            const scriptContent = generateScript(groupId, gateUrl);
            scriptOutput.textContent = scriptContent;
            copyBtn.style.display = 'block';
            copyFeedback.style.display = 'none';
        } else {
            scriptOutput.textContent = 'Select a group to see the script here.';
            copyBtn.style.display = 'none';
        }
    });

    copyBtn.addEventListener('click', function() {
        navigator.clipboard.writeText(scriptOutput.textContent).then(function() {
            copyFeedback.style.display = 'inline';
            setTimeout(() => {
                copyFeedback.style.display = 'none';
            }, 2000);
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    });

    function generateScript(groupId, gateUrl) {
        return `# PowerShell Check-in Script
# Generated on ${new Date().toISOString()}

# --- CONFIGURATION ---
$GroupId = "${groupId}"
$GateUrl = "${gateUrl}"

# --- SCRIPT ---
try {
    # Get unique hardware ID from the motherboard serial number
    $Hwid = (Get-CimInstance Win32_BaseBoard).SerialNumber.Trim()

    # Get the computer name
    $ComputerName = $env:COMPUTERNAME

    # Prepare the data payload
    $payload = @{
        group_id      = $GroupId
        hwid          = $Hwid
        computer_name = $ComputerName
    }

    # Send the data to the gate endpoint
    Invoke-RestMethod -Uri $GateUrl -Method Post -Body $payload

    # Write-Host "Check-in successful for group $GroupId"
}
catch {
    # You can add error logging here if needed, for example:
    # "[$($_.Exception.GetType().FullName)] - $($_.Exception.Message)" | Out-File -FilePath "C:\\path\\to\\error.log" -Append
    # Write-Host "An error occurred during check-in: $_"
}
`;
    }
});
</script>

<?php
require_once __DIR__ . '/../app/partials/footer.php';
?>
