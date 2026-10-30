<?php ob_start(); ?>
<div class="content-body" style="padding: 24px; max-width: 1040px; margin: 0 auto;">

    <!-- Minimal Header Row -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: #2563eb; font-size: 28px;">cloud_sync</span>
                    Software Updates
                </h1>
                <span id="header-version-badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 12px; font-weight: 700; padding: 2px 10px; border-radius: 999px;">
                    v<?= htmlspecialchars($currentVersion) ?>
                </span>
            </div>
            <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">
                Check for official application releases, security updates, and performance patches.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <button type="button" id="btn-manual-check" onclick="triggerUpdateCheck()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; font-weight: 700; font-size: 13px; border-radius: 12px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);">
                <span class="material-symbols-rounded" id="btn-check-icon" style="font-size: 18px;">sync</span>
                <span id="btn-check-text">Check for Updates</span>
            </button>
            <a href="https://github.com/<?= htmlspecialchars($githubRepo) ?>/releases" target="_blank" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 15px; font-weight: 600; font-size: 13px; border-radius: 12px;">
                <span class="material-symbols-rounded" style="font-size: 17px; color: #64748b;">history</span>
                <span>Release Log</span>
            </a>
        </div>
    </div>

    <!-- Central Status Card -->
    <div class="card" style="padding: 36px 24px; text-align: center; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 24px; background: #ffffff;">
        <div style="max-width: 480px; margin: 0 auto;">
            
            <!-- Dynamic State Icon -->
            <div id="state-icon-container" style="width: 68px; height: 68px; border-radius: 20px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.12); transition: all 0.3s ease;">
                <span class="material-symbols-rounded" id="state-icon" style="font-size: 34px;">check_circle</span>
            </div>

            <!-- Version Status Tag -->
            <div>
                <span id="state-badge" style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 11.5px; font-weight: 700; padding: 3px 12px; border-radius: 999px; margin-bottom: 8px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                    Version <?= htmlspecialchars($currentVersion) ?> Installed
                </span>
                <h2 id="state-title" style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 6px;">DevAltum POS is Up to Date</h2>
                <p id="state-desc" style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0;">
                    Your desktop terminal is running the latest verified release. When new updates are released, they download automatically in the background.
                </p>
            </div>

            <!-- Live Download Progress Container (Hidden by default, shown during download) -->
            <div id="download-progress-box" style="display: none; margin-top: 22px; text-align: left; background: #f8fafc; padding: 18px; border-radius: 14px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 13px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px;" id="progress-status-label">
                        <span class="material-symbols-rounded rotating-spin" style="color: #2563eb; font-size: 18px;">downloading</span>
                        Downloading Release...
                    </span>
                    <span style="font-size: 14px; font-weight: 800; color: #2563eb;" id="progress-percent">0%</span>
                </div>
                <div style="height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin-bottom: 10px;">
                    <div id="progress-bar-fill" style="width: 0%; height: 100%; background: linear-gradient(90deg, #2563eb, #10b981); border-radius: 999px; transition: width 0.3s ease;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 11.5px; color: #64748b; font-weight: 600;">
                    <span id="progress-speed">Speed: -- KB/s</span>
                    <span id="progress-transferred">0 MB / 0 MB</span>
                </div>
            </div>

            <!-- Ready to Install Button (Hidden by default, shown when 100% downloaded) -->
            <div id="ready-install-box" style="display: none; margin-top: 22px;">
                <button type="button" onclick="triggerInstallUpdate()" class="btn btn-primary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; padding: 12px 28px; border-radius: 14px; font-weight: 800; font-size: 14px; cursor: pointer; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35); display: inline-flex; align-items: center; gap: 8px; color: #ffffff;">
                    <span class="material-symbols-rounded">restart_alt</span>
                    Restart & Update Now (<span id="new-version-label">v<?= htmlspecialchars($currentVersion) ?></span>)
                </button>
                <p style="font-size: 12px; color: #059669; margin: 8px 0 0; font-weight: 600;">
                    ✓ All inventory, customer records, and invoices remain 100% safe.
                </p>
            </div>

        </div>
    </div>

    <!-- Minimal 2-Column Info Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
        
        <!-- System Details Card -->
        <div class="card" style="padding: 22px; border-radius: 18px; border: 1px solid #e2e8f0; background: #ffffff;">
            <h3 style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; margin: 0 0 14px; display: flex; align-items: center; gap: 6px;">
                <span class="material-symbols-rounded" style="font-size: 16px; color: #64748b;">memory</span>
                System Details
            </h3>
            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 12.5px;">
                <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                    <span style="color: #64748b;">Installed Version</span>
                    <strong style="color: #0f172a; font-weight: 800;">v<?= htmlspecialchars($currentVersion) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                    <span style="color: #64748b;">Architecture</span>
                    <strong style="color: #0f172a; font-weight: 700;">Windows 64-bit (x64)</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                    <span style="color: #64748b;">Release Channel</span>
                    <strong style="color: #2563eb; font-weight: 700;">Production Stable</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #64748b;">Data Safety</span>
                    <strong style="color: #059669; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                        <span class="material-symbols-rounded" style="font-size: 16px;">shield</span> Protected
                    </strong>
                </div>
            </div>
        </div>

        <!-- Release Highlights Card -->
        <div class="card" style="padding: 22px; border-radius: 18px; border: 1px solid #e2e8f0; background: #ffffff;">
            <h3 style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; margin: 0 0 14px; display: flex; align-items: center; gap: 6px;">
                <span class="material-symbols-rounded" style="font-size: 16px; color: #059669;">verified</span>
                Version <?= htmlspecialchars($currentVersion) ?> Highlights
            </h3>
            <ul style="margin: 0; padding-left: 18px; font-size: 12.5px; color: #475569; line-height: 1.6; display: flex; flex-direction: column; gap: 6px;">
                <li><strong>Universal Token Auto-Healing:</strong> Permanent fix for form timeouts and security token mismatches.</li>
                <li><strong>Biometric Passkey Support:</strong> Direct Windows Hello fingerprint & hardware authentication.</li>
                <li><strong>Fast Tab Switching:</strong> Seamless store settings switching without freezing.</li>
                <li><strong>Clean Safe Uninstaller:</strong> Optional permanent wipe checkbox with background lock release.</li>
            </ul>
        </div>

    </div>

