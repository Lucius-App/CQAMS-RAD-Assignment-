<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

startSecureSession();
$currentUser = getAuthenticatedUser();

$clinics = [];
$doctors = [];
try {
    $db = getDatabaseConnection();
    $clinics = $db->query(
        'SELECT clinic_id, clinic_code, name, address, phone, email, photo_url, description
         FROM clinics
         WHERE is_active = 1
         ORDER BY clinic_id'
    )->fetchAll();

    $doctors = $db->query(
        'SELECT d.doctor_id, d.specialization, s.first_name, s.last_name, c.name AS clinic_name
         FROM doctors d
         INNER JOIN staff s ON s.staff_id = d.staff_id
         INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
         LEFT JOIN clinics c ON c.clinic_id = d.clinic_id
         WHERE u.is_active = 1
         ORDER BY s.last_name, s.first_name'
    )->fetchAll();
} catch (Throwable $e) {
    error_log('Landing page DB fetch error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CQAMS | Clinic Queue & Appointment Management System</title>
    <link rel="stylesheet" href="/CQMS/assets/css/app.css">
    <style>
        .landing-header {
            background: #ffffff;
            border-bottom: 1px solid var(--color-border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .landing-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .landing-nav a {
            color: var(--color-ink);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.15s ease;
        }
        .landing-nav a:hover {
            color: var(--color-primary);
        }
        .hero {
            background: radial-gradient(circle at 10% 20%, rgba(20, 184, 166, 0.15), transparent 40%),
                        linear-gradient(135deg, #082f49 0%, #0e4d63 50%, #176b87 100%);
            color: #ffffff;
            padding: 4.5rem 2rem;
            text-align: center;
        }
        .hero-shell {
            max-width: 850px;
            margin: 0 auto;
        }
        .hero h1 {
            font-size: 2.75rem;
            font-weight: 800;
            line-height: 1.15;
            margin: 0 0 1.25rem;
            letter-spacing: -0.02em;
        }
        .hero p {
            font-size: 1.2rem;
            color: #ccfbf1;
            margin: 0 0 2rem;
            line-height: 1.6;
        }
        .hero-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .hero-btn-primary {
            background: #14b8a6;
            color: #042f2e;
            font-weight: 700;
            padding: 0.85rem 1.75rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(20, 184, 166, 0.4);
            transition: transform 0.15s ease, background 0.15s ease;
        }
        .hero-btn-primary:hover {
            background: #2dd4bf;
            transform: translateY(-2px);
        }
        .hero-btn-secondary {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            font-weight: 600;
            padding: 0.85rem 1.75rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.25);
            transition: background 0.15s ease;
        }
        .hero-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.22);
        }
        .section-wrap {
            max-width: 1100px;
            margin: 3.5rem auto;
            padding: 0 1.5rem;
        }
        .section-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .section-header h2 {
            font-size: 2rem;
            margin: 0 0 0.5rem;
            color: var(--color-ink);
        }
        .section-header p {
            color: var(--color-muted);
            margin: 0;
            font-size: 1.05rem;
        }
        .clinic-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(310px, 1fr));
            gap: 1.75rem;
        }
        .clinic-card {
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px var(--color-shadow);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .clinic-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.1);
        }
        .clinic-photo {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #e2e8f0;
            display: block;
        }
        .clinic-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .clinic-body h3 {
            margin: 0 0 0.5rem;
            font-size: 1.25rem;
            color: var(--color-primary-dark);
        }
        .clinic-meta {
            font-size: 0.9rem;
            color: var(--color-muted);
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        .clinic-meta strong {
            color: var(--color-ink);
        }
        .clinic-desc {
            font-size: 0.925rem;
            line-height: 1.5;
            color: var(--color-ink);
            flex: 1;
            margin-bottom: 1.25rem;
        }
        .doctor-pill-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
        }
        .doctor-pill {
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .doctor-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 700;
            display: grid;
            place-items: center;
            font-size: 1.1rem;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .feature-box {
            background: #f8fafc;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            text-align: center;
        }
        .feature-icon {
            display: grid;
            width: 3.25rem;
            height: 3.25rem;
            place-items: center;
            margin: 0 auto 1rem;
            color: var(--color-primary-dark);
            background: #e8f4f4;
            border: 1px solid #d5e7e8;
            border-radius: 0.85rem;
        }
        .feature-icon svg {
            width: 1.55rem;
            height: 1.55rem;
        }
        .feature-box h4 {
            margin: 0 0 0.5rem;
            font-size: 1.1rem;
        }
        .feature-box p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--color-muted);
        }
        .landing-footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 3rem 2rem 2rem;
            text-align: center;
            font-size: 0.9rem;
        }
        .landing-footer a {
            color: #38bdf8;
            text-decoration: none;
        }
    </style>
</head>
<body>

<header class="landing-header">
    <a class="brand" href="/CQMS/" style="text-decoration: none;">
        <span class="brand-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                <path d="M9 6.5h11M9 12h11M9 17.5h7"/>
                <circle cx="5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
                <circle cx="5" cy="12" r="1" fill="currentColor" stroke="none"/>
                <circle cx="5" cy="17.5" r="1" fill="currentColor" stroke="none"/>
            </svg>
        </span>
        <span>CQAMS</span>
    </a>
    <nav class="landing-nav">
        <a href="#clinics">Clinic Branches</a>
        <a href="#doctors">Medical Specialists</a>
        <a href="#features">Our Services</a>
        <a href="/CQMS/public/queue.php" target="_blank" style="color: #0f766e; font-weight: 600;">Live Waiting Queue ↗</a>
        <?php if ($currentUser !== null): ?>
            <a href="<?= escapeHtml(getDashboardPath($currentUser['role'])) ?>" class="button button-small" style="font-weight: 600;">
                Go to Workspace (<?= escapeHtml($currentUser['username']) ?>)
            </a>
            <a href="/CQMS/logout.php" style="color: var(--color-muted); font-size: 0.85rem;">Log out</a>
        <?php else: ?>
            <a href="/CQMS/login.php" class="button button-secondary button-small">Staff / Patient Login</a>
            <a href="/CQMS/register.php" class="button button-small" style="background: var(--color-primary); color: #fff;">Patient Register</a>
        <?php endif; ?>
    </nav>
</header>

<section class="hero">
    <div class="hero-shell">
        <span style="background: rgba(20, 184, 166, 0.2); color: #5eead4; padding: 0.35rem 0.9rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Multi-Branch Outpatient Network</span>
        <h1 style="margin-top: 1rem;">Fast, Reliable Healthcare & Consultation Appointments</h1>
        <p>Book appointments with licensed medical specialists, track your medications and medical history online, and monitor real-time clinic queue statuses without waiting in crowded lobbies.</p>
        <div class="hero-actions">
            <?php if ($currentUser !== null && $currentUser['role'] === 'patient'): ?>
                <a href="/CQMS/patient/book.php" class="hero-btn-primary">Book Doctor Appointment →</a>
                <a href="/CQMS/patient/" class="hero-btn-secondary">View My Patient Portal</a>
            <?php else: ?>
                <a href="/CQMS/register.php" class="hero-btn-primary">Register as a Patient →</a>
                <a href="/CQMS/login.php" class="hero-btn-secondary">Patient / Doctor Sign In</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Features Row -->
<div class="section-wrap" id="features">
    <div class="features-grid">
        <div class="feature-box">
            <div class="feature-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3.5" y="5.5" width="17" height="15" rx="2.2"/>
                    <path d="M7.5 3.5v4m9-4v4m-13 2h17M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01"/>
                </svg>
            </div>
            <h4>Online Booking</h4>
            <p>Select your preferred doctor, time slot, and reason with anti-double-booking protection.</p>
        </div>
        <div class="feature-box">
            <div class="feature-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="5.5" r="2"/>
                    <circle cx="6" cy="18.5" r="2"/>
                    <circle cx="18" cy="18.5" r="2"/>
                    <path d="M12 7.5v4m0 0H6v5m6-5h6v5"/>
                </svg>
            </div>
            <h4>Multiple Branches</h4>
            <p>Choose from our Central HQ, North Suburb, and South Specialist healthcare centres.</p>
        </div>
        <div class="feature-box">
            <div class="feature-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 3.5h8l4 4v13H6z"/>
                    <path d="M14 3.5v4h4M8 13h2l1.2-2.2 2.2 5 1.5-2.8h1.6"/>
                </svg>
            </div>
            <h4>Prescription Records</h4>
            <p>Access your doctor clinical notes, dosage instructions, and medications history anytime.</p>
        </div>
        <div class="feature-box">
            <div class="feature-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="4" width="16" height="16" rx="2.2"/>
                    <path d="M8 8h8M8 12h5M8 16h3"/>
                    <circle cx="16.5" cy="15.5" r="1" fill="currentColor" stroke="none"/>
                </svg>
            </div>
            <h4>Live Queue Display</h4>
            <p>Privacy-safe waiting-area displays keep you informed on consultation room calls.</p>
        </div>
    </div>
</div>

<!-- Clinic Branches Showcase -->
<section class="section-wrap" id="clinics">
    <div class="section-header">
        <p class="eyebrow" style="color: var(--color-primary); font-weight: 700;">Our Healthcare Facilities</p>
        <h2>Select a Clinic Branch</h2>
        <p>Choose the clinic closest to you for comprehensive general and specialist consultations.</p>
    </div>
    <div class="clinic-grid">
        <?php foreach ($clinics as $clinic): ?>
            <article class="clinic-card">
                <img src="<?= escapeHtml($clinic['photo_url'] ?: '/CQMS/assets/images/clinic_main.svg') ?>" alt="<?= escapeHtml($clinic['name']) ?>" class="clinic-photo">
                <div class="clinic-body">
                    <h3><?= escapeHtml($clinic['name']) ?></h3>
                    <div class="clinic-meta">
                        <div>📍 <?= escapeHtml($clinic['address']) ?></div>
                        <div>📞 <?= escapeHtml($clinic['phone']) ?></div>
                        <div>✉️ <?= escapeHtml($clinic['email'] ?: 'info@cqams.test') ?></div>
                    </div>
                    <p class="clinic-desc"><?= escapeHtml($clinic['description'] ?: 'Equipped with modern outpatient facilities and licensed consultation physicians.') ?></p>
                    <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center;">
                        <span class="status-badge" style="background: #e0f2fe; color: #0284c7;">Mon – Sat Open</span>
                        <?php if ($currentUser !== null && $currentUser['role'] === 'patient'): ?>
                            <a href="/CQMS/patient/book.php?clinic_id=<?= (int) $clinic['clinic_id'] ?>" class="button button-small">Book Here</a>
                        <?php else: ?>
                            <a href="/CQMS/login.php" class="button button-small button-secondary">Book Visit</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- Doctors Section -->
<section class="section-wrap" id="doctors">
    <div class="section-header">
        <p class="eyebrow" style="color: var(--color-primary); font-weight: 700;">Expert Medical Staff</p>
        <h2>Our Attending Specialists</h2>
        <p>Licensed practitioners available for scheduled and walk-in consultations.</p>
    </div>
    <div class="doctor-pill-grid">
        <?php foreach ($doctors as $doc): ?>
            <div class="doctor-pill">
                <div class="doctor-avatar">Dr</div>
                <div>
                    <strong style="color: var(--color-ink); font-size: 1rem;"><?= escapeHtml($doc['first_name'] . ' ' . $doc['last_name']) ?></strong>
                    <div style="color: var(--color-primary); font-size: 0.85rem; font-weight: 600;"><?= escapeHtml($doc['specialization']) ?></div>
                    <div style="color: var(--color-muted); font-size: 0.8rem; margin-top: 0.2rem;"><?= escapeHtml($doc['clinic_name'] ?: 'Central HQ') ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<footer class="landing-footer">
    <p style="margin-bottom: 0.5rem; font-weight: 600; color: #f8fafc;">CQAMS &middot; Clinic Queue and Appointment Management System</p>
    <p>A Web-Based Information Management System built with PHP 8, PDO, MySQL, HTML5, CSS3, and JavaScript.</p>
    <p style="margin-top: 1.5rem; font-size: 0.8rem; color: #64748b;">
        <a href="/CQMS/login.php">Staff Login</a> &middot;
        <a href="/CQMS/register.php">Patient Registration</a> &middot;
        <a href="/CQMS/public/queue.php">Public Queue Screen</a> &middot;
        <a href="/CQMS/docs/CQAMS-HANDOVER-GUIDE.md">Handover Documentation</a>
    </p>
</footer>

</body>
</html>
