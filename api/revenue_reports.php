<?php
require_once '../config/database.php';
requireLogin();

if (!isAdmin() && !isStaff()) {
    http_response_code(403);
    exit('Access denied');
}

function isValidReportDate($date) {
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    $parts = explode('-', $date);
    return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}

function pdfText($value) {
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$value);
    $value = preg_replace('/[^\x20-\x7E]/', '', $value);
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
}

function pdfTextAt($value, $x, $y, $size, $font = 'F1') {
    return 'BT /' . $font . ' ' . $size . ' Tf ' . $x . ' ' . $y . ' Td (' . pdfText($value) . ") Tj ET\n";
}

function createReportLogoJpeg() {
    $source = imagecreatefrompng(__DIR__ . '/../assets/logo.png');
    if ($source === false) {
        throw new RuntimeException('Unable to load the shop logo for the sales report.');
    }

    $logo = imagecreatetruecolor(240, 240);
    $white = imagecolorallocate($logo, 255, 255, 255);
    imagefill($logo, 0, 0, $white);
    imagecopyresampled($logo, $source, 0, 0, 0, 0, 240, 240, imagesx($source), imagesy($source));
    ob_start();
    imagejpeg($logo, null, 88);
    $jpeg = ob_get_clean();
    imagedestroy($source);
    imagedestroy($logo);

    if ($jpeg === false) {
        throw new RuntimeException('Unable to encode the shop logo for the sales report.');
    }

    return $jpeg;
}

