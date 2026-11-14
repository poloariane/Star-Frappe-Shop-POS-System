<?php
require_once 'config/database.php';
requireAdmin();

$period = $_GET['period'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');
$dateParts = explode('-', (string)$date);
if (count($dateParts) !== 3 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date) || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])) {
    http_response_code(400);
    exit('Invalid report date');
}

$anchorDate = new DateTimeImmutable($date);
if ($period === 'daily') {
    $from = $anchorDate;
    $to = $anchorDate;
    $periodLabel = 'Daily';
} elseif ($period === 'weekly') {
    $from = $anchorDate->modify('monday this week');
    $to = $from->modify('+6 days');
    $periodLabel = 'Weekly';
} elseif ($period === 'monthly') {
    $from = $anchorDate->modify('first day of this month');
    $to = $anchorDate->modify('last day of this month');
    $periodLabel = 'Monthly';
} elseif ($period === 'annual') {
    $from = $anchorDate->setDate((int)$anchorDate->format('Y'), 1, 1);
    $to = $anchorDate->setDate((int)$anchorDate->format('Y'), 12, 31);
    $periodLabel = 'Annual';
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
$totalRevenue = array_sum(array_map(function ($sale) {
    return (float)$sale['total_amount'];
}, $sales));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($periodLabel) ?> Sales Report - Star Frappe Shop</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 32px; color: #202820; background: #edf1ed; font-family: Arial, sans-serif; }
        .report { max-width: 900px; margin: auto; padding: 42px 48px; background: #fff; box-shadow: 0 8px 30px rgba(0,0,0,.1); }
        .brand { display: flex; align-items: center; gap: 20px; padding-bottom: 22px; border-bottom: 3px solid #1a472a; }
        .brand img { width: 96px; height: 96px; object-fit: contain; }
        .brand h1 { margin: 0 0 8px; color: #1a472a; font-size: 27px; letter-spacing: 1px; }
        .brand p { margin: 4px 0; color: #59645a; font-size: 13px; }
        .report-heading { display: flex; justify-content: space-between; gap: 20px; align-items: end; margin: 28px 0 18px; }
        .report-heading h2 { margin: 0 0 7px; color: #1a472a; font-size: 21px; }
        .report-heading p, .generated { margin: 0; color: #687168; font-size: 12px; }
        .summary { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin: 20px 0 25px; }
        .summary div { padding: 15px 18px; border: 1px solid #e1e9e1; border-radius: 8px; background: #f7faf7; }
        .summary span { display: block; margin-bottom: 7px; color: #627062; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .summary strong { color: #1a472a; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { padding: 12px 10px; text-align: left; color: #fff; background: #1a472a; text-transform: uppercase; letter-spacing: .4px; }
        td { padding: 11px 10px; border-bottom: 1px solid #e6ebe6; }
        tbody tr:nth-child(even) { background: #f7faf7; }
        td:last-child, th:last-child { text-align: right; }
        .empty { padding: 30px; text-align: center; color: #697269; border: 1px dashed #cbd6cb; }
        .footer { margin-top: 30px; padding-top: 14px; border-top: 1px solid #dfe5df; color: #748074; font-size: 10px; text-align: center; }
        .print-action { display: block; margin: 18px auto; padding: 12px 22px; border: 0; border-radius: 8px; color: white; background: #1a472a; font-size: 14px; font-weight: 700; cursor: pointer; }
        @page { size: A4 portrait; margin: 14mm; }
        @media print {
            body { padding: 0; background: #fff; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .report { max-width: none; padding: 0; box-shadow: none; }
            .print-action { display: none; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <button class="print-action" type="button" onclick="window.print()">Print report / Save as PDF</button>
    <main class="report">
        <header class="brand">
            <img src="assets/logo.png" alt="Star Frappe Shop logo">
            <div>
                <h1>STAR FRAPPE SHOP</h1>
                <p>135 Asinas Avenue, UEP 1, Catarman, Northern Samar</p>
                <p>Official Sales Report</p>
                <p>Shop Owner: <?= htmlspecialchars(getShopOwnerName($pdo), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </header>
        <section class="report-heading">
            <div>
                <h2><?= htmlspecialchars($periodLabel) ?> Sales Report</h2>
                <p>Report period: <?= htmlspecialchars(date('F j, Y', strtotime($fromDate))) ?> – <?= htmlspecialchars(date('F j, Y', strtotime($toDate))) ?></p>
            </div>
            <p class="generated">Generated <?= htmlspecialchars(date('F j, Y g:i A')) ?></p>
        </section>
        <section class="summary">
            <div><span>Completed sales</span><strong><?= count($sales) ?></strong></div>
            <div><span>Gross revenue</span><strong>₱<?= number_format($totalRevenue, 2) ?></strong></div>
        </section>
        <?php if ($sales): ?>
        <table>
            <thead><tr><th>Receipt number</th><th>Date and time</th><th>Total amount</th></tr></thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?= htmlspecialchars($sale['receipt_number'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($sale['sale_date']))) ?></td>
                    <td>₱<?= number_format((float)$sale['total_amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty">No completed sales were recorded during this period.</div>
        <?php endif; ?>
        <footer class="footer">Star Frappe Shop · 135 Asinas Avenue, UEP 1, Catarman, Northern Samar</footer>
    </main>
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
