<?php
/**
 * Chart canvas partial.
 *
 * Wraps a <canvas> with a card. The data-config JSON contains a PARTIAL
 * chart config (type + data only) — the colors are merged in by app.js
 * at chart-init time so dark mode just works.
 *
 * @var string $chartId
 * @var string $title
 * @var string $jsonConfig     Partial config (type + data + options)
 * @var array  $tableRows      [['label' => string, 'value' => int], ...]
 */
$chartId    = (string) ($chartId ?? '');
$title      = (string) ($title ?? '');
$jsonConfig = (string) ($jsonConfig ?? '{}');
$tableRows  = is_array($tableRows ?? null) ? $tableRows : [];
?>
<div class="imreg-chart-card">
    <h3 class="imreg-chart-card__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
    <div class="imreg-chart-card__canvas-wrap">
        <canvas id="<?= htmlspecialchars($chartId, ENT_QUOTES, 'UTF-8') ?>"></canvas>
    </div>
    <script type="application/json" data-chart-config="<?= htmlspecialchars($chartId, ENT_QUOTES, 'UTF-8') ?>"><?= $jsonConfig /* trusted: built by DashboardController */ ?></script>
    <details class="imreg-chart-card__data">
        <summary>Data table (screen reader)</summary>
        <table>
            <thead>
                <tr><th scope="col">Label</th><th scope="col">Value</th></tr>
            </thead>
            <tbody>
            <?php foreach ($tableRows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($r['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($r['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </details>
</div>
