<?php
/**
 * Accessible data-table partial.
 *
 * @var array<int, string>      $columns
 * @var array<int, array>        $rows
 * @var array<int, string>       $rowKeys
 * @var array<int, callable>     $cellCallbacks
 * @var string                   $empty
 * @var string                   $caption
 * @var string                   $id
 */
$columns = $columns ?? [];
$rows    = $rows ?? [];
$rowKeys = $rowKeys ?? [];
$cellCb  = $cellCb ?? [];
$empty   = (string) ($empty ?? 'No rows.');
$caption = (string) ($caption ?? '');
$id      = (string) ($id ?? '');
?>
<div class="imreg-table-wrap">
    <table id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" class="imreg-table">
        <?php if ($caption !== ''): ?>
            <caption class="imreg-table__caption"><?= htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') ?></caption>
        <?php endif; ?>
        <thead>
            <tr>
                <?php foreach ($columns as $col): ?>
                    <th scope="col"><?= htmlspecialchars((string) $col, ENT_QUOTES, 'UTF-8') ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= count($columns) ?>" class="imreg-table__empty"><?= htmlspecialchars($empty, ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($rowKeys as $i => $key): ?>
                            <td>
                                <?php if (isset($cellCb[$i]) && is_callable($cellCb[$i])) {
                                    echo $cellCb[$i]($row, $key);
                                } else {
                                    $val = is_array($row) ? ($row[$key] ?? '') : '';
                                    echo htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8');
                                } ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
