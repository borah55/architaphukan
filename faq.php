<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Frequently Asked Questions';
$faqs = db_all('SELECT * FROM faq WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <h2 class="mb-4"><i class="fa fa-circle-question text-doge me-1"></i> FAQ</h2>
        <?php if (!$faqs): ?>
            <div class="alert alert-info">No FAQs available yet.</div>
        <?php else: ?>
            <div class="accordion" id="faqAccordion">
                <?php foreach ($faqs as $i => $f): ?>
                <div class="accordion-item bg-dark text-light border-secondary">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-dark text-light"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq<?= (int) $f['id'] ?>">
                            <?= e($f['question']) ?>
                        </button>
                    </h2>
                    <div id="faq<?= (int) $f['id'] ?>" class="accordion-collapse collapse"
                         data-bs-parent="#faqAccordion">
                        <div class="accordion-body"><?= nl2br(e($f['answer'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
