<?php
/**
 * KPI card partial.
 *
 * @var string $label
 * @var string $value
 * @var string $subtitle
 * @var string $variant    'alert' or '' (default)
 */
$label    = (string) ($label ?? '');
$value    = (string) ($value ?? '');
$subtitle = (string) ($subtitle ?? '');
$variant  = (string) ($variant ?? '');
$class    = $variant === 'alert' ? 'imreg-kpi imreg-kpi--alert' : 'imreg-kpi';
?>
<div class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>">
    <div class="imreg-kpi__label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="imreg-kpi__value"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></div>
    <?php if ($subtitle !== ''): ?>
        <div class="imreg-kpi__subtitle"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>
