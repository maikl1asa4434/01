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

// Determine the base URL for the gate endpoint. Use https.
$protocol = 'https';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);
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
        height: 500px;
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
        // This is the new, robust script template
        return `try {
    # Force PowerShell to use the modern TLS 1.2 security protocol
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12

    # --- Configuration ---
    $GroupId = "${groupId}"
    $GateUrl = "${gateUrl}"

    # --- HWID Gathering with Fallback ---
    $Hwid = ""
    try {
        # Primary Method: Motherboard Serial Number
        $Hwid = (Get-CimInstance Win32_BaseBoard).SerialNumber.Trim()
    } catch {}

    if ([string]::IsNullOrWhiteSpace($Hwid)) {
        try {
            # Fallback Method: MAC Address of the first active, physical network adapter
            $adapter = Get-CimInstance Win32_NetworkAdapter | Where-Object { $_.NetConnectionStatus -eq 2 -and $_.PhysicalAdapter -eq $true } | Select-Object -First 1
            if ($adapter) {
                $Hwid = $adapter.MACAddress
            }
        } catch {}
    }

    # If HWID is still not found, script cannot proceed.
    if ([string]::IsNullOrWhiteSpace($Hwid)) {
        # In a real deployment, you might log this to a file.
        # Write-Error "FATAL: Could not determine a unique Hardware ID for this machine."
        return
    }

    # --- Computer Name Gathering with Fallback ---
    $ComputerName = $env:COMPUTERNAME
    if ([string]::IsNullOrWhiteSpace($ComputerName)) {
        $ComputerName = "Unknown-Computer"
    }

    # --- Prepare JSON Payload ---
    # Using JSON ensures special characters (e.g., non-English names) are handled correctly.
    $payload = @{
        group_id      = $GroupId
        hwid          = $Hwid
        computer_name = $ComputerName
    } | ConvertTo-Json -Compress

    $headers = @{
        "User-Agent" = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36"
    }

    # --- Send Data ---
    Invoke-RestMethod -Uri $GateUrl -Method Post -Headers $headers -Body $payload -ContentType 'application/json; charset=utf-8'

}
catch {
    # In a scheduled task, you might want to log errors to a file instead of the console.
    # For example:
    # "$([System.DateTime]::UtcNow.ToString('u')) - $($_.Exception.Message)" | Out-File -FilePath "C:\\path\\to\\error.log" -Append
}`;
    }
});
</script>

<?php
require_once __DIR__ . '/../app/partials/footer.php';
?>
