<?php
/**
 * Restore-from-alumni modal partial.
 *
 * Uses native <dialog> for keyboard + focus-trap + Escape-closes behavior.
 *
 * @var int    $id
 * @var string $baseUrl
 * @var array  $statuses
 * @var string $csrf
 */
$id       = (int) ($id ?? 0);
$baseUrl  = (string) ($baseUrl ?? '');
$statuses = is_array($statuses ?? null) ? $statuses : ['pending', 'tentative', 'confirm', 'forfeit', 'reschedule'];
$csrf     = (string) ($csrf ?? '');
?>
<dialog id="imreg-restore-dialog-<?= $id ?>" class="imreg-modal" aria-labelledby="imreg-restore-title-<?= $id ?>">
    <form method="post" action="<?= htmlspecialchars($baseUrl . '/admin/registrations/' . $id . '/restore', ENT_QUOTES, 'UTF-8') ?>" class="imreg-modal__form">
        <div class="imreg-modal__body">
            <h2 id="imreg-restore-title-<?= $id ?>" class="imreg-modal__title">Restore registration</h2>
            <p class="imreg-modal__text">Choose a status for this restored registration. It will appear in the Registrations list.</p>
            <div class="imreg-field">
                <label for="new_status_<?= $id ?>" class="imreg-label">Status</label>
                <select id="new_status_<?= $id ?>" name="new_status" class="imreg-select">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="imreg-modal__footer">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button type="button" class="imreg-btn imreg-btn--ghost" data-imreg-close-dialog>Cancel</button>
            <button type="submit" class="imreg-btn imreg-btn--primary">Restore</button>
        </div>
    </form>
</dialog>
