<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
    <?php $start = $user ? site_url('home') : site_url('register'); ?>
    <?php
    // Hero picture (optional): a photo in public/assets/img/hero-pet.jpg or .png fills the blob
    $heroImage = null;
    foreach (['hero-pet.jpg', 'hero-pet.png'] as $file) {
        if ($heroImage === null && is_file(FCPATH . 'assets/img/' . $file)) {
            $heroImage = 'assets/img/' . $file;
        }
    }
    ?>

    <!-- ===================== HERO ===================== -->
    <section class="hero">
        <!-- Paw prints in the background (decoration only) -->
        <span class="paw" style="top: 8%; left: 18%;">🐾</span>
        <span class="paw" style="top: 22%; left: 30%; font-size: 2.6rem;">🐾</span>
        <span class="paw" style="top: 14%; left: 49%;">🐾</span>
        <span class="paw" style="top: 70%; left: 47%; font-size: 2.2rem;">🐾</span>

        <div class="site-container hero-grid">
            <div class="hero-copy">
                <h1>Your Pet's Health Records and Vet Care – All in One Place</h1>
                <p>
                    Book clinic visits, follow every vaccine and medicine, and ask our AI what the vet's
                    words mean. Made for the pet parents of <?= esc($clinicName) ?>.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= $start ?>" class="btn-teal"><?= $user ? 'Go to Dashboard' : 'Create Free Account' ?></a>
                    <a href="#how" class="btn-white">How It Works</a>
                </div>
            </div>

            <!-- Blob with a pet photo, or a preview of the app when there is no photo -->
            <div class="hero-art">
                <div class="blob-back"></div>
                <?php if ($heroImage): ?>
                    <div class="blob-front has-image">
                        <img src="<?= base_url($heroImage) ?>" alt="A happy dog and cat at <?= esc($clinicName) ?>" class="blob-img">
                    </div>
                <?php else: ?>
                <div class="blob-front" aria-hidden="true">
                    <div class="mini-card">
                        <span class="mini-avatar">🐶</span>
                        <div><strong>Shiro</strong><small>Aspin · 3 yrs</small></div>
                        <span class="mini-tag">✓ Vaccines current</span>
                    </div>
                    <div class="mini-card">
                        <span class="mini-icon">📅</span>
                        <div><strong>Wellness exam</strong><small>Mon · 10:00 AM</small></div>
                    </div>
                    <div class="mini-card">
                        <span class="mini-icon">💊</span>
                        <div><strong>Omega-3 · 8:00 PM</strong><small>Dose due today</small></div>
                        <span class="mini-mark">Mark</span>
                    </div>
                </div>
                <?php endif ?>
            </div>
        </div>

        <!-- Torn-paper edge at the bottom of the hero -->
        <svg class="torn-edge" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true">
            <path fill="#fff" d="M0,50 L0,50 L18,55 L52,33 L68,36 L93,33 L123,43 L138,35 L165,56 L181,45 L197,57 L212,37 L233,33 L265,55 L280,44 L295,38 L318,56 L336,37 L368,49 L399,41 L416,42 L441,36 L472,34 L504,33 L537,43 L566,57 L590,59 L622,59 L647,49 L668,41 L689,35 L721,49 L751,61 L775,58 L798,34 L815,62 L842,40 L866,39 L895,56 L910,34 L941,50 L965,52 L998,61 L1030,59 L1046,35 L1068,60 L1084,33 L1107,58 L1130,54 L1155,31 L1183,52 L1202,37 L1231,33 L1251,48 L1269,45 L1295,55 L1324,35 L1343,58 L1369,47 L1387,57 L1418,47 L1440,48 L1440,90 L0,90 Z"/>
        </svg>
    </section>

    <!-- ===================== HOW IT WORKS ===================== -->
    <section class="section" id="how">
        <div class="site-container">
            <div class="section-head">
                <span class="kicker">How It Works</span>
                <h2>Four Easy Steps to Better Pet Care</h2>
            </div>

            <?php
            $steps = [
                ['title' => 'Create Your Account', 'text' => 'Sign up for free with your email in less than a minute'],
                ['title' => 'Add Your Pet', 'text' => 'Enter your pet\'s name, species, birthday and photo'],
                ['title' => 'Book a Visit', 'text' => 'Choose a day and time that works for you'],
                ['title' => 'Track Their Health', 'text' => 'Follow records, medicines and daily journal in one place'],
            ];
            ?>
            <div class="steps">
                <?php foreach ($steps as $i => $step): ?>
                    <div class="step-card">
                        <span class="step-number"><?= $i + 1 ?></span>
                        <h3><?= esc($step['title']) ?></h3>
                        <p><?= esc($step['text']) ?></p>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="provide">
                <div class="provide-title">With PawRecord, You Always Have:</div>
                <ul>
                    <li><i class="bi bi-check2"></i> Every visit and vaccine in one timeline</li>
                    <li><i class="bi bi-check2"></i> Reminders for every medicine dose</li>
                    <li><i class="bi bi-check2"></i> Plain-language answers to vet terms</li>
                    <li><i class="bi bi-check2"></i> A daily journal your vet can read</li>
                    <li><i class="bi bi-check2"></i> Online booking, even after clinic hours</li>
                </ul>
            </div>

            <div class="text-center">
                <a href="<?= $start ?>" class="btn-teal"><?= $user ? 'Go to Dashboard' : 'Get Started Now' ?></a>
            </div>
        </div>
    </section>

    <!-- ===================== SERVICES (FEATURES) ===================== -->
    <section class="section section-teal" id="features">
        <div class="site-container services-grid">
            <div class="services-intro">
                <span class="paw" style="position: static; font-size: 2.4rem;">🐾 🐾</span>
                <span class="kicker kicker-light">What do we offer?</span>
                <h2>Trusted Tools for Your Pet's Everyday Care</h2>
                <div class="pets-row" aria-hidden="true">🐱🐶🐕</div>
            </div>

            <?php
            $features = [
                ['icon' => 'bi-robot', 'title' => 'AI Medical Chatbot', 'text' => 'Ask what terms like "otitis" or "BID" mean and get a simple answer'],
                ['icon' => 'bi-clock-history', 'title' => 'Health Timeline', 'text' => 'Every visit, vaccine, medicine and appointment on one page'],
                ['icon' => 'bi-capsule', 'title' => 'Medication Tracker', 'text' => 'Dose reminders, one-tap "Mark given" and missed-dose alerts'],
                ['icon' => 'bi-journal-medical', 'title' => 'Symptom Journal', 'text' => 'Log appetite and mood daily; your vet gets a clear summary'],
                ['icon' => 'bi-calendar2-check', 'title' => 'Online Booking', 'text' => 'Pick a date and time for a check-up, vaccine or grooming'],
                ['icon' => 'bi-bell', 'title' => 'Smart Alerts', 'text' => 'Missed doses and reminders appear on your dashboard'],
            ];
            ?>
            <div class="service-cards">
                <?php foreach ($features as $f): ?>
                    <div class="service-card">
                        <i class="bi <?= $f['icon'] ?>"></i>
                        <h3><?= esc($f['title']) ?></h3>
                        <p><?= esc($f['text']) ?></p>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </section>

    <!-- ===================== WHAT WE CAN HELP WITH ===================== -->
    <section class="section">
        <div class="site-container help-grid">
            <div>
                <span class="kicker">What We Can Help With</span>
                <h2 class="help-title">Organized Care, Right When You Need It</h2>

                <?php
                $helps = [
                    ['icon' => 'bi-shield-check', 'title' => 'Vaccination Schedules', 'text' => 'See which vaccines are done and when the next booster is due'],
                    ['icon' => 'bi-capsule-pill', 'title' => 'Medicine Courses', 'text' => 'Finish antibiotics and other treatments without missing a dose'],
                    ['icon' => 'bi-heart-pulse', 'title' => 'Changes in Health', 'text' => 'Notice drops in appetite or energy early with the daily journal'],
                    ['icon' => 'bi-chat-heart', 'title' => 'Understanding Diagnoses', 'text' => 'Explain words like "atopic dermatitis" in plain language'],
                    ['icon' => 'bi-calendar-heart', 'title' => 'Follow-up Visits', 'text' => 'Get reminded about check-ups your vet asked for'],
                    ['icon' => 'bi-exclamation-triangle', 'title' => 'Emergencies', 'text' => 'Know when to call the clinic right away instead of waiting'],
                ];
                ?>
                <div class="help-list">
                    <?php foreach ($helps as $h): ?>
                        <div class="help-item">
                            <span class="help-icon"><i class="bi <?= $h['icon'] ?>"></i></span>
                            <div>
                                <div class="help-name"><?= esc($h['title']) ?></div>
                                <div class="help-text"><?= esc($h['text']) ?></div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>

                <a href="<?= $start ?>" class="btn-teal w-100 text-center mt-4"><?= $user ? 'Go to Dashboard' : 'Start Now – It\'s Free' ?></a>
            </div>

            <!-- Big picture card (emoji illustration, no photo needed) -->
            <div class="help-art" aria-hidden="true">
                <div class="help-art-pets">🐶🐱</div>
                <div class="help-art-note">
                    <strong>Shiro's next vaccine</strong>
                    <span>Anti-Rabies booster · in 12 days</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== FAQ ===================== -->
    <section class="section section-soft" id="faq">
        <div class="site-container faq">
            <div class="section-head">
                <span class="kicker">FAQs</span>
                <h2>Got Questions? We've Got Answers</h2>
            </div>

            <details>
                <summary>What is PawRecord?</summary>
                <p>PawRecord is the online system of <?= esc($clinicName) ?>. It keeps your pet's appointments, medical records, medicines and daily health journal in one place.</p>
            </details>
            <details>
                <summary>Is it free to use?</summary>
                <p>Yes. Creating an account and using PawRecord is free for the clinic's pet parents.</p>
            </details>
            <details>
                <summary>Can I book an appointment even when the clinic is closed?</summary>
                <p>Yes. Book anytime online. The clinic will confirm your request during clinic hours.</p>
            </details>
            <details>
                <summary>Can the AI chatbot diagnose my pet?</summary>
                <p>No. It explains medical words in simple language. For diagnosis and treatment, always talk to your veterinarian.</p>
            </details>
            <details>
                <summary>Who can see my pet's records?</summary>
                <p>Only you and the clinic's veterinarians and staff. Other pet owners can never see your pets.</p>
            </details>
            <details>
                <summary>I think this is an emergency. What do I do?</summary>
                <p>Do not wait for an online answer. Call the clinic or go to the nearest emergency veterinarian right away.</p>
            </details>
        </div>
    </section>
<?= $this->endSection() ?>
