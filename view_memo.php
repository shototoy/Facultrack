<?php
require_once 'assets/php/common_utilities.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid announcement ID.");
}

$id = intval($_GET['id']);
$pdo = get_db_connection();

// Fetch announcement details
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name as created_by_name
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.user_id 
    WHERE a.announcement_id = ? AND a.is_active = TRUE
");
$stmt->execute([$id]);
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$announcement) {
    die("Announcement not found or inactive.");
}

$title = htmlspecialchars($announcement['title']);
$content = nl2br(htmlspecialchars($announcement['content']));
$authorName = $announcement['created_by_name'] ? htmlspecialchars($announcement['created_by_name']) : 'ADMINISTRATION';
$date = date('F d, Y', strtotime($announcement['created_at']));
$year = date('Y', strtotime($announcement['created_at']));

// Determine base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$dir = dirname($_SERVER['PHP_SELF']);
$appRoot = rtrim($protocol . "://" . $host . $dir, '/') . '/';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Announcement - <?php echo $title; ?></title>
    <base href="<?php echo $appRoot; ?>">
    <style>
        body { 
            font-family: 'Times New Roman', Times, serif; 
            margin: 0;
            padding: 0;
            min-height: 100vh;
            box-sizing: border-box;
            background: #525659; /* PDF viewer-like background */
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 20px;
            padding-bottom: 20px;
        }
        
        .page {
            width: 8.5in;
            min-height: 11in;
            background: white;
            position: relative;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
            padding: 0;
            overflow: hidden;
        }

        .background-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0; /* Changed from -10 to 0 to sit inside .page correctly */
        }
        .background-layer img {
            width: 100%;
            height: 100%;
            object-fit: fill; 
        }

        .content-wrapper {
            padding: 154px 96px 96px 96px; 
            position: relative;
            z-index: 1;
        }

        .document-header {
            position: absolute; 
            top: 15px;
            left: 25px;
            width: 75%;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            height: 90px;
            z-index: 2;
        }
        
        .logos-left {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-right: 25px;
        }
        
        .logo {
            width: 75px;
            height: 75px;
            object-fit: contain;
        }
        
        .header-text {
            text-align: left;
            color: #000;
            line-height: 1.1;
            font-family: 'Times New Roman', Times, serif;
            width: 100%;
        }
        .header-text h2 {
            font-size: 15pt;
            font-weight: bold;
            margin: 2px 0;
            color: #008000;
            text-transform: uppercase;
        }
        .header-text p {
            font-size: 10.5pt;
            margin: 0;
            font-style: italic;
        }

        .office-title-block {
            margin-top: 30px;  
            text-align: center;
            margin-bottom: 35px;
        }
        .office-title {
            font-family: 'Monotype Corsiva', 'Apple Chancery', cursive;
            font-size: 24pt;
            color: #000;
            line-height: 1;
        }
        .office-subtitle {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            margin-top: 8px;
        }

        .memo-info {
            margin-bottom: 20px;
            font-family: Arial, sans-serif;
            font-size: 11pt;
        }
        .memo-row {
            display: flex;
            margin-bottom: 8px;
        }
        .memo-label {
            font-weight: bold;
            width: 100px;
            flex-shrink: 0;
        }
        .memo-value {
            font-weight: bold;
        }
        .memo-line {
                border-bottom: 2px solid #000;
                margin-bottom: 30px;
        }

        .content { 
            font-size: 12pt; 
            line-height: 1.5; 
            color: #000; 
            margin-bottom: 50px; 
            white-space: pre-wrap; 
            text-align: justify;
            font-family: Arial, sans-serif;
        }
        
        .footer-text {
            position: absolute;
            bottom: 48px;
            left: 96px; 
            right: 96px;
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 7pt;
            color: #000;
            border-top: 1px dotted #ccc;
            padding-top: 5px;
            background: transparent;
            z-index: 2;
        }
        .footer-text span {
            font-weight: bold;
        }

        @media print {
            @page { margin: 0; size: letter portrait; }
            body { 
                background: white;
                display: block;
                padding: 0;
            }
            .page {
                width: 100%;
                height: 100%;
                min-height: auto;
                box-shadow: none;
                margin: 0;
            }
            .background-layer {
                display: block !important;
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="background-layer">
            <img src="assets/images/announcement.png" alt="">
        </div>

        <div class="content-wrapper">
            <!-- Header removed as requested -->
            <div class="document-header" style="display: none;">
                
            </div>

            <div class="office-title-block">
                <div class="office-title">Office of the Campus Director</div>
                <div class="office-subtitle">Isulan Campus</div>
            </div>

            <div class="memo-info">
                <div style="font-weight: bold; margin-bottom: 20px;">OFFICE MEMORANDUM No. <?php echo $id; ?>, Series <?php echo $year; ?></div>
                
                <div class="memo-row"><span class="memo-label">TO:</span> <span class="memo-value">CAMPUS DESIGNATED PERSONNEL</span></div>
                <div class="memo-row"><span class="memo-label">FROM:</span> <span class="memo-value"><?php echo strtoupper($authorName); ?></span></div>
                <div class="memo-row" style="margin-left: 100px; margin-top: -8px; font-weight: normal; font-size: 10pt;">Campus Director</div>
                
                <div class="memo-row"><span class="memo-label">SUBJECT:</span> <span class="memo-value" style="text-transform: uppercase;"><?php echo $title; ?></span></div>
                <div class="memo-row"><span class="memo-label">DATE:</span> <span class="memo-value"><?php echo $date; ?></span></div>
            </div>
            
            <div class="memo-line"></div>
            
            <div class="content"><?php echo $content; ?></div>
            
            <div class="signatory">
                <p>For your information and guidance.</p>
            </div>
        </div>

        <!-- Footer removed as requested -->
        <div class="footer-text" style="display: none;">
            <span>VISION:</span> A leading University in advancing scholarly innovation, multi-cultural convergence, and responsive public service in a borderless Region. | 
            <span>MISSION:</span> The University shall primarily provide advanced instruction and professional training in science and technology, agriculture, fisheries, education and other relevant fields of study. It shall also undertake research and extension services, and provide progressive leadership in its areas of specialization. | 
            <span>MAXIM:</span> Generator of Solutions. | 
            <span>CORE VALUES:</span> Patriotism, Respect, Integrity, Zeal, Excellence in Public Service.
        </div>
    </div>

    <script>
        // Optional: Auto-print after a delay if needed, but per request this is just view memo
        // setTimeout(() => {
        //     window.print();
        // }, 1000);
    </script>
</body>
</html>