</div>

<script>
(function initSoftwareUpdatesWorkspace() {
    const isDesktop = typeof window.desktopAPI !== 'undefined' && window.desktopAPI.isDesktop;
    let checkTimeout = null;

    window.triggerUpdateCheck = function() {
        const btn = document.getElementById('btn-manual-check');
        const icon = document.getElementById('btn-check-icon');
        const text = document.getElementById('btn-check-text');

        if (btn) btn.disabled = true;
        if (icon) icon.classList.add('rotating-spin');
        if (text) text.textContent = 'Checking...';

        updateUIState('checking');

        if (isDesktop && window.desktopAPI.checkForUpdates) {
            window.desktopAPI.checkForUpdates().then(res => {
                if (res && res.error) {
                    resetCheckBtn();
                    updateUIState('error', res.error);
                }
            }).catch(err => {
                resetCheckBtn();
                updateUIState('error', err.message);
            });
        } else {
            // Web simulation fallback
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(() => {
                resetCheckBtn();
                updateUIState('not-available');
            }, 1200);
        }
    };

    window.triggerInstallUpdate = function() {
        if (isDesktop && window.desktopAPI.installUpdate) {
            window.desktopAPI.installUpdate();
        } else {
            alert('Update installation is active in Desktop version.');
        }
    };

    function resetCheckBtn() {
        const btn = document.getElementById('btn-manual-check');
        const icon = document.getElementById('btn-check-icon');
        const text = document.getElementById('btn-check-text');
        if (btn) btn.disabled = false;
        if (icon) icon.classList.remove('rotating-spin');
        if (text) text.textContent = 'Check for Updates';
    }

    function updateUIState(state, info) {
        const iconBox = document.getElementById('state-icon-container');
        const icon = document.getElementById('state-icon');
        const badge = document.getElementById('state-badge');
        const title = document.getElementById('state-title');
        const desc = document.getElementById('state-desc');
        const progressBox = document.getElementById('download-progress-box');
        const readyBox = document.getElementById('ready-install-box');

        if (!iconBox || !icon || !title || !desc) return;

        if (state === 'checking') {
            iconBox.style.background = '#eff6ff';
            iconBox.style.color = '#2563eb';
            iconBox.style.borderColor = '#bfdbfe';
            icon.textContent = 'sync';
            icon.classList.add('rotating-spin');
            title.textContent = 'Checking for Updates...';
            desc.textContent = 'Connecting to release channel to verify latest build...';
            if (progressBox) progressBox.style.display = 'none';
            if (readyBox) readyBox.style.display = 'none';
        }
        else if (state === 'available') {
            iconBox.style.background = '#fef3c7';
            iconBox.style.color = '#d97706';
            iconBox.style.borderColor = '#fde68a';
            icon.textContent = 'downloading';
            icon.classList.remove('rotating-spin');
            title.textContent = 'New Update Available';
            desc.textContent = 'A new build is downloading in the background. You can continue using POS normally.';
            if (progressBox) progressBox.style.display = 'block';
            if (readyBox) readyBox.style.display = 'none';
        }
        else if (state === 'downloaded') {
            iconBox.style.background = '#ecfdf5';
            iconBox.style.color = '#059669';
            iconBox.style.borderColor = '#a7f3d0';
            icon.textContent = 'verified';
            icon.classList.remove('rotating-spin');
            title.textContent = 'Update Ready to Install!';
            desc.textContent = 'The update package has been downloaded and verified. Restart to complete the upgrade.';
            if (progressBox) progressBox.style.display = 'none';
            if (readyBox) readyBox.style.display = 'block';
            if (badge) {
                badge.textContent = 'Update Ready';
                badge.style.background = '#dcfce7';
                badge.style.color = '#15803d';
            }
        }
        else if (state === 'not-available') {
            iconBox.style.background = '#ecfdf5';
            iconBox.style.color = '#059669';
            iconBox.style.borderColor = '#a7f3d0';
            icon.textContent = 'check_circle';
            icon.classList.remove('rotating-spin');
            title.textContent = 'DevAltum POS is Up to Date';
            desc.textContent = 'Your desktop terminal is running the latest verified release.';
            if (progressBox) progressBox.style.display = 'none';
            if (readyBox) readyBox.style.display = 'none';
        }
        else if (state === 'error') {
            iconBox.style.background = '#fef2f2';
            iconBox.style.color = '#dc2626';
            iconBox.style.borderColor = '#fecaca';
            icon.textContent = 'cloud_off';
            icon.classList.remove('rotating-spin');
            title.textContent = 'Connection Standby';
            desc.textContent = info || 'Could not connect to release server. POS works 100% offline and will retry automatically.';
            if (progressBox) progressBox.style.display = 'none';
            if (readyBox) readyBox.style.display = 'none';
        }
    }

    if (isDesktop) {
        window.desktopAPI.onUpdateStatus(data => {
            resetCheckBtn();
            if (!data) return;
            if (data.status === 'checking') updateUIState('checking');
            else if (data.status === 'available') updateUIState('available');
            else if (data.status === 'downloaded') updateUIState('downloaded');
            else if (data.status === 'not-available') updateUIState('not-available');
            else if (data.status === 'error') updateUIState('error', data.error);
        });

        window.desktopAPI.onUpdateProgress(data => {
            const progressBox = document.getElementById('download-progress-box');
            if (progressBox) progressBox.style.display = 'block';

            const pct = Math.min(100, Math.max(0, data.percent || 0));
            const pctText = Math.round(pct) + '%';
            
            const pctEl = document.getElementById('progress-percent');
            const barEl = document.getElementById('progress-bar-fill');
            if (pctEl) pctEl.textContent = pctText;
            if (barEl) barEl.style.width = pctText;

            if (data.bytesPerSecond) {
                const mbps = (data.bytesPerSecond / (1024 * 1024)).toFixed(1);
                const kbps = (data.bytesPerSecond / 1024).toFixed(0);
                const spdEl = document.getElementById('progress-speed');
                if (spdEl) spdEl.textContent = mbps >= 1 ? `Speed: ${mbps} MB/s` : `Speed: ${kbps} KB/s`;
            }

            if (data.transferred && data.total) {
                const tMB = (data.transferred / (1024 * 1024)).toFixed(1);
                const totMB = (data.total / (1024 * 1024)).toFixed(1);
                const trEl = document.getElementById('progress-transferred');
                if (trEl) trEl.textContent = `${tMB} MB / ${totMB} MB`;
            }
        });
    }
})();
</script>

<style>
@keyframes rotatingSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.rotating-spin {
    animation: rotatingSpin 1s linear infinite;
    display: inline-block;
}
</style>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
