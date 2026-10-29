<div class="content-body" style="padding: 24px; max-width: 1200px; margin: 0 auto;">
    <!-- Hero / Header Card -->
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1e1b4b 100%); border-radius: 20px; padding: 32px; color: #fff; box-shadow: 0 20px 40px -15px rgba(15,23,42,0.35); position: relative; overflow: hidden; margin-bottom: 24px;">
        <div style="position: absolute; top: -60px; right: -60px; width: 260px; height: 260px; background: radial-gradient(circle, rgba(59,130,246,0.25) 0%, rgba(59,130,246,0) 70%); border-radius: 50%; pointer-events: none;"></div>
        <div style="position: absolute; bottom: -80px; left: 30%; width: 220px; height: 220px; background: radial-gradient(circle, rgba(16,185,129,0.18) 0%, rgba(16,185,129,0) 70%); border-radius: 50%; pointer-events: none;"></div>

        <div style="position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 24px;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 72px; height: 72px; border-radius: 18px; background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
                    <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/images/logo.png" alt="DevAltum POS" style="width: 52px; height: 52px; object-fit: contain;" onerror="this.src='https://ui-avatars.com/api/?name=DA&background=2563eb&color=fff&size=96';">
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                        <h1 style="font-size: 24px; font-weight: 700; margin: 0; color: #ffffff; letter-spacing: -0.5px;">DevAltum POS Update Center</h1>
                        <span id="header-version-badge" style="background: rgba(59,130,246,0.2); border: 1px solid rgba(59,130,246,0.4); color: #93c5fd; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">v<?= htmlspecialchars($currentVersion) ?></span>
                    </div>
                    <p style="margin: 0; color: #94a3b8; font-size: 14px;">Automated cloud updates, zero-data-loss upgrades, and instant release deployment</p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <button type="button" id="btn-manual-check" onclick="triggerUpdateCheck()" style="background: #2563eb; color: #fff; border: none; padding: 12px 22px; border-radius: 12px; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 14px rgba(37,99,235,0.4);">
                    <span class="material-symbols-rounded" id="btn-check-icon" style="font-size: 18px;">sync</span>
                    <span id="btn-check-text">Check for Updates</span>
                </button>
                <a href="https://github.com/<?= htmlspecialchars($githubRepo) ?>/releases" target="_blank" style="background: rgba(255,255,255,0.08); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.15); padding: 12px 18px; border-radius: 12px; font-weight: 500; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
                    <span class="material-symbols-rounded" style="font-size: 18px;">open_in_new</span>
                    <span>Releases</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Live Status & Deployment Card -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
        <!-- Main Update Status Panel -->
        <div style="background: #fff; border-radius: 18px; padding: 28px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: #2563eb;">cloud_download</span>
                    Live Update Engine
                </h3>
                <span id="engine-status-pill" style="font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;">
                    System Ready
                </span>
            </div>

            <!-- Dynamic State Box -->
            <div id="update-state-card" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; text-align: center; margin-bottom: 20px; transition: all 0.3s ease;">
                <div id="state-icon-container" style="width: 64px; height: 64px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <span class="material-symbols-rounded" id="state-icon" style="font-size: 32px;">verified</span>
                </div>
                <h4 id="state-title" style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">DevAltum POS is Up to Date</h4>
                <p id="state-desc" style="font-size: 13px; color: #64748b; margin: 0 auto; max-width: 480px;">
                    Your desktop terminal is running the verified release <strong id="state-curr-ver">v<?= htmlspecialchars($currentVersion) ?></strong>. When new releases are published to GitHub, updates download silently in background.
                </p>

                <!-- Live Progress Box (Hidden by default, shown during download) -->
                <div id="download-progress-box" style="display: none; margin-top: 20px; text-align: left; background: #ffffff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;" id="progress-status-label">Downloading Update...</span>
                        <span style="font-size: 14px; font-weight: 700; color: #2563eb;" id="progress-percent">0%</span>
                    </div>
                    <div style="height: 10px; background: #f1f5f9; border-radius: 6px; overflow: hidden; margin-bottom: 10px;">
                        <div id="progress-bar-fill" style="width: 0%; height: 100%; background: linear-gradient(90deg, #3b82f6, #10b981); border-radius: 6px; transition: width 0.3s ease;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #64748b;">
                        <span id="progress-speed">Speed: -- KB/s</span>
                        <span id="progress-transferred">0 MB / 0 MB</span>
                    </div>
                </div>

                <!-- Ready to Install Action (Hidden by default) -->
                <div id="ready-install-box" style="display: none; margin-top: 20px;">
                    <button type="button" onclick="triggerInstallUpdate()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border: none; padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; box-shadow: 0 6px 20px rgba(16,185,129,0.4); display: inline-flex; align-items: center; gap: 10px;">
                        <span class="material-symbols-rounded">restart_alt</span>
                        Restart & Update Now (v<span id="new-version-label"></span>)
                    </button>
                    <p style="font-size: 12px; color: #059669; margin-top: 8px; font-weight: 500;">
                        ✓ All store inventory, invoices, and databases are 100% preserved.
                    </p>
                </div>
            </div>

            <!-- Features Highlights -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                <div style="background: #f8fafc; padding: 14px; border-radius: 10px; border: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span class="material-symbols-rounded" style="color: #10b981; font-size: 18px;">security</span>
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Data Immunity</span>
                    </div>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">MariaDB databases and store configurations remain safe across updates.</p>
                </div>
                <div style="background: #f8fafc; padding: 14px; border-radius: 10px; border: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span class="material-symbols-rounded" style="color: #3b82f6; font-size: 18px;">downloading</span>
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Differential Delta</span>
                    </div>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">Blockmap technology downloads only changed binary chunks for high speed.</p>
                </div>
                <div style="background: #f8fafc; padding: 14px; border-radius: 10px; border: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span class="material-symbols-rounded" style="color: #8b5cf6; font-size: 18px;">wifi_off</span>
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Offline Resilient</span>
                    </div>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">POS runs 100% offline; updater only checks when internet is active.</p>
                </div>
            </div>
        </div>

        <!-- Release & Environment Info Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div style="background: #fff; border-radius: 18px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
                <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: #64748b;">info</span>
                    System Specification
                </h3>

                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b;">Installed Version:</span>
                        <strong style="color: #0f172a;" id="sys-installed-version">v<?= htmlspecialchars($currentVersion) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b;">Architecture:</span>
                        <strong style="color: #0f172a;">Windows 64-bit (x64)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b;">Channel:</span>
                        <span style="color: #2563eb; font-weight: 600;">Official GitHub Stable</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b;">Cloud Repo:</span>
                        <a href="https://github.com/<?= htmlspecialchars($githubRepo) ?>" target="_blank" style="color: #0284c7; text-decoration: none; font-weight: 500; font-family: monospace; font-size: 12px;">
                            <?= htmlspecialchars($githubRepo) ?>
                        </a>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b;">Runtime Mode:</span>
                        <span id="sys-runtime-mode" style="font-weight: 600; color: #10b981;">Electron Standalone</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-bottom: 4px;">
                        <span style="color: #64748b;">Last Checked:</span>
                        <span id="sys-last-check" style="color: #0f172a; font-weight: 500;">Just now</span>
                    </div>
                </div>
            </div>

            <div style="background: #fff; border-radius: 18px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
                <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 12px 0;">Official Support</h3>
                <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0 0 16px 0;">
                    Have questions about new versions or need assistance with custom business modules?
                </p>
                <a href="https://wa.me/923101648600?text=Assalam%20o%20Alaikum%20DevAltum%20Support,%20I%20have%20an%20inquiry%20regarding%20DevAltum%20POS%20Updates" target="_blank" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: #16a34a; font-size: 18px;">chat</span>
                    <span>Chat on WhatsApp</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
