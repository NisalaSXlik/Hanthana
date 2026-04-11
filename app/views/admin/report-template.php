<?php
require_once __DIR__ . '/../../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? 'user') !== 'admin')) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

$report = $report ?? [];
$title = (string)($report['title'] ?? 'Admin Report');
$subtitle = (string)($report['subtitle'] ?? 'Structured report');
$summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
$sections = is_array($report['sections'] ?? null) ? $report['sections'] : [];
$generatedAt = (string)($report['generated_at'] ?? date('Y-m-d H:i:s'));
$generatedBy = (string)($report['generated_by'] ?? 'Admin');
$csvExportUrl = (string)($report['csv_export_url'] ?? '');
$autoPrint = !empty($autoPrint);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> · Hanthana Reports</title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #475569;
            --line: #d5deea;
            --surface: #ffffff;
            --shell: #e9eef5;
            --accent: #1d4ed8;
            --accent-soft: #eef4ff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            color: var(--ink);
            background: var(--shell);
            line-height: 1.35;
        }

        .report-shell {
            max-width: 1060px;
            margin: 1.2rem auto;
            padding: 0 1rem;
        }

        .report-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.8rem;
        }

        .report-controls__left,
        .report-controls__right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .report-btn,
        .report-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            text-decoration: none;
            border-radius: 999px;
            padding: 0.45rem 0.9rem;
            font-size: 0.84rem;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .report-btn {
            color: #fff;
            background: var(--accent);
            border-color: var(--accent);
        }

        .report-link {
            color: var(--accent);
            background: #fff;
            border-color: #c8d5e8;
        }

        .report-page {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 1.1rem 1.2rem 1.3rem;
        }

        .report-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid var(--line);
            padding-bottom: 0.8rem;
            margin-bottom: 0.85rem;
        }

        .report-brand {
            margin: 0;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: var(--muted);
        }

        .report-title {
            margin: 0.2rem 0 0.25rem;
            font-size: 1.36rem;
            letter-spacing: -0.01em;
            line-height: 1.15;
        }

        .report-subtitle {
            margin: 0;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .report-meta {
            min-width: 250px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #f8fafc;
            padding: 0.62rem 0.7rem;
        }

        .report-meta__row {
            display: flex;
            justify-content: space-between;
            gap: 0.7rem;
            font-size: 0.8rem;
            padding: 0.2rem 0;
            border-bottom: 1px dashed #d8e1ed;
        }

        .report-meta__row:last-child {
            border-bottom: none;
        }

        .report-meta__label {
            color: var(--muted);
            font-weight: 600;
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.5rem;
            margin-bottom: 0.8rem;
        }

        .report-summary-card {
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--accent-soft);
            padding: 0.58rem 0.66rem;
        }

        .report-summary-card p {
            margin: 0;
            font-size: 0.72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.045em;
            font-weight: 700;
        }

        .report-summary-card strong {
            margin-top: 0.25rem;
            display: block;
            font-size: 1.03rem;
            line-height: 1.1;
        }

        .report-section {
            margin-top: 0.85rem;
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .report-section h3 {
            margin: 0;
            padding: 0.62rem 0.7rem;
            font-size: 0.93rem;
            border-bottom: 1px solid var(--line);
            background: #f8fafc;
            letter-spacing: 0.01em;
        }

        .report-table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.78rem;
        }

        thead {
            background: #f1f5f9;
        }

        th,
        td {
            border-bottom: 1px solid #e2e8f0;
            padding: 0.44rem 0.5rem;
            text-align: left;
            vertical-align: top;
        }

        th {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.045em;
            color: #475569;
            font-weight: 700;
            white-space: nowrap;
        }

        tbody tr:nth-child(even) {
            background: #fcfdff;
        }

        .table-empty {
            padding: 0.8rem;
            margin: 0;
            color: var(--muted);
            font-size: 0.84rem;
        }

        @media (max-width: 760px) {
            .report-header {
                flex-direction: column;
            }

            .report-meta {
                width: 100%;
                min-width: 0;
            }

            .report-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .report-controls__left,
            .report-controls__right {
                width: 100%;
            }

            .report-btn,
            .report-link {
                width: 100%;
            }
        }

        @page {
            size: A4;
            margin: 12mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .report-shell {
                margin: 0;
                max-width: none;
                padding: 4mm;
            }

            .report-controls {
                display: none !important;
            }

            .report-page {
                border: 1px solid #b8c5d8;
                border-radius: 0;
                padding: 8mm 8mm 9mm;
                background: #fff;
            }

            .report-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="report-shell">
        <div class="report-controls">
            <div class="report-controls__left">
                <a class="report-link" href="<?php echo htmlspecialchars(BASE_PATH . 'index.php?controller=Admin&action=index'); ?>">Back to dashboard</a>
                <?php if ($csvExportUrl !== ''): ?>
                    <a class="report-link" href="<?php echo htmlspecialchars($csvExportUrl); ?>">Download CSV</a>
                <?php endif; ?>
            </div>
            <div class="report-controls__right">
                <button type="button" class="report-btn" onclick="window.print()">Download PDF</button>
            </div>
        </div>

        <article class="report-page">
            <header class="report-header">
                <div>
                    <p class="report-brand">Hanthana Admin Report</p>
                    <h1 class="report-title"><?php echo htmlspecialchars($title); ?></h1>
                    <p class="report-subtitle"><?php echo htmlspecialchars($subtitle); ?></p>
                </div>
                <div class="report-meta">
                    <div class="report-meta__row">
                        <span class="report-meta__label">Generated at</span>
                        <strong><?php echo htmlspecialchars($generatedAt); ?></strong>
                    </div>
                    <div class="report-meta__row">
                        <span class="report-meta__label">Generated by</span>
                        <strong><?php echo htmlspecialchars($generatedBy); ?></strong>
                    </div>
                    <div class="report-meta__row">
                        <span class="report-meta__label">Format</span>
                        <strong>Structured PDF Template</strong>
                    </div>
                </div>
            </header>

            <?php if (!empty($summary)): ?>
                <section class="report-summary">
                    <?php foreach ($summary as $item): ?>
                        <div class="report-summary-card">
                            <p><?php echo htmlspecialchars((string)($item['label'] ?? 'Metric')); ?></p>
                            <strong><?php echo htmlspecialchars((string)($item['value'] ?? '0')); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php foreach ($sections as $section): ?>
                <?php
                    $sectionTitle = (string)($section['title'] ?? 'Section');
                    $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
                    $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
                ?>
                <section class="report-section">
                    <h3><?php echo htmlspecialchars($sectionTitle); ?></h3>
                    <div class="report-table-wrap">
                        <?php if (!empty($rows)): ?>
                            <table>
                                <?php if (!empty($columns)): ?>
                                    <thead>
                                        <tr>
                                            <?php foreach ($columns as $column): ?>
                                                <th><?php echo htmlspecialchars((string)$column); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                <?php endif; ?>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <?php $cells = is_array($row) ? $row : [$row]; ?>
                                        <tr>
                                            <?php foreach ($cells as $cell): ?>
                                                <td><?php echo htmlspecialchars((string)$cell); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="table-empty">No data available for this section.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </article>
    </div>

    <?php if ($autoPrint): ?>
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    <?php endif; ?>
</body>
</html>
