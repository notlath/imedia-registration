<?php
/**
 * @var string  $baseUrl
 * @var array   $kpis
 * @var array   $thresholdSlots
 * @var string  $series30ConfigJson
 * @var string  $statusConfigJson
 * @var string  $topCoursesConfigJson
 * @var array   $series30Labels
 * @var array   $series30Data
 * @var array   $statusLabels
 * @var array   $statusData
 * @var array   $topLabels
 * @var array   $topData
 */
$total          = (int) ($kpis['total']      ?? 0);
$newToday       = (int) ($kpis['new_today']  ?? 0);
$newWeek        = (int) ($kpis['new_week']   ?? 0);
$thresholdCount = count($thresholdSlots);
?>
<div class="imreg-grid">
    <?php $label = 'Total inquiries';    $value = number_format($total);          $subtitle = 'All-time';   $variant = '';          include IMREG_VIEWS_PATH . '/partials/kpi-card.php'; ?>
    <?php $label = 'New today';          $value = number_format($newToday);       $subtitle = 'Since 00:00'; $variant = '';          include IMREG_VIEWS_PATH . '/partials/kpi-card.php'; ?>
    <?php $label = 'New this week';      $value = number_format($newWeek);        $subtitle = 'Last 7 days'; $variant = '';          include IMREG_VIEWS_PATH . '/partials/kpi-card.php'; ?>
    <?php $label = 'At / over threshold';$value = number_format($thresholdCount); $subtitle = 'Course slots'; $variant = $thresholdCount > 0 ? 'alert' : ''; include IMREG_VIEWS_PATH . '/partials/kpi-card.php'; ?>
</div>

<?php if ($thresholdSlots !== []): ?>
    <div class="imreg-banner imreg-banner--alert" role="alert" aria-live="polite">
        <span class="imreg-banner__dot" aria-hidden="true"></span>
        <div>
            <strong>Threshold reached.</strong>
            <?= count($thresholdSlots) ?> course slot<?= count($thresholdSlots) === 1 ? ' is' : 's are' ?> at or over the alert threshold.
            <a href="<?= htmlspecialchars($baseUrl . '/admin/alerts', ENT_QUOTES, 'UTF-8') ?>" style="color:inherit;text-decoration:underline;margin-left:0.5rem;">View alerts</a>
        </div>
    </div>
<?php endif; ?>

<div class="imreg-grid--3">
    <?php
    $chartId    = 'chart-series30';
    $title      = 'New registrations — last 30 days';
    $jsonConfig = $series30ConfigJson;
    $tableRows  = array_map(static fn ($l, $v) => ['label' => (string) $l, 'value' => (int) $v], $series30Labels, $series30Data);
    include IMREG_VIEWS_PATH . '/partials/chart-canvas.php';

    $chartId    = 'chart-status-breakdown';
    $title      = 'Status breakdown';
    $jsonConfig = $statusConfigJson;
    $tableRows  = array_map(static fn ($l, $v) => ['label' => (string) $l, 'value' => (int) $v], $statusLabels, $statusData);
    include IMREG_VIEWS_PATH . '/partials/chart-canvas.php';

    $chartId    = 'chart-top-courses';
    $title      = 'Top courses by confirm count';
    $jsonConfig = $topCoursesConfigJson;
    $tableRows  = array_map(static fn ($l, $v) => ['label' => (string) $l, 'value' => (int) $v], $topLabels, $topData);
    include IMREG_VIEWS_PATH . '/partials/chart-canvas.php';
    ?>
</div>
