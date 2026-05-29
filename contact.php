<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Contact';
$errors = [];
$sent = false;
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if (is_post()) {
    csrf_require();
    $old = [
        'name'    => trim((string) post('name')),
        'email'   => trim((string) post('email')),
        'subject' => trim((string) post('subject')),
        'message' => trim((string) post('message')),
    ];
    // honeypot
    if (post('website') !== '') {
        $errors[] = 'Spam detected.';
    }
    if (strlen($old['name']) < 2)         $errors[] = 'Please enter your name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($old['subject']) < 3)      $errors[] = 'Subject is too short.';
    if (strlen($old['message']) < 10)     $errors[] = 'Message is too short.';
    if (!recaptcha_verify())              $errors[] = 'Captcha verification failed.';

    if (!$errors) {
        db_insert('contact_messages', [
            'name'       => $old['name'],
            'email'      => $old['email'],
            'subject'    => $old['subject'],
            'message'    => $old['message'],
            'ip_address' => client_ip(),
        ]);
        $adminEmail = setting('admin_email');
        if ($adminEmail) {
            $body = "New contact from {$old['name']} <{$old['email']}>\n\n"
                  . "Subject: {$old['subject']}\n\n"
                  . $old['message'];
            @mail($adminEmail, '[Contact] ' . $old['subject'], $body, 'From: ' . $old['email']);
        }
        $sent = true;
        $old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h2 class="mb-3"><i class="fa fa-envelope text-doge me-1"></i> Contact us</h2>
        <p class="text-muted">Have a question, suggestion, or partnership idea? Send us a message.</p>

        <?php if ($sent): ?>
            <div class="alert alert-success"><i class="fa fa-check"></i> Thank you - your message has been sent. We will reply by email.</div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Your name</label>
                            <input type="text" name="name" class="form-control" required value="<?= e($old['name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required value="<?= e($old['email']) ?>">
                        </div>
                    </div>
                    <div class="my-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" required value="<?= e($old['subject']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" rows="6" class="form-control" required><?= e($old['message']) ?></textarea>
                    </div>
                    <?= recaptcha_render() ?>
                    <button type="submit" class="btn btn-doge"><i class="fa fa-paper-plane me-1"></i> Send message</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
