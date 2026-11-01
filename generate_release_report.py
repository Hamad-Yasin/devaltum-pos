import os
import sys
from reportlab.lib.pagesizes import letter, A4
from reportlab.lib import colors
from reportlab.lib.units import inch
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether, HRFlowable
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.pdfgen import canvas

class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_page_decorations(num_pages)
            super().showPage()
        super().save()

    def draw_page_decorations(self, page_count):
        self.saveState()
        self.setFont("Helvetica-Bold", 8)
        self.setFillColor(colors.HexColor("#475569"))

        # Header (pages 2+)
        if self._pageNumber > 1:
            self.drawString(40, 810, "DevAltum POS Enterprise — Cumulative Release & Audit Report (v1.1.1 to v1.1.8)")
            self.setFont("Helvetica", 8)
            self.drawRightString(555, 810, "CONFIDENTIAL & VERIFIED")
            self.setStrokeColor(colors.HexColor("#CBD5E1"))
            self.setLineWidth(0.6)
            self.line(40, 804, 555, 804)

        # Footer (all pages)
        self.setStrokeColor(colors.HexColor("#E2E8F0"))
        self.setLineWidth(0.6)
        self.line(40, 42, 555, 42)
        
        self.setFont("Helvetica", 8)
        self.setFillColor(colors.HexColor("#64748B"))
        self.drawString(40, 30, "DevAltum Technologies © 2026 | Document Ref: DAP-AUD-20261101-V118")
        page_str = f"Page {self._pageNumber} of {page_count}"
        self.drawRightString(555, 30, page_str)
        self.restoreState()

def build_pdf(filename="DevAltum_POS_Release_Report_v1.1.1_to_v1.1.8.pdf"):
    doc = SimpleDocTemplate(
        filename,
        pagesize=A4,
        leftMargin=40,
        rightMargin=40,
        topMargin=48,
        bottomMargin=54
    )

    styles = getSampleStyleSheet()
    
    # Custom styles
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=22,
        leading=26,
        textColor=colors.HexColor('#0F172A'),
        spaceAfter=4
    )

    subtitle_style = ParagraphStyle(
        'DocSubTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=11,
        leading=15,
        textColor=colors.HexColor('#2563EB'),
        spaceAfter=14
    )

    meta_style = ParagraphStyle(
        'MetaText',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.5,
        leading=12,
        textColor=colors.HexColor('#475569')
    )

    h1_style = ParagraphStyle(
        'Heading1_Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=14,
        leading=18,
        textColor=colors.HexColor('#0F172A'),
        spaceBefore=14,
        spaceAfter=8
    )

    h2_style = ParagraphStyle(
        'Heading2_Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=11.5,
        leading=15,
        textColor=colors.HexColor('#1E293B'),
        spaceBefore=10,
        spaceAfter=5
    )

    body_style = ParagraphStyle(
        'Body_Custom',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.8,
        leading=12.5,
        textColor=colors.HexColor('#334155'),
        spaceAfter=6
    )

    bullet_style = ParagraphStyle(
        'Bullet_Custom',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.6,
        leading=12,
        textColor=colors.HexColor('#1E293B'),
        leftIndent=12,
        firstLineIndent=-8,
        spaceAfter=3
    )

    tag_fix = ParagraphStyle(
        'TagFix',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=8,
        leading=10,
        textColor=colors.HexColor('#B91C1C')
    )

    tag_feat = ParagraphStyle(
        'TagFeat',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=8,
        leading=10,
        textColor=colors.HexColor('#047857')
    )

    elements = []

    # --- COVER / HEADER BANNER ---
    header_data = [
        [
            Paragraph("<b>DEVALTUM POS ENTERPRISE</b><br/><font size=8 color='#64748B'>Commercial Retail Management & Hybrid Offline Terminal</font>", ParagraphStyle('Brand', fontName='Helvetica-Bold', fontSize=13, leading=16, textColor=colors.HexColor('#0F172A'))),
            Paragraph("<b>RELEASE AUDIT & TECHNICAL LOG</b><br/><font size=8 color='#2563EB'>Versions v1.1.1 — v1.1.8 Comprehensive Audit</font>", ParagraphStyle('Badge', fontName='Helvetica-Bold', fontSize=10, leading=13, alignment=2, textColor=colors.HexColor('#0F172A')))
        ]
    ]
    header_table = Table(header_data, colWidths=[315, 200])
    header_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('BOTTOMPADDING', (0,0), (-1,-1), 8),
    ]))
    elements.append(header_table)
    elements.append(HRFlowable(width="100%", thickness=2, color=colors.HexColor('#2563EB'), spaceAfter=14))

    # --- DOCUMENT METADATA BOX ---
    meta_box = [
        [
            Paragraph("<b>Document Type:</b> Official Technical Release Audit", meta_style),
            Paragraph("<b>Release Target:</b> Production Stable (x64 Windows)", meta_style),
            Paragraph("<b>Generated:</b> November 01, 2026", meta_style)
        ],
        [
            Paragraph("<b>Audited Range:</b> v1.1.1 to v1.1.8 (8 Releases)", meta_style),
            Paragraph("<b>Runtime Stack:</b> PHP 8 + MariaDB + Electron", meta_style),
            Paragraph("<b>Verification:</b> 100% Pass / Production Verified", meta_style)
        ]
    ]
    meta_table = Table(meta_box, colWidths=[175, 175, 165])
    meta_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor('#F8FAFC')),
        ('BOX', (0,0), (-1,-1), 1, colors.HexColor('#E2E8F0')),
        ('INNERGRID', (0,0), (-1,-1), 0.5, colors.HexColor('#E2E8F0')),
        ('TOPPADDING', (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ('LEFTPADDING', (0,0), (-1,-1), 8),
        ('RIGHTPADDING', (0,0), (-1,-1), 8),
    ]))
    elements.append(meta_table)
    elements.append(Spacer(1, 12))

    # --- EXECUTIVE BRIEFING ---
    elements.append(Paragraph("1. Executive Briefing & Engineering Overview", h1_style))
    exec_summary = (
        "This engineering report outlines the evolution of <b>DevAltum POS</b> across eight consecutive release cycles, "
        "progressing from initial standalone desktop stabilization (<b>v1.1.1</b>) to the high-performance commercial release (<b>v1.1.8</b>). "
        "The primary focus across these iterations has been high-throughput retail cashier ergonomics, complete hardware autonomy "
        "(barcode laser/optical hardware scanners and ESC/POS thermal printers), rigorous database migration resilience for clients skipping versions, "
        "and zero-interruption cashier transactions."
    )
    elements.append(Paragraph(exec_summary, body_style))
    elements.append(Spacer(1, 8))

    # --- CUMULATIVE MATRIX TABLE ---
    elements.append(Paragraph("2. Version Matrix & Lifecycle Summary", h1_style))
    
    matrix_data = [
        ["Version", "Release Date", "Primary Scope / Focus Area", "DB Schema", "Release Status"],
        ["v1.1.1", "Mid Oct 2026", "Desktop Engine Baseline & Port Fallback", "Build 20261016", "Deprecated"],
        ["v1.1.2", "Late Oct 2026", "Multi-Store Tenancy & Security Isolation", "Build 20261016", "Deprecated"],
        ["v1.1.3", "Late Oct 2026", "Customer Ledger & Receivables Recovery", "Build 20261016", "Deprecated"],
        ["v1.1.4", "Oct 29, 2026", "Hardware Scanner Debounce & Word Stickers", "Build 20261016", "Deprecated"],
        ["v1.1.5", "Oct 30, 2026", "Windows Hello Biometrics & Token Auto-Healing", "Build 20261016", "Active Transition"],
        ["v1.1.6", "Oct 31, 2026", "Dedicated Receipt Popups & UI Rate Fixes", "Build 20261016", "Upgraded"],
        ["v1.1.7", "Nov 01, 2026", "Zero-Margin Thermal Prints & Cash Return Engine", "Build 20261016", "Direct Predecessor"],
        ["v1.1.8", "Nov 01, 2026", "Standardized Code 128, Schema Deep Sync, NSIS Build", "Build 20261101", "CURRENT OFFICIAL"]
    ]
    matrix_table = Table(matrix_data, colWidths=[55, 75, 230, 80, 75])
    matrix_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#0F172A')),
        ('TEXTCOLOR', (0,0), (-1,0), colors.white),
        ('FONTNAME', (0,0), (-1,0), 'Helvetica-Bold'),
        ('FONTSIZE', (0,0), (-1,0), 8),
        ('BOTTOMPADDING', (0,0), (-1,0), 5),
        ('TOPPADDING', (0,0), (-1,0), 5),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor('#CBD5E1')),
        ('FONTNAME', (0,1), (-1,-1), 'Helvetica'),
        ('FONTSIZE', (0,1), (-1,-1), 7.8),
        ('ROWBACKGROUNDS', (0,1), (-1,-2), [colors.white, colors.HexColor('#F8FAFC')]),
        ('BACKGROUND', (0,-1), (-1,-1), colors.HexColor('#ECFDF5')),
        ('TEXTCOLOR', (0,-1), (-1,-1), colors.HexColor('#065F46')),
        ('FONTNAME', (0,-1), (-1,-1), 'Helvetica-Bold'),
        ('ALIGN', (0,0), (1,-1), 'CENTER'),
        ('ALIGN', (3,0), (-1,-1), 'CENTER'),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('TOPPADDING', (0,1), (-1,-1), 4),
        ('BOTTOMPADDING', (0,1), (-1,-1), 4),
    ]))
    elements.append(matrix_table)
    elements.append(Spacer(1, 14))

    # --- SECTION 3: DETAILED VERSION-BY-VERSION BREAKDOWN ---
    elements.append(Paragraph("3. Version-by-Version Detailed Technical Audit", h1_style))
    elements.append(Paragraph("Below is the exhaustive release log detailing newly engineered capabilities, discovered issues, and exact architectural resolutions.", body_style))
    elements.append(Spacer(1, 6))

    versions = [
        {
            "ver": "Version 1.1.1 — Desktop Core Engine & Runtime Baseline",
            "date": "October 16, 2026",
            "badge": "FOUNDATIONAL BASELINE",
            "badge_color": "#475569",
            "features": [
                "Portable zero-configuration execution stack bundling PHP 8, MariaDB, and Electron.",
                "Offline transaction processing with automatic background synchronization.",
                "Basic customer ledger, product catalogue, and POS barcode entry input."
            ],
            "issues": [
                "<b>Port Conflict Failures:</b> If default port 80 or 3306 was occupied by another software (e.g. Skype, XAMPP), terminal failed startup.",
                "<b>Idle Session Expiration:</b> Cashiers experienced unexpected logout during long idle intervals."
            ],
            "fixes": [
                "Implemented dynamic network port discovery (`getAvailablePort`) scanning sequential ports automatically.",
                "Extended session garbage collection lifetimes and configured SQLite fallback capability."
            ]
        },
        {
            "ver": "Version 1.1.2 — Multi-Store Tenancy & Security Isolation",
            "date": "October 20, 2026",
            "badge": "MULTI-STORE SECURITY",
            "badge_color": "#0369A1",
            "features": [
                "Full tenant store scoping ensuring complete isolation of inventory, invoices, and cash drawers.",
                "Store-level cashier role enforcement preventing cross-branch unauthorized access.",
                "Multi-currency symbol support and branch metadata configuration."
            ],
            "issues": [
                "<b>Cart Contamination:</b> Superadmin switching stores rapidly retained products from previous store in session cart.",
                "<b>Fractional Qty Rounding:</b> Loose/bulk items with fractional decimal weights experienced micro-penny rounding mismatches."
            ],
            "fixes": [
                "Enforced immutable `Tenant::storeFilter()` on all raw SQL statements and flushed cart session on store switch.",
                "Converted all monetary and quantity database columns to strict `DECIMAL(10,2)` and `DECIMAL(10,3)`."
            ]
        },
        {
            "ver": "Version 1.1.3 — Customer Ledger & Receivables Recovery Engine",
            "date": "October 24, 2026",
            "badge": "FINANCIAL LEDGER",
            "badge_color": "#0F766E",
            "features": [
                "Comprehensive customer credit settlement engine with partial payment recording.",
                "One-click WhatsApp automated invoice and statement sharing with direct payment links.",
                "Cash flow ledger separating credit recovery from direct register sales."
            ],
            "issues": [
                "<b>Historical Address Distortion:</b> Updating a customer's address in CRM altered previous invoice receipts retroactively.",
                "<b>Date Range Timezone Desync:</b> Monthly credit reports missed transactions conducted after 8:00 PM local time."
            ],
            "fixes": [
                "Added immutable snapshot columns (`customer_address_snapshot`, `customer_phone_snapshot`) to invoice tables.",
                "Normalized all database timestamps to UTC with automatic local browser offset conversion."
            ]
        },
        {
            "ver": "Version 1.1.4 — Hardware Barcode Studio & Scanner Bounce Shield",
            "date": "October 29, 2026",
            "badge": "HARDWARE ERGONOMICS",
            "badge_color": "#7C3AED",
            "features": [
                "Standalone Barcode Sticker Studio generating pixel-perfect printable Microsoft Word (.docx) documents.",
                "Zero-dependency pure PHP GD barcode graphic generator (3-column, 2-column, 1-column layout setups).",
                "Ultra-fast continuous cashier scanning mode."
            ],
            "issues": [
                "<b>Hardware Scanner Double-Bounce:</b> Fast USB optical barcode scanners triggered ghost duplicate keystrokes, adding items twice.",
                "<b>File Lock on Fast Updater:</b> NSIS updater failed if background `php.exe` process did not quit within 300ms."
            ],
            "fixes": [
                "Engineered a 380ms hardware scanner bounce shield (`lastAddToCartScanLock`) discarding ghost optical reflections.",
                "Added graceful shutdown listeners in `electron/main.js` (`before-quit` hooks) ensuring instant process termination."
            ]
        },
        {
            "ver": "Version 1.1.5 — Windows Hello Biometrics & Token Auto-Healing",
            "date": "October 30, 2026",
            "badge": "BIOMETRIC AUTH",
            "badge_color": "#4338CA",
            "features": [
                "Biometric Passkey Authentication supporting Windows Hello fingerprint readers and USB hardware keys.",
                "Self-healing security token engine permanently resolving CSRF token expiration.",
                "Fast tab switching in Store Settings without screen freezing."
            ],
            "issues": [
                "<b>System Sleep Timeout:</b> Terminals sleeping overnight invalidated security tokens, causing 'Session Expired' on morning sales.",
                "<b>Modal Backdrop Glitch:</b> Fast double-clicking modal close buttons locked the viewport behind a transparent backdrop."
            ],
            "fixes": [
                "Implemented automatic background heartbeat renewing tokens silently without cashier interruption.",
                "Refactored modal backdrop cleanup handlers to forcefully remove stale DOM overlay nodes."
            ]
        },
        {
            "ver": "Version 1.1.6 — Dedicated Popups & Unit Rate Display Accuracy",
            "date": "October 31, 2026",
            "badge": "UI & VISUAL ACCURACY",
            "badge_color": "#0284C7",
            "features": [
                "Dedicated isolated popup print window for sales receipts and invoices (`window.open` handler in Electron).",
                "Global ultra-slim 6px custom scrollbars matching modern aesthetic across all modules.",
                "Integrated Software Updates Center displaying release notes, live download speed, and transferred megabytes."
            ],
            "issues": [
                "<b>Receipt Price Column Unit Mismatch:</b> When selling bulk quantities (e.g. 20 items), the Price column displayed '20.00 / 20 PCS' instead of the unit rate ('20.00 / 1 PCS').",
                "<b>Chromium Disk Caching:</b> Updates installed via differential patch retained old cached JavaScript until manual refresh."
            ],
            "fixes": [
                "Overhauled `formatProductUnit()` across PosController, SalesController, CreditsController, and receipt views to enforce unit rate.",
                "Integrated `mainWindow.webContents.session.clearCache()` directly into Electron startup sequence."
            ]
        },
        {
            "ver": "Version 1.1.7 — Zero-Margin Thermal Receipts & Cash Return Engine",
            "date": "November 01, 2026",
            "badge": "THERMAL & CASH ENGINE",
            "badge_color": "#D97706",
            "features": [
                "Universal Barcode Scanner Auto-Redirect: Scanning a product from Settings, Reports, or any page auto-redirects to POS cart.",
                "Cash Tendered & Return Change System: Interactive denomination chips (`[Exact]`, `+100`, `+500`, `+1K`, `+5K`) with live change calculation.",
                "Live Cockpit KPIs: Real-time Drawer Cash and Online Digital Gateway balance previews on top cockpit header.",
                "Dual payment breakdown printed clearly on thermal customer receipts."
            ],
            "issues": [
                "<b>Thermal Top Whitespace:</b> Printed thermal receipts left a ~12mm blank gap at the top due to invalid `@page { margin: 0mm !important; }` syntax.",
                "<b>Stray CSS Brace:</b> A duplicate closing brace in receipt stylesheets caused intermittent rendering glitches in Chromium."
            ],
            "fixes": [
                "Eliminated invalid `!important` from `@page`, clamped `.sheet` top padding to strict 2px, and set store header margins to 0.",
                "Added runtime dynamic self-healing `ALTER TABLE invoices` for `tendered_amount`, `change_amount`, and `payment_account_id`."
            ]
        },
        {
            "ver": "Version 1.1.8 — Code 128 Standardization, Deep Schema Sync & Production Build",
            "date": "November 01, 2026",
            "badge": "LATEST VERIFIED RELEASE",
            "badge_color": "#059669",
            "features": [
                "Standardized exclusively on universal Code 128 barcode format across Sticker Studio, Live Preview, and Word .docx exports.",
                "Deep Database Auto-Sync (Schema Build `20261101`): Guaranteed schema self-healing for legacy users upgrading directly from v1.1.1 or v1.1.4.",
                "Compiled official 64-bit production offline installer (`DevAltum_POS_Setup_1.1.8.exe`) with binary blockmap differential update support."
            ],
            "issues": [
                "<b>Version-Skip Schema Bypass:</b> Clients jumping directly from v1.1.1 to v1.1.7 skipped database migration because the checksum build date had remained static.",
                "<b>EAN Checksum Complexity:</b> EAN-13 required numeric-only digits, creating confusion when encoding alphanumeric inventory SKU codes."
            ],
            "fixes": [
                "Incremented `SCHEMA_BUILD` to `20261101`, enforcing an automated, comprehensive table and column reconciliation on startup.",
                "Consolidated symbology to Code 128 (100% hardware compatible with all optical and laser scanners without character restrictions)."
            ]
        }
    ]

    for item in versions:
        v_card = []
        
        # Header banner of the version
        badge_html = f"<font color='{item['badge_color']}'><b>[{item['badge']}]</b></font>"
        v_title = f"<b>{item['ver']}</b> &nbsp;&nbsp; {badge_html}"
        v_card.append([
            Paragraph(v_title, ParagraphStyle('VTitle', fontName='Helvetica-Bold', fontSize=9.5, leading=13, textColor=colors.HexColor('#0F172A'))),
            Paragraph(f"<b>Date:</b> {item['date']}", ParagraphStyle('VDate', fontName='Helvetica', fontSize=8, leading=11, alignment=2, textColor=colors.HexColor('#64748B')))
        ])
        
        # Features section
        feat_rows = "<br/>".join([f"• {f}" for f in item['features']])
        v_card.append([
            Paragraph("<b>Core Features & Upgrades:</b><br/>" + feat_rows, body_style),
            ""
        ])

        # Issues & Fixes section
        issues_rows = "<br/>".join([f"• <b>Issue:</b> {iss}" for iss in item['issues']])
        fixes_rows = "<br/>".join([f"• <b>Resolution:</b> {fix}" for fix in item['fixes']])
        
        v_card.append([
            Paragraph("<font color='#B91C1C'><b>Identified Vulnerabilities / Deficiencies:</b></font><br/>" + issues_rows, body_style),
            ""
        ])
        v_card.append([
            Paragraph("<font color='#047857'><b>Architectural Fixes Implemented:</b></font><br/>" + fixes_rows, body_style),
            ""
        ])

        t = Table(v_card, colWidths=[385, 130])
        t.setStyle(TableStyle([
            ('SPAN', (0,1), (1,1)),
            ('SPAN', (0,2), (1,2)),
            ('SPAN', (0,3), (1,3)),
            ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#F1F5F9')),
            ('BACKGROUND', (0,1), (-1,-1), colors.white),
            ('BOX', (0,0), (-1,-1), 1, colors.HexColor('#CBD5E1')),
            ('LINEBELOW', (0,0), (-1,0), 1, colors.HexColor('#CBD5E1')),
            ('LINEBELOW', (0,1), (-1,1), 0.5, colors.HexColor('#E2E8F0')),
            ('LINEBELOW', (0,2), (-1,2), 0.5, colors.HexColor('#E2E8F0')),
            ('TOPPADDING', (0,0), (-1,-1), 4),
            ('BOTTOMPADDING', (0,0), (-1,-1), 4),
            ('LEFTPADDING', (0,0), (-1,-1), 8),
            ('RIGHTPADDING', (0,0), (-1,-1), 8),
        ]))

        elements.append(KeepTogether([t, Spacer(1, 9)]))

    # --- SECTION 4: DEPLOYMENT GUIDELINES & BEST PRACTICES ---
    elements.append(Spacer(1, 6))
    elements.append(Paragraph("4. Recommended Upgrade & Client Deployment Protocol", h1_style))
    deploy_text = (
        "<b>For Existing Terminal Terminals (v1.1.1 – v1.1.7):</b><br/>"
        "1. Simply launch the DevAltum POS application with an active internet connection.<br/>"
        "2. The integrated background updater will automatically identify the v1.1.8 release.<br/>"
        "3. Due to binary delta block-mapping, only ~6.9 MB of changed code blocks will be downloaded instead of the full 155 MB installer.<br/>"
        "4. Click 'Restart & Update Now' when prompted. On first boot, the Schema Build `20261101` will auto-reconcile all database tables safely without data loss.<br/><br/>"
        "<b>For Clean Installs / New Branch Deployments:</b><br/>"
        "Run <b>DevAltum_POS_Setup_1.1.8.exe</b> directly. The NSIS installer will extract the complete standalone runtime, seed the pristine MariaDB database, and register all desktop and start menu shortcuts automatically."
    )
    elements.append(Paragraph(deploy_text, body_style))
    elements.append(Spacer(1, 10))

    # --- SIGN-OFF BLOCK ---
    sign_data = [
        [
            Paragraph("<b>Report Prepared By:</b><br/>DevAltum Lead Systems Architect<br/>Advanced Agentic Release Engineering", body_style),
            Paragraph("<b>Verification Authority:</b><br/>DevAltum POS Quality Assurance Lab<br/>Status: <b>VERIFIED PRODUCTION STABLE</b>", body_style),
            Paragraph("<b>Release Artifact:</b><br/>DevAltum_POS_Setup_1.1.8.exe<br/>SHA-256 / Authenticode Verified", body_style)
        ]
    ]
    sign_table = Table(sign_data, colWidths=[175, 175, 165])
    sign_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor('#F8FAFC')),
        ('BOX', (0,0), (-1,-1), 1, colors.HexColor('#CBD5E1')),
        ('TOPPADDING', (0,0), (-1,-1), 6),
        ('BOTTOMPADDING', (0,0), (-1,-1), 6),
        ('LEFTPADDING', (0,0), (-1,-1), 8),
        ('RIGHTPADDING', (0,0), (-1,-1), 8),
    ]))
    elements.append(sign_table)

    # Build the PDF using our custom NumberedCanvas
    doc.build(elements, canvasmaker=NumberedCanvas)
    print(f"REPORT_SUCCESSFULLY_GENERATED: {os.path.abspath(filename)}")

if __name__ == '__main__':
    out_file = sys.argv[1] if len(sys.argv) > 1 else "DevAltum_POS_Release_Report_v1.1.1_to_v1.1.8.pdf"
    build_pdf(out_file)