function buildSalesPdf(array $sales, $periodLabel, $fromDate, $toDate, $logoJpeg) {
    $pages = array_chunk($sales, 34);
    if (empty($pages)) {
        $pages = [[]];
    }

    $objects = [];
    $pageReferences = [];
    $fontObjectId = 3 + count($pages) * 2;
    $boldFontObjectId = $fontObjectId + 1;
    $logoObjectId = $fontObjectId + 2;
    $totalRevenue = array_sum(array_map(function ($sale) {
        return (float)$sale['total_amount'];
    }, $sales));
    $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

    foreach ($pages as $index => $pageLines) {
        $pageObjectId = 3 + $index * 2;
        $contentObjectId = $pageObjectId + 1;
        $pageReferences[] = $pageObjectId . ' 0 R';

        $commands = "q\n66 0 0 66 48 750 cm\n/Im1 Do\nQ\n";
        $commands .= pdfTextAt('STAR FRAPPE SHOP', 130, 800, 22, 'F2');
        $commands .= pdfTextAt('135 Asinas Avenue, UEP 1, Catarman, Northern Samar', 130, 780, 10);
        $commands .= pdfTextAt('Generated: ' . date('F j, Y g:i A'), 130, 762, 9);
        $commands .= "0.12 0.28 0.16 RG\n1.5 w\n48 740 m\n564 740 l\nS\n";
        $commands .= pdfTextAt($periodLabel . ' Sales Report', 48, 716, 17, 'F2');
        $commands .= pdfTextAt('Report period: ' . $fromDate . ' to ' . $toDate, 48, 698, 10);
        $commands .= pdfTextAt('Completed sales: ' . count($sales) . '     Gross revenue: PHP ' . number_format($totalRevenue, 2), 48, 678, 11, 'F2');
        $commands .= "0.93 0.96 0.93 rg\n48 644 516 22 re\nf\n";
        $commands .= "0 0 0 rg\n";
        $commands .= pdfTextAt('Receipt', 58, 651, 9, 'F2');
        $commands .= pdfTextAt('Date and time', 250, 651, 9, 'F2');
        $commands .= pdfTextAt('Amount (PHP)', 455, 651, 9, 'F2');

        $rowY = 627;
        foreach ($pageLines as $sale) {
            $receipt = $sale['receipt_number'] ?: 'N/A';
            $commands .= pdfTextAt(substr($receipt, 0, 28), 58, $rowY, 9);
            $commands .= pdfTextAt(date('Y-m-d H:i', strtotime($sale['sale_date'])), 250, $rowY, 9);
            $commands .= pdfTextAt(number_format((float)$sale['total_amount'], 2), 455, $rowY, 9);
            $commands .= "0.88 0.9 0.88 RG\n0.5 w\n48 " . ($rowY - 7) . " m\n564 " . ($rowY - 7) . " l\nS\n";
            $rowY -= 17;
        }

        $commands .= pdfTextAt('Star Frappe Shop | ' . $periodLabel . ' Sales Report | Page ' . ($index + 1) . ' of ' . count($pages), 48, 35, 8);
        $objects[$pageObjectId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 ' . $fontObjectId . ' 0 R /F2 ' . $boldFontObjectId . ' 0 R >> /XObject << /Im1 ' . $logoObjectId . ' 0 R >> >> /Contents ' . $contentObjectId . ' 0 R >>';
        $objects[$contentObjectId] = '<< /Length ' . strlen($commands) . " >>\nstream\n" . $commands . "\nendstream";
    }

    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageReferences) . '] /Count ' . count($pages) . ' >>';
    $objects[$fontObjectId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
    $objects[$boldFontObjectId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
    $objects[$logoObjectId] = '<< /Type /XObject /Subtype /Image /Width 240 /Height 240 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($logoJpeg) . " >>\nstream\n" . $logoJpeg . "\nendstream";
    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $id => $object) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($id = 1; $id <= count($objects); $id++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}

function exportSalesPdf($pdo) {
    $period = $_GET['period'] ?? '';
    $date = $_GET['date'] ?? date('Y-m-d');
    if (!isValidReportDate($date)) {
        http_response_code(400);
        exit('Invalid report date');
    }
    $anchorDate = new DateTimeImmutable($date);

    if ($period === 'daily') {
        $from = $anchorDate;
        $to = $anchorDate;
        $label = 'Daily';
    } elseif ($period === 'weekly') {
        $from = $anchorDate->modify('monday this week');
        $to = $from->modify('+6 days');
        $label = 'Weekly';
    } elseif ($period === 'monthly') {
        $from = $anchorDate->modify('first day of this month');
        $to = $anchorDate->modify('last day of this month');
        $label = 'Monthly';
    } elseif ($period === 'annual') {
        $from = $anchorDate->setDate((int)$anchorDate->format('Y'), 1, 1);
        $to = $anchorDate->setDate((int)$anchorDate->format('Y'), 12, 31);
        $label = 'Annual';
    } else {
        http_response_code(400);
        exit('Invalid report period');
    }

    $fromDate = $from->format('Y-m-d');
    $toDate = $to->format('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT s.sale_date, s.total_amount, r.receipt_number
        FROM sale s
        LEFT JOIN receipt r ON s.sale_id = r.sale_id
        WHERE s.status = 'Completed' AND DATE(s.sale_date) BETWEEN ? AND ?
        ORDER BY s.sale_date ASC
    ");
    $stmt->execute([$fromDate, $toDate]);
    $sales = $stmt->fetchAll();

    $pdf = buildSalesPdf($sales, $label, $fromDate, $toDate, createReportLogoJpeg());
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="sales-' . $period . '-' . $fromDate . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    if (!isAdmin()) {
        http_response_code(403);
        exit('Access denied');
    }
    exportSalesPdf($pdo);
}

header('Content-Type: application/json');

if (isStaff()) {
    $selectedDate = $_GET['date'] ?? $_GET['from'] ?? date('Y-m-d');
    $toDate = $_GET['to'] ?? $selectedDate;
    if (!isValidReportDate($selectedDate) || !isValidReportDate($toDate) || $selectedDate !== $toDate) {
        http_response_code(400);
        echo json_encode(['error' => 'Staff reports are limited to one selected day.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(sale_id) AS total_sales, COALESCE(SUM(total_amount), 0) AS gross_revenue
        FROM sale
        WHERE status = 'Completed' AND DATE(sale_date) = ?
    ");
    $stmt->execute([$selectedDate]);
    $salesData = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT s.sale_id, s.sale_date, s.total_amount, r.receipt_number
        FROM sale s
        LEFT JOIN receipt r ON s.sale_id = r.sale_id
        WHERE s.status = 'Completed' AND DATE(s.sale_date) = ?
        ORDER BY s.sale_date DESC
    ");
    $stmt->execute([$selectedDate]);

    echo json_encode([
        'summary' => [
            'shop_sales' => (int)$salesData['total_sales'],
            'gross_revenue' => (float)$salesData['gross_revenue']
        ],
        'sales_list' => $stmt->fetchAll()
    ]);
    exit;
}

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
if (!isValidReportDate($from) || !isValidReportDate($to) || $from > $to) {
    http_response_code(400);
    echo json_encode(['error' => 'A valid date range is required.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        COUNT(sale_id) AS total_sales,
        COALESCE(SUM(total_amount), 0) AS gross_revenue
    FROM sale
    WHERE status = 'Completed' AND DATE(sale_date) BETWEEN ? AND ?
");
$stmt->execute([$from, $to]);
$salesData = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total_expenses
    FROM expense
    WHERE DATE(expense_date) BETWEEN ? AND ?
");
$stmt->execute([$from, $to]);
$totalExpenses = (float)$stmt->fetch()['total_expenses'];

$grossRevenue = (float)$salesData['gross_revenue'];
$totalSales = (int)$salesData['total_sales'];

$stmt = $pdo->prepare("
    SELECT DATE(sale_date) AS date, COALESCE(SUM(total_amount), 0) AS revenue
    FROM sale
    WHERE status = 'Completed' AND DATE(sale_date) BETWEEN ? AND ?
    GROUP BY DATE(sale_date)
    ORDER BY date DESC
");
$stmt->execute([$from, $to]);
$dailyRevenue = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT p.payment_method, COALESCE(SUM(s.total_amount), 0) AS total
    FROM sale s
    JOIN payment p ON s.sale_id = p.sale_id
    WHERE s.status = 'Completed' AND DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY p.payment_method
");
$stmt->execute([$from, $to]);
$paymentMethods = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT pr.product_name, SUM(si.quantity) AS qty_sold, SUM(si.subtotal) AS total_revenue
    FROM sale_item si
    JOIN sale s ON si.sale_id = s.sale_id
    JOIN product pr ON si.product_id = pr.product_id
    WHERE s.status = 'Completed' AND DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY pr.product_name
    ORDER BY qty_sold DESC
    LIMIT 10
");
$stmt->execute([$from, $to]);
$topProducts = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT s.sale_id, s.sale_date, s.total_amount, r.receipt_number
    FROM sale s
    LEFT JOIN receipt r ON s.sale_id = r.sale_id
    WHERE s.status = 'Completed' AND DATE(s.sale_date) BETWEEN ? AND ?
    ORDER BY s.sale_date DESC
");
$stmt->execute([$from, $to]);
$salesList = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT expense_name, amount, expense_date
    FROM expense
    WHERE DATE(expense_date) BETWEEN ? AND ?
    ORDER BY expense_date DESC
");
$stmt->execute([$from, $to]);
$expensesList = $stmt->fetchAll();

echo json_encode([
    'summary' => [
        'shop_sales' => $totalSales,
        'gross_revenue' => $grossRevenue,
        'total_expenses' => $totalExpenses,
        'revenue_after_expenses' => $grossRevenue - $totalExpenses,
        'average_sale' => $totalSales > 0 ? $grossRevenue / $totalSales : 0
    ],
    'daily_revenue' => $dailyRevenue,
    'payment_methods' => $paymentMethods,
    'top_products' => $topProducts,
    'sales_list' => $salesList,
    'expenses_list' => $expensesList
]);
?>