let isDesktop = typeof window.desktopAPI !== 'undefined' && window.desktopAPI.isDesktop;
let isUpdateReady = false;

document.addEventListener('DOMContentLoaded', function() {
    initUpdaterCenter();
});

async function initUpdaterCenter() {
    const runtimeEl = document.getElementById('sys-runtime-mode');
    if (!isDesktop) {
        if (runtimeEl) {
            runtimeEl.textContent = 'Web Browser Client';
            runtimeEl.style.color = '#f59e0b';
        }
        return;
    }

    try {
        const sysInfo = await window.desktopAPI.getSystemInfo();
        if (sysInfo && sysInfo.version) {
            document.getElementById('sys-installed-version').textContent = 'v' + sysInfo.version;
            document.getElementById('state-curr-ver').textContent = 'v' + sysInfo.version;
            document.getElementById('header-version-badge').textContent = 'v' + sysInfo.version;
        }
    } catch(e) {
        console.log('System info notice:', e);
    }

    // Register electron updater event listeners
    window.desktopAPI.onUpdateStatus((data) => {
        handleUpdaterStatus(data);
    });

    window.desktopAPI.onUpdateProgress((data) => {
        handleUpdaterProgress(data);
    });
}

function triggerUpdateCheck() {
    const btn = document.getElementById('btn-manual-check');
    const icon = document.getElementById('btn-check-icon');
    const text = document.getElementById('btn-check-text');

    if (icon) icon.classList.add('rotating-spin');
    if (text) text.textContent = 'Checking Cloud...';
    if (btn) btn.disabled = true;

    document.getElementById('sys-last-check').textContent = new Date().toLocaleTimeString();

    if (!isDesktop) {
        setTimeout(() => {
            if (icon) icon.classList.remove('rotating-spin');
            if (text) text.textContent = 'Check for Updates';
            if (btn) btn.disabled = false;
            alert('DevAltum POS Update Center: Running in Web Browser mode. Native desktop updates are executed within the DevAltum POS Windows app.');
        }, 800);
        return;
    }

    window.desktopAPI.checkForUpdates().then((res) => {
        setTimeout(() => {
            if (icon) icon.classList.remove('rotating-spin');
            if (text) text.textContent = 'Check for Updates';
            if (btn) btn.disabled = false;
        }, 1500);
    }).catch((err) => {
        if (icon) icon.classList.remove('rotating-spin');
        if (text) text.textContent = 'Check for Updates';
        if (btn) btn.disabled = false;
        console.warn('Check update error:', err);
    });
}

function handleUpdaterStatus(data) {
    const pill = document.getElementById('engine-status-pill');
    const iconContainer = document.getElementById('state-icon-container');
    const icon = document.getElementById('state-icon');
    const title = document.getElementById('state-title');
    const desc = document.getElementById('state-desc');
    const progressBox = document.getElementById('download-progress-box');
    const readyBox = document.getElementById('ready-install-box');

    if (!data || !data.status) return;

    if (data.status === 'checking') {
        pill.textContent = 'Checking GitHub...';
        pill.style.background = '#eff6ff';
        pill.style.color = '#2563eb';
        pill.style.borderColor = '#bfdbfe';

        iconContainer.style.background = '#eff6ff';
        iconContainer.style.color = '#2563eb';
        icon.textContent = 'sync';
        icon.classList.add('rotating-spin');

        title.textContent = 'Checking Cloud Releases...';
        desc.textContent = 'Connecting to official GitHub release channel for latest DevAltum POS binaries.';
    }
    else if (data.status === 'available') {
        icon.classList.remove('rotating-spin');
        pill.textContent = 'Update Available';
        pill.style.background = '#fef3c7';
        pill.style.color = '#b45309';
        pill.style.borderColor = '#fde68a';

        iconContainer.style.background = '#eff6ff';
        iconContainer.style.color = '#2563eb';
        icon.textContent = 'downloading';

        title.textContent = `New Release v${data.version || ''} Found!`;
        desc.textContent = 'An official new update is downloading silently in the background. You can continue using POS normally.';
        if (progressBox) progressBox.style.display = 'block';
    }
    else if (data.status === 'not-available') {
        icon.classList.remove('rotating-spin');
        pill.textContent = 'Up to Date';
        pill.style.background = '#ecfdf5';
        pill.style.color = '#059669';
        pill.style.borderColor = '#a7f3d0';

        iconContainer.style.background = '#ecfdf5';
        iconContainer.style.color = '#059669';
        icon.textContent = 'verified';

        title.textContent = 'DevAltum POS is Up to Date';
        desc.textContent = 'Your system has the latest features, security patches, and optimizations installed.';
        if (progressBox) progressBox.style.display = 'none';
        if (readyBox) readyBox.style.display = 'none';
    }
    else if (data.status === 'downloaded') {
        icon.classList.remove('rotating-spin');
        isUpdateReady = true;

        pill.textContent = 'Update Downloaded';
        pill.style.background = '#ecfdf5';
        pill.style.color = '#059669';
        pill.style.borderColor = '#a7f3d0';

        iconContainer.style.background = '#dcfce7';
        iconContainer.style.color = '#15803d';
        icon.textContent = 'install_desktop';

        title.textContent = `DevAltum POS v${data.version || ''} Ready to Install!`;
        desc.textContent = 'The update package has been downloaded and verified. Click the button below to restart and finalize the update.';
        
        if (progressBox) progressBox.style.display = 'none';
        if (readyBox) {
            document.getElementById('new-version-label').textContent = data.version || '';
            readyBox.style.display = 'block';
        }
    }
    else if (data.status === 'error') {
        icon.classList.remove('rotating-spin');
        pill.textContent = 'Offline / Standby';
        pill.style.background = '#fef2f2';
        pill.style.color = '#b91c1c';
        pill.style.borderColor = '#fecaca';

        iconContainer.style.background = '#fef2f2';
        iconContainer.style.color = '#dc2626';
        icon.textContent = 'cloud_off';

        title.textContent = 'Cloud Connection Standby';
        desc.textContent = 'Could not reach GitHub releases. POS works 100% offline. Will automatically check again when connected.';
    }
}

function handleUpdaterProgress(data) {
    const progressBox = document.getElementById('download-progress-box');
    if (!progressBox) return;
    progressBox.style.display = 'block';

    const percent = Math.min(100, Math.max(0, data.percent || 0));
    document.getElementById('progress-percent').textContent = percent + '%';
    document.getElementById('progress-bar-fill').style.width = percent + '%';

    if (data.bytesPerSecond) {
        const speedKB = (data.bytesPerSecond / 1024).toFixed(0);
        const speedMB = (data.bytesPerSecond / (1024 * 1024)).toFixed(1);
        document.getElementById('progress-speed').textContent = speedMB >= 1 ? `Speed: ${speedMB} MB/s` : `Speed: ${speedKB} KB/s`;
    }

    if (data.transferred && data.total) {
        const transMB = (data.transferred / (1024 * 1024)).toFixed(1);
        const totalMB = (data.total / (1024 * 1024)).toFixed(1);
        document.getElementById('progress-transferred').textContent = `${transMB} MB / ${totalMB} MB`;
    }
}

function triggerInstallUpdate() {
    if (!isDesktop) return;
    window.desktopAPI.installUpdate();
}
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

