<main class="auth-shell register-shell">
    <section class="register-card">
        <div class="register-brand">
            <a class="brand centered" href="<?= h(public_url('')) ?>">
                <img class="brand-logo" src="<?= h(public_url('assets/img/logo.png')) ?>" alt="لوگوی گپ‌هوش">
                <span>گپ‌هوش</span>
            </a>

            <div class="register-heading">
                <span class="auth-badge">شروع رایگان</span>
                <h1>ساخت حساب کاربری</h1>
                <p>برای شروع گفتگو با هوش مصنوعی، شماره موبایل خود را تأیید کن. ایمیل اختیاری است.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= h(public_url('register')) ?>" class="register-form" id="registerForm" data-base-url="<?= h(public_url('')) ?>">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

            <div class="form-section">
                <div class="section-label">
                    <strong>۱. اطلاعات اصلی</strong>
                    <span>نام و شماره موبایل برای ساخت حساب لازم است.</span>
                </div>

                <label>
                    نام و نام خانوادگی
                    <input name="name" autocomplete="name" required placeholder="مثلاً شایان نماینده" value="<?= h($old['name'] ?? '') ?>">
                </label>

                <label>
                    شماره موبایل
                    <input name="mobile" id="registerMobile" inputmode="tel" autocomplete="tel" required placeholder="09123456789" value="<?= h($old['mobile'] ?? '') ?>">
                </label>
            </div>

            <div class="otp-box">
                <div>
                    <strong>۲. تأیید شماره موبایل</strong>
                    <p>کد یکبارمصرف از طریق ملی‌پیامک ارسال می‌شود و فقط چند دقیقه اعتبار دارد.</p>
                    <small class="field-hint" id="otpStatus">شماره موبایل را وارد کن و روی ارسال کد بزن.</small>
                </div>

                <button class="btn ghost otp-send-btn" type="button" id="sendOtpBtn">
                    ارسال کد تأیید
                </button>

                <label class="otp-code-field">
                    کد تأیید پیامکی
                    <input name="otp_code" inputmode="numeric" autocomplete="one-time-code" required placeholder="مثلاً 374143">
                </label>
            </div>

            <div class="form-section">
                <div class="section-label">
                    <strong>۳. اطلاعات تکمیلی</strong>
                    <span>رمز عبور حداقل ۸ کاراکتر باشد.</span>
                </div>

                <label>
                    ایمیل <span class="optional">اختیاری</span>
                    <input name="email" type="email" autocomplete="email" placeholder="you@example.com" value="<?= h($old['email'] ?? '') ?>">
                </label>

                <label>
                    رمز عبور
                    <input name="password" type="password" autocomplete="new-password" required placeholder="حداقل ۸ کاراکتر">
                </label>
            </div>

            <p class="form-note">
                با ثبت‌نام، <a href="<?= h(public_url('terms')) ?>">قوانین استفاده</a> و
                <a href="<?= h(public_url('privacy')) ?>">حریم خصوصی</a> گپ‌هوش را می‌پذیری.
            </p>

            <button class="btn primary full register-submit" type="submit">
                ساخت حساب و ورود به گپ‌هوش
            </button>
        </form>

        <p class="switch-link">قبلاً ثبت‌نام کردی؟ <a href="<?= h(public_url('login')) ?>">وارد شو</a></p>
    </section>
</main>