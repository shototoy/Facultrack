<?php

if (!isset($header_config)) {
    die('Header configuration is required');
}

$page_title = $header_config['page_title'] ?? 'FaculTrack';
$page_subtitle = $header_config['page_subtitle'] ?? 'Sultan Kudarat State University - Isulan Campus';
$user_name = $header_config['user_name'] ?? 'Unknown User';
$user_role = $header_config['user_role'] ?? '';
$user_details = $header_config['user_details'] ?? '';
$stats = $header_config['stats'] ?? [];
$announcements_count = $header_config['announcements_count'] ?? 0;
$show_announcements_toggle = $header_config['show_announcements_toggle'] ?? true;
$announcements = $header_config['announcements'] ?? [];
require_once 'assets/php/announcement_functions.php';
?>

<style>
/* PAGE HEADER STYLES - INTERNAL */
.page-header {
    background: linear-gradient(135deg, var(--header-bg-primary), var(--header-bg-secondary));
    color: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="80" cy="20" r="20" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="60" r="15" fill="rgba(255,255,255,0.05)"/><circle cx="70" cy="80" r="25" fill="rgba(255,255,255,0.08)"/></svg>') no-repeat;
    background-size: cover;
    pointer-events: none;
}

.header-content {
    display: flex;
    align-items: stretch;
    justify-content: space-between;
    gap: 0;
    position: relative;
    z-index: 1;
    height: 100%;
    min-height: 80px;
}

.header-middle {
    flex: 0 0 33.333%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: center;
    padding: 0 20px;
    max-height: 100%;
}

.page-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0 0 4px 0;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.page-subtitle {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
    font-weight: 400;
}

.header-right {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    flex: 0 0 33.333%;
    max-height: 100%;
}

.header-stats {
    display: flex;
    gap: 12px;
    align-items: center;
    max-height: 100%;
}

.header-stat {
    text-align: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    min-width: 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    max-height: 100%;
}

.header-stat-number {
    font-size: 1.1rem;
    font-weight: bold;
    color: white;
    line-height: 1;
    margin-bottom: 2px;
}

.header-stat-label {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.8);
    line-height: 1;
    white-space: nowrap;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ANNOUNCEMENT STYLES - INTERNAL */
.page-header .announcement-toggle {
    background: rgba(255, 255, 255, 0.1) !important;
    color: white !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    width: 40px !important;
    height: 40px !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    font-size: 1.2rem !important;
    backdrop-filter: blur(10px) !important;
    transition: all 0.3s ease !important;
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.page-header .announcement-toggle:hover {
    background: rgba(255, 255, 255, 0.3) !important;
    transform: scale(1.05) !important;
    border-color: rgba(255, 255, 255, 0.5) !important;
}

.page-header .announcement-badge {
    position: absolute !important;
    top: -5px !important;
    right: -5px !important;
    background: rgba(255, 193, 7, 0.95) !important;
    color: white !important;
    border-radius: 50% !important;
    width: 18px !important;
    height: 18px !important;
    font-size: 0.7rem !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: bold !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 0 0 33.333%;
    margin-left: 0;
    padding-left: 0;
    max-height: 100%;
}

.user-avatar {
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    border: 2px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.user-name {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    color: white;
}

.user-role {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

.user-details {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
}

@media (max-width: 1024px) and (min-width: 769px) {
    .page-header {
        padding: 16px 20px;
    }
    
    .header-content {
        align-items: center;
        gap: 0;
    }
    
    .header-left {
        flex: 0 0 30%;
        display: flex;
        align-items: center;
        gap: 1rem;
        padding-right: 15px;
    }
    
    .user-avatar {
        border-radius: 50%;
        background: var(--accent-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        aspect-ratio: 1;
    }
    
    .user-info {
        flex: 1;
    }
    
    .user-name {
        margin: 0;
        font-size: 0.8rem;
        line-height: 1.2;
    }
    
    .user-role, .user-details {
        margin: 0;
        opacity: 0.8;
        font-size: 0.7rem;
        line-height: 1.1;
    }
    
    .header-middle {
        flex: 0 0 40%;
        padding: 0 15px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
        gap: 2px;
    }
    
    .page-title {
        font-size: 1.2rem;
        margin-bottom: 0;
        line-height: 1.2;
        white-space: pre-line;
    }
    
    .page-subtitle {
        font-size: 0.75rem;
        opacity: 0.8;
        line-height: 1.2;
        white-space: pre-line;
    }
    
    .header-right {
        flex: 0 0 30%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 6px;
        padding-left: 15px;
    }
    
    .header-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.25em;
        width: 100%;
        min-height: 0;
    }
    
    .header-stat {
        padding: 0.4em 0.2em;
        text-align: center;
        min-width: 0;
        height: auto;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .header-stat-number {
        font-size: 0.75em;
        line-height: 1;
    }
    
    .header-stat-label {
        font-size: 0.55em;
        line-height: 1;
    }
    
    .header-actions {
        display: flex;
        gap: 0.25em;
        width: 100%;
        min-height: 0;
    }
    
    .logout-btn {
        padding: 0.4em 0.5em;
        font-size: 0.7em;
        flex: 1;
        margin: 0;
        height: auto;
        min-height: 2em;
    }
    
    .page-header .announcement-toggle {
        width: auto !important;
        height: auto !important;
        min-height: 2em !important;
        font-size: 0.75em !important;
        flex: 1;
        padding: 0.4em !important;
    }
    
    .page-header .announcement-badge {
        width: 12px !important;
        height: 12px !important;
        font-size: 0.55rem !important;
    }
    
    .sidebar {
        width: 350px;
    }
}

@media (max-width: 768px) {
    .page-header {
        padding: 12px 16px;
    }
    
    /* Default phone behavior for non-faculty pages */
    body:not(.faculty-page) .page-header {
        position: relative !important;
        z-index: auto !important;
        transform: none !important;
        transition: none !important;
        top: auto !important;
        left: auto !important;
        right: auto !important;
        bottom: auto !important;
    }
    
    body:not(.faculty-page) .page-header.scrolling-up,
    body:not(.faculty-page) .page-header.scrolling-down {
        transform: none !important;
        position: relative !important;
    }
    
    body {
        overflow: auto !important;
        height: auto !important;
        min-height: 100vh !important;
    }
    
    .main-container {
        padding-top: 0 !important;
        height: auto !important;
    }
    
    .header-content {
        flex-direction: column;
        align-items: center;
        gap: 16px;
        text-align: center;
    }
    
    /* First row on phone - User info LEFT, Details RIGHT when not constrained */
    .header-content {
        flex-direction: row;
        align-items: stretch;
        gap: 1rem;
    }
    
    .header-left {
        order: 1;
        flex: 0 0 50%;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }
    
    .header-middle {
        order: 2;
        flex: 0 0 50%;
        padding: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: right;
        gap: 2px;
    }
    
    .page-title {
        font-size: 1.2rem;
        margin-bottom: 0;
        line-height: 1.3;
        white-space: pre-line;
    }
    
    .page-subtitle {
        font-size: 0.8rem;
        opacity: 0.8;
        line-height: 1.3;
        white-space: pre-line;
    }
    
    .user-avatar {
        border-radius: 50%;
        background: var(--accent-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        aspect-ratio: 1;
    }
    
    .user-info {
        text-align: left;
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    
    .user-name {
        margin: 0;
    }
    
    .user-role, .user-details {
        margin: 0;
        opacity: 0.8;
    }
    
    /* 2nd: Stats */
    .header-right {
        order: 3;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        margin-top: 16px;
    }
    
    .header-stats {
        order: 1;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px;
        width: 100%;
    }
    
    .header-stat {
        padding: 0.5em 0.75em;
        min-width: 0;
        height: auto;
        flex: 1;
    }
    
    .header-stat-number {
        font-size: 1em;
        line-height: 1.1;
    }
    
    .header-stat-label {
        font-size: 0.65em;
        line-height: 1.1;
    }
    
    /* 3rd: Logout/Announcement */
    .header-actions {
        order: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 12px;
        width: 100%;
    }
    
    /* When constrained - Details on TOP, User info BELOW */
    @media (max-width: 600px) {
        .header-content {
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        
        .header-middle {
            order: 1;
            flex: none;
            width: 100%;
            text-align: center;
        }
        
        .header-left {
            order: 2;
            flex: none;
            width: 100%;
            justify-content: center;
        }
        
        .page-title {
            font-size: 1.4rem;
        }
        
        .page-subtitle {
            font-size: 0.9rem;
        }
    }
    
    .logout-btn {
        padding: 0.6em 1.2em;
        font-size: 0.9em;
        margin: 0;
        height: auto;
        flex: 1;
    }
    
    .page-header .announcement-toggle {
        width: auto !important;
        height: auto !important;
        min-width: 2.5em !important;
        min-height: 2.5em !important;
        aspect-ratio: 1 !important;
        font-size: 1.2em !important;
        border-radius: 8px !important;
        padding: 0.5em !important;
    }
    
    .page-header .announcement-badge {
        width: 18px !important;
        height: 18px !important;
        font-size: 0.65rem !important;
    }
    
    /* Full width sidebar for phone */
    .sidebar {
        width: 100% !important;
        right: -100% !important;
    }
    
    .sidebar.open {
        right: 0 !important;
    }
}

<script>
let lastScrollTop = 0;
let header = null;
let ticking = false;

document.addEventListener('DOMContentLoaded', function() {
    header = document.querySelector('.page-header');
    
    console.log('Page header script loaded for:', document.body.className);
    
    // Add scroll listener for all pages, but behavior differs by page and screen size
    window.addEventListener('scroll', function() {
        console.log('Window scroll event triggered');
        if (!ticking) {
            requestAnimationFrame(handleScroll);
            ticking = true;
        }
    });
    
    // For faculty page, also listen on body scroll
    if (document.body.classList.contains('faculty-page')) {
        console.log('Adding body scroll listener for faculty page');
        document.body.addEventListener('scroll', function() {
            console.log('Body scroll event triggered');
            if (!ticking) {
                requestAnimationFrame(handleScroll);
                ticking = true;
            }
        });
    }
});

function handleScroll() {
    if (window.innerWidth <= 768) {
        // Faculty page specific behavior on phone
        if (document.body.classList.contains('faculty-page')) {
            console.log('Faculty page detected in scroll handler');
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
            
            console.log('Faculty scroll detected:', scrollTop);
            
            // Add scrolling class when user starts scrolling
            if (scrollTop > 50) {
                document.body.classList.add('scrolling');
                console.log('Added scrolling class');
            } else {
                document.body.classList.remove('scrolling');
                console.log('Removed scrolling class');
            }
            
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                // Scrolling down - hide header and remove padding
                header.classList.add('scrolling-down');
                header.classList.remove('scrolling-up');
                document.body.classList.add('header-hidden');
                console.log('Header hidden');
            } else if (scrollTop < lastScrollTop || scrollTop <= 20) {
                // Scrolling up or near top - show header and restore padding
                header.classList.add('scrolling-up');
                header.classList.remove('scrolling-down');
                document.body.classList.remove('header-hidden');
                console.log('Header shown');
            }
            
            // Ensure header is always visible when at the very top
            if (scrollTop <= 20) {
                header.classList.remove('scrolling-down', 'scrolling-up');
                document.body.classList.remove('header-hidden');
                document.body.classList.remove('scrolling');
                console.log('At top - reset all');
            }
            
            lastScrollTop = scrollTop;
            ticking = false;
        }
        return;
    }
    
    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    if (scrollTop > lastScrollTop && scrollTop > 100) {
        header.classList.add('scrolling-down');
        header.classList.remove('scrolling-up');
    } else if (scrollTop < lastScrollTop) {
        header.classList.add('scrolling-up');
        header.classList.remove('scrolling-down');
    }
    if (scrollTop <= 0) {
        header.classList.remove('scrolling-down', 'scrolling-up');
    }
    
    lastScrollTop = scrollTop;
    ticking = false;
}

window.addEventListener('resize', function() {
    if (header) {
        header.classList.remove('scrolling-down', 'scrolling-up');
        
        if (window.innerWidth <= 768) {
            header.style.position = 'relative';
            header.style.transform = 'none';
        }
    }
});
</script>

@media (min-width: 1025px) {
    .header-content {
        align-items: center;
    }
    
    .header-left {
        flex: 0 0 30%;
        display: flex;
        align-items: center;
        gap: 15px;
        padding-right: 20px;
    }
    
    .header-middle {
        flex: 0 0 40%;
        padding: 0 20px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
        gap: 4px;
    }
    
    .page-title {
        font-size: 1.8rem;
        margin-bottom: 0;
        line-height: 1.2;
        white-space: pre-line;
    }
    
    .page-subtitle {
        font-size: 1rem;
        opacity: 0.8;
        line-height: 1.2;
        white-space: pre-line;
    }
    
    .header-right {
        flex: 0 0 30%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-end;
        gap: 8px;
        padding-left: 20px;
        min-width: 0;
    }
    
    .header-stats {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
        width: 100%;
        min-width: 0;
    }
    
    .header-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
        width: 100%;
    }
    
    .logout-btn {
        margin: 0;
    }
    
    .sidebar {
        width: 400px;
    }
}

/* DESKTOP WIDTH CONSTRAINTS - Header actions wrap to row 2 */
@media (max-width: 1300px) and (min-width: 1025px) {
    .header-right {
        flex-direction: column;
        gap: 6px;
        min-width: 0;
    }
    
    .header-stats {
        flex-wrap: wrap;
        gap: 6px;
        width: 100%;
        justify-content: flex-end;
    }
    
    .header-actions {
        flex-wrap: wrap;
        justify-content: flex-end;
        width: 100%;
        gap: 6px;
    }
    
    .logout-btn {
        white-space: nowrap;
        font-size: 0.8rem;
        padding: 6px 12px;
    }
    
    .page-header .announcement-toggle {
        min-width: 36px !important;
        min-height: 36px !important;
        font-size: 1rem !important;
    }
}

/* FACULTY CARD BUTTONS - 2x2 grid on width constraints (PC mode) */
@media (max-width: 1400px) and (min-width: 1025px) {
    .faculty-actions {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        grid-template-rows: auto auto !important;
        gap: 3px !important;
        width: 100% !important;
    }
    
    .faculty-actions .btn-action {
        width: 100% !important;
        flex: none !important;
        justify-content: center !important;
        padding: 4px 2px !important;
        font-size: 0.7rem !important;
        text-align: center !important;
        white-space: nowrap !important;
        min-height: 28px !important;
        line-height: 1.2 !important;
    }
    
    .faculty-actions .btn-action i {
        margin-right: 3px !important;
        font-size: 0.65rem !important;
    }
    
    /* Ensure proper grid positioning */
    .faculty-actions .btn-action:nth-child(1) { grid-column: 1; grid-row: 1; }
    .faculty-actions .btn-action:nth-child(2) { grid-column: 2; grid-row: 1; }
    .faculty-actions .btn-action:nth-child(3) { grid-column: 1; grid-row: 2; }
    .faculty-actions .btn-action:nth-child(4) { grid-column: 2; grid-row: 2; }
}

/* COURSES TAB - Dynamic column layout on container constraints */
@media (min-width: 1025px) {
    .courses-grid {
        display: grid !important;
        gap: 20px !important;
        width: 100% !important;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;
    }
    
    /* Wide containers - 3+ columns */
    @media (min-width: 1400px) {
        .courses-grid {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
        }
    }
    
    /* Medium containers - 2 columns */
    @media (max-width: 1399px) and (min-width: 1200px) {
        .courses-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
    
    /* Constrained containers - single column */
    @media (max-width: 1199px) and (min-width: 1025px) {
        .courses-grid {
            grid-template-columns: 1fr !important;
        }
    }
}

/* Course card responsive adjustments - Desktop */
@media (max-width: 1399px) and (min-width: 1025px) {
    .course-card {
        width: 100% !important;
        max-width: none !important;
        min-height: auto !important;
    }
    
    .course-card .card-content {
        padding: 15px !important;
    }
    
    .course-card .course-code {
        font-size: 1.1rem !important;
    }
    
    .course-card .course-title {
        font-size: 0.95rem !important;
        line-height: 1.3 !important;
    }
    
    .course-card .course-details {
        font-size: 0.8rem !important;
    }
}

/* COURSES TAB - Tablet layout (769px-1024px) */
@media (max-width: 1024px) and (min-width: 769px) {
    .courses-grid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 15px !important;
        width: 100% !important;
    }
    
    .course-card {
        width: 100% !important;
        max-width: none !important;
    }
    
    .course-card .card-content {
        padding: 12px !important;
    }
    
    .course-card .course-code {
        font-size: 1rem !important;
    }
    
    .course-card .course-title {
        font-size: 0.9rem !important;
        line-height: 1.3 !important;
    }
    
    .course-card .course-details {
        font-size: 0.75rem !important;
    }
}

/* COURSES TAB - Phone layout (≤768px) */
@media (max-width: 768px) {
    .courses-grid {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 12px !important;
        width: 100% !important;
    }
    
    .course-card {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
    }
    
    .course-card .card-content {
        padding: 15px !important;
    }
    
    .course-card .course-code {
        font-size: 1.1rem !important;
    }
    
    .course-card .course-title {
        font-size: 0.95rem !important;
        line-height: 1.4 !important;
    }
    
    .course-card .course-details {
        font-size: 0.8rem !important;
    }
}

/* FLOATING ANNOUNCEMENT TOGGLE */
.floating-announcement-toggle {
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    z-index: 10000 !important;
    background: rgba(46, 125, 50, 0.95) !important;
    backdrop-filter: blur(10px) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25) !important;
    border-radius: 12px !important;
    width: 50px !important;
    height: 50px !important;
    font-size: 1.4rem !important;
    color: white !important;
    transition: all 0.3s ease !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.floating-announcement-toggle:hover {
    background: rgba(46, 125, 50, 1) !important;
    transform: scale(1.1) !important;
    box-shadow: 0 6px 30px rgba(0, 0, 0, 0.3) !important;
}

.floating-announcement-toggle .announcement-badge {
    position: absolute !important;
    top: -8px !important;
    right: -8px !important;
    background: #ff4444 !important;
    color: white !important;
    border-radius: 50% !important;
    width: 22px !important;
    height: 22px !important;
    font-size: 0.75rem !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: bold !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
    border: 2px solid white !important;
}

.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9998;
    opacity: 0;
    visibility: hidden;
    transition: all 0.35s ease;
    backdrop-filter: blur(3px);
}

.sidebar-overlay.active {
    opacity: 1;
    visibility: visible;
}

.sidebar {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    height: 100%;
    background: #ffffff;
    z-index: 9999;
    transition: right 0.35s ease;
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
}

.sidebar.open {
    right: 0;
}

.sidebar-header {
    background: var(--btn-primary-bg);
    color: white;
    padding: 10px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    margin: 0;
}

.sidebar-header-buttons {
    position: absolute;
    top: 0;
    right: 15px;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-btn, .sidebar-toggle-btn {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease;
}

.sidebar-toggle-btn {
    background: rgba(255, 255, 255, 0.2);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 1.4rem;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.sidebar-toggle-btn:hover {
    background: var(--btn-primary-hover);
    transform: scale(1.05);
}

.close-btn:hover, .sidebar-toggle-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.sidebar-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0 0 3px 0;
}

.sidebar-subtitle {
    font-size: 0.85rem;
    opacity: 0.8;
    margin: 0;
}

.announcements-container {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    margin: 0;
}

.announcement-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #ddd;
    transition: transform 0.2s ease;
}

.announcement-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.announcement-card.priority-high {
    border-left-color: #dc3545;
}

.announcement-card.priority-medium {
    border-left-color: #ffc107;
}

.announcement-card.priority-low {
    border-left-color: #28a745;
}

.announcement-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.announcement-priority {
    display: flex;
    align-items: center;
    gap: 5px;
}

.priority-text {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.announcement-date {
    font-size: 0.7rem;
    color: #666;
}

.announcement-title {
    margin: 0 0 10px 0;
    font-size: 1rem;
    font-weight: 600;
    color: #333;
}

.announcement-content {
    margin: 0 0 15px 0;
    font-size: 0.85rem;
    line-height: 1.5;
    color: #555;
}

.announcement-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.7rem;
    color: #888;
}

.announcement-author {
    font-style: italic;
}

.announcement-audience {
    background: rgba(46, 125, 50, 0.1);
    color: var(--text-green-secondary);
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
}
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar.classList.contains('open')) {
        closeSidebar();
    } else {
        sidebar.classList.add('open');
        overlay.classList.add('active');
    }
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
}
</script>

<div class="page-header">
    <div class="header-content">
        <div class="header-left">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user_name, 0, 2)); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                <?php if ($user_role): ?>
                    <div class="user-role"><?php echo htmlspecialchars($user_role); ?></div>
                <?php endif; ?>
                <?php if ($user_details): ?>
                    <div class="user-details"><?php echo htmlspecialchars($user_details); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="header-middle">
            <h1 class="page-title"><?php echo str_replace(' - ', "\n", htmlspecialchars($page_title)); ?></h1>
            <p class="page-subtitle"><?php echo htmlspecialchars($page_subtitle); ?></p>
        </div>
        
        <div class="header-right">
            <?php if (!empty($stats)): ?>
                <div class="header-stats">
                    <?php foreach ($stats as $stat): ?>
                        <div class="header-stat">
                            <div class="header-stat-number"><?php echo htmlspecialchars($stat['value']); ?></div>
                            <div class="header-stat-label"><?php echo htmlspecialchars($stat['label']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="header-actions">
                <a href="logout.php" class="logout-btn">Logout</a>
                <?php if ($show_announcements_toggle): ?>
                    <button class="announcement-toggle" onclick="toggleSidebar()">
                        <svg class="feather"><use href="#bell"></use></svg>
                        <?php if ($announcements_count > 0): ?>
                            <span class="announcement-badge"><?php echo $announcements_count; ?></span>
                        <?php endif; ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-header-buttons">
            <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
            <svg class="feather"><use href="#bell"></use></svg>
        </button>
        </div>
        <div class="sidebar-title">Announcements</div>
        <div class="sidebar-subtitle">Latest Updates</div>
    </div>
    <div class="announcements-container" id="announcementsContainer">
        <?php if (!empty($announcements)): ?>
            <?php foreach ($announcements as $announcement): ?>
                <?php echo renderAnnouncementCard($announcement); ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 3rem; margin-bottom: 10px;">
                    <svg class="feather feather-xl"><use href="#bell"></use></svg>
                </div>
                <h4>No Announcements</h4>
                <p>There are no announcements at this time.</p>
            </div>
        <?php endif; ?>
    </div>
</div>