<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
    <?php $start = $user ? site_url('home') : site_url('register'); ?>

    <!-- Hero: text on the left, a small preview of the app on the right -->
    <section class="hero">
        <div class="site-container hero-grid">
            <div>
                <span class="eyebrow">For pet owners of <?= esc($clinic) ?></span>
                <h1 class="hero-title">Your pet's health, all in one place.</h1>
                <p class="hero-text">
                    Book clinic visits, see every check-up and vaccine on one timeline, never miss a dose,
                    and ask our AI what the vet's words mean — anytime.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= $start ?>" class="site-btn site-btn-lg"><?= $user ? 'Go to dashboard' : 'Create a free account' ?></a>
                    <a href="#how" class="site-btn-ghost site-btn-lg">See how it works</a>
                </div>
                <div class="hero-points">
                    <span>✓ Free for clinic clients</span>
                    <span>✓ Works on phone and computer</span>
                </div>
            </div>

            <!-- App preview made with HTML only (no picture needed) -->
            <div class="preview" aria-hidden="true">
                <div class="preview-pet">
                    <div class="preview-avatar">🐶</div>
                    <div>
                        <div class="fw-bold">Shiro</div>
                        <div class="small text-muted">Aspin · 3 yrs · Male</div>
                    </div>
                    <span class="preview-badge">✓ Vaccines current</span>
                </div>
                <div class="preview-row is-green">
                    <span>📅</span>
                    <div><div class="fw-semibold">Wellness exam</div><div class="small">Mon, 10:00 AM · Dr. Cruz</div></div>
                </div>
                <div class="preview-row is-orange">
                    <span>💊</span>
                    <div><div class="fw-semibold">Omega-3 · 8:00 PM</div><div class="small">Dose due today</div></div>
                    <span class="preview-mark">Mark</span>
                </div>
                <div class="preview-chat">
                    <div class="preview-bubble is-user">What does "BID" mean?</div>
                    <div class="preview-bubble">Twice a day, about every 12 hours. 🐾</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="section" id="features">
        <div class="site-container">
            <div class="section-head">
                <span class="eyebrow">Features</span>
                <h2>Everything your pet's care needs</h2>
                <p>Simple tools that help you and your vet work together.</p>
            </div>

            <?php
            $features = [
                ['icon' => '🤖', 'title' => 'AI Medical Chatbot', 'text' => 'Ask what terms like "otitis" or "BID" mean and get a plain-language answer.'],
                ['icon' => '📅', 'title' => 'Health Timeline', 'text' => 'Every visit, vaccine, medicine and appointment of your pet on one page.'],
                ['icon' => '💊', 'title' => 'Medication Tracker', 'text' => 'Dose reminders, one-tap "Mark given", and alerts when a dose is missed.'],
                ['icon' => '📝', 'title' => 'Symptom Journal', 'text' => 'Log appetite, mood and symptoms daily. Your vet gets a clear summary.'],
                ['icon' => '🗓️', 'title' => 'Online Booking', 'text' => 'Pick a day and time for a check-up, vaccine or grooming in seconds.'],
                ['icon' => '🔔', 'title' => 'Smart Alerts', 'text' => 'Missed doses and reminders show up on your dashboard and bell.'],
            ];
            ?>
            <div class="feature-grid">
                <?php foreach ($features as $f): ?>
                    <div class="feature-card">
                        <div class="feature-icon"><?= $f['icon'] ?></div>
                        <h3><?= esc($f['title']) ?></h3>
                        <p><?= esc($f['text']) ?></p>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="section section-soft" id="how">
        <div class="site-container">
            <div class="section-head">
                <span class="eyebrow">How it works</span>
                <h2>Start in three steps</h2>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Create your account</h3>
                    <p>Sign up with your email. It takes less than a minute.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Add your pet</h3>
                    <p>Name, species, birthday and a photo. Add as many pets as you have.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Book and track</h3>
                    <p>Book a visit, follow the medicines, and keep a daily journal.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ: <details> opens and closes without any JavaScript -->
    <section class="section" id="faq">
        <div class="site-container faq">
            <div class="section-head">
                <span class="eyebrow">FAQ</span>
                <h2>Common questions</h2>
            </div>

            <details>
                <summary>Is PawRecord free?</summary>
                <p>Yes. Pet owners who are clients of <?= esc($clinic) ?> can use PawRecord for free.</p>
            </details>
            <details>
                <summary>Can the AI chatbot diagnose my pet?</summary>
                <p>No. It explains medical words and records in simple language. For diagnosis and treatment, always talk to your veterinarian.</p>
            </details>
            <details>
                <summary>Who can see my pet's records?</summary>
                <p>Only you and the clinic's veterinarians and staff. Other pet owners can never see your pets.</p>
            </details>
            <details>
                <summary>What if my pet has an emergency?</summary>
                <p>Do not wait for an online answer. Call the clinic or go to the nearest emergency veterinarian right away.</p>
            </details>
        </div>
    </section>

    <!-- Final call to action -->
    <section class="cta">
        <div class="site-container text-center">
            <h2>Ready to keep your pet's health on track?</h2>
            <a href="<?= $start ?>" class="site-btn site-btn-lg site-btn-light"><?= $user ? 'Go to dashboard' : 'Create a free account' ?></a>
        </div>
    </section>
<?= $this->endSection() ?>
