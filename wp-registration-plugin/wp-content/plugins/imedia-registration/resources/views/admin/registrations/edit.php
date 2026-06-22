<?php
/**
 * Registration edit (and "new") view.
 *
 * @var string  $mode
 * @var ?int    $id
 * @var array   $row
 * @var array   $statuses
 * @var array   $payments
 * @var string  $csrf
 * @var string  $action
 * @var string  $submitText
 * @var string  $baseUrl
 * @var array   $errors
 * @var ?string $errorMsg
 */
$mode       = (string) ($mode ?? 'create');
$id         = $id ?? null;
$row        = is_array($row ?? null) ? $row : [];
$statuses   = $statuses ?? ['pending', 'tentative', 'confirm', 'forfeit', 'reschedule'];
$payments   = $payments ?? ['pending', 'deposit', 'fully_paid'];
$csrf       = (string) ($csrf ?? '');
$action     = (string) ($action ?? '');
$submitText = (string) ($submitText ?? 'Save');
$baseUrl    = (string) ($baseUrl ?? '');
$errors     = is_array($errors ?? null) ? $errors : [];
$errorMsg   = $errorMsg ?? null;
$resumePath = (string) ($row['resume_path'] ?? '');

$name       = \App\Core\View::old('name',       (string) ($row['name']   ?? ''));
$mobile     = \App\Core\View::old('mobile',     (string) ($row['mobile'] ?? ''));
$email      = \App\Core\View::old('email',      (string) ($row['email']  ?? ''));
$address    = \App\Core\View::old('address',    (string) ($row['address'] ?? ''));
$course     = \App\Core\View::old('course',     (string) ($row['course'] ?? ''));
$startDate  = \App\Core\View::old('start_date', (string) ($row['start_date'] ?? ''));
$endDate    = \App\Core\View::old('end_date',   (string) ($row['end_date']   ?? ''));
$status     = \App\Core\View::old('status',     (string) ($row['status']     ?? 'pending'));
$payment    = \App\Core\View::old('payment_status', (string) ($row['payment_status'] ?? 'pending'));
$paidAmount = \App\Core\View::old('paid_amount', (string) ($row['paid_amount'] ?? ''));
$paidAt     = \App\Core\View::old('paid_at',     (string) ($row['paid_at']     ?? ''));
$remark     = \App\Core\View::old('remark',      (string) ($row['remark']     ?? ''));
$dyn        = \App\Core\View::old('dynamic_data', $row['dynamic_data'] ?? []);
$dynJson    = is_array($dyn) ? json_encode($dyn, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string) $dyn;
?>
<?php if (is_string($errorMsg) && $errorMsg !== ''): ?>
    <div class="imreg-flash imreg-flash--error" role="alert"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" novalidate class="imreg-card" style="max-width:880px;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="reg[id]" value="<?= htmlspecialchars((string) ($id ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div class="imreg-grid--form">
        <div class="imreg-field">
            <label for="reg-name" class="imreg-label">Name <span class="imreg-label__required" aria-hidden="true">*</span></label>
            <input id="reg-name" name="reg[name]" required value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input <?= isset($errors['name']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['name'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field">
            <label for="reg-email" class="imreg-label">Email <span class="imreg-label__required" aria-hidden="true">*</span></label>
            <input id="reg-email" name="reg[email]" type="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input <?= isset($errors['email']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['email'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field">
            <label for="reg-mobile" class="imreg-label">Mobile</label>
            <input id="reg-mobile" name="reg[mobile]" type="tel" value="<?= htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input">
        </div>
        <div class="imreg-field">
            <label for="reg-course" class="imreg-label">Course <span class="imreg-label__required" aria-hidden="true">*</span></label>
            <input id="reg-course" name="reg[course]" required value="<?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input <?= isset($errors['course']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['course'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['course'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field imreg-field--full">
            <label for="reg-address" class="imreg-label">Address</label>
            <textarea id="reg-address" name="reg[address]" rows="2" class="imreg-textarea"><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="imreg-field">
            <label for="reg-start" class="imreg-label">Start date</label>
            <input id="reg-start" name="reg[start_date]" type="date" value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input">
        </div>
        <div class="imreg-field">
            <label for="reg-end" class="imreg-label">End date</label>
            <input id="reg-end" name="reg[end_date]" type="date" value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input">
        </div>
        <div class="imreg-field">
            <label for="reg-status" class="imreg-label">Status</label>
            <select id="reg-status" name="reg[status]" class="imreg-select">
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $status === $s ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="imreg-field">
            <label for="reg-payment" class="imreg-label">Payment status</label>
            <select id="reg-payment" name="reg[payment_status]" class="imreg-select">
                <?php foreach ($payments as $p): ?>
                    <option value="<?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>" <?= $payment === $p ? 'selected' : '' ?>>
                        <?= htmlspecialchars(str_replace('_', ' ', $p), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="imreg-field">
            <label for="reg-paid-amount" class="imreg-label">Paid amount <span class="imreg-text-muted" style="font-weight:400;">(required for non-pending payments)</span></label>
            <input id="reg-paid-amount" name="reg[paid_amount]" type="number" step="0.01" min="0"
                   value="<?= htmlspecialchars($paidAmount, ENT_QUOTES, 'UTF-8') ?>"
                   class="imreg-input <?= isset($errors['paid_amount']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['paid_amount'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['paid_amount'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field">
            <label for="reg-paid-at" class="imreg-label">Paid date <span class="imreg-text-muted" style="font-weight:400;">(required for non-pending payments)</span></label>
            <input id="reg-paid-at" name="reg[paid_at]" type="date"
                   value="<?= htmlspecialchars($paidAt, ENT_QUOTES, 'UTF-8') ?>"
                   class="imreg-input <?= isset($errors['paid_at']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['paid_at'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['paid_at'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field imreg-field--full">
            <label for="reg-remark" class="imreg-label">Remark <span class="imreg-text-muted" style="font-weight:400;">(required for non-pending payments)</span></label>
            <textarea id="reg-remark" name="reg[remark]" rows="2"
                      class="imreg-textarea <?= isset($errors['remark']) ? 'imreg-input--error' : '' ?>"><?= htmlspecialchars($remark, ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if (isset($errors['remark'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['remark'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field imreg-field--full">
            <label for="reg-dyn" class="imreg-label">dynamic_data <span class="imreg-text-muted" style="font-weight:400;">(JSON)</span></label>
            <textarea id="reg-dyn" name="reg[dynamic_data]" rows="6" class="imreg-textarea imreg-input--code"><?= htmlspecialchars((string) $dynJson, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="imreg-field imreg-field--full">
            <label for="reg-resume" class="imreg-label">Resume <span class="imreg-text-muted" style="font-weight:400;">(PDF, DOC, or DOCX. Max 5 MB.)</span></label>
            <?php if ($resumePath !== ''): ?>
                <div class="imreg-text-muted" style="font-size:0.75rem;margin-bottom:0.25rem;">
                    Current: <a href="<?= htmlspecialchars($baseUrl . '/admin/registrations/' . (int) ($id ?? 0) . '/resume', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($resumePath, ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            <?php endif; ?>
            <input id="reg-resume" name="resume" type="file"
                   accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                   class="imreg-input">
            <div class="imreg-help">Uploading a new file replaces the previous one.</div>
        </div>
    </div>

    <div class="imreg-actions imreg-actions--end">
        <button type="submit" class="imreg-btn imreg-btn--primary imreg-btn--lg"><?= htmlspecialchars($submitText, ENT_QUOTES, 'UTF-8') ?></button>
        <a href="<?= htmlspecialchars($baseUrl . '/admin/registrations', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--ghost">Cancel</a>
    </div>
</form>
