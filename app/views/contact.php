<header class="site-header landing-header">
    <a class="brand" href="<?= h(public_url('')) ?>" aria-label="گپ‌هوش">
        <img class="brand-logo" src="<?= h(public_url('assets/img/logo.png')) ?>" alt="لوگوی گپ‌هوش" loading="eager">
        <span>گپ‌هوش</span>
    </a>
    <nav class="top-nav" aria-label="منوی اصلی">
        <a href="<?= h(public_url('')) ?>#usecases">کاربردها</a>
        <a href="<?= h(public_url('')) ?>#features">امکانات</a>
        <a href="<?= h(public_url('')) ?>#security">امنیت</a>
        <a href="<?= h(public_url('contact')) ?>">ارتباط با ما</a>
        <a href="<?= h(public_url('login')) ?>">ورود</a>
        <a class="btn small primary" href="<?= h(public_url('register')) ?>">شروع رایگان</a>
    </nav>
</header>

<main>
    <section class="contact-hero">
        <div class="contact-copy">
            <p class="eyebrow">ارتباط با گپ‌هوش</p>
            <h1>برای پشتیبانی، همکاری یا گزارش مشکل پیام بده.</h1>
            <p>اگر در ثبت‌نام، اتصال مدل‌های هوش مصنوعی، پاسخ‌دهی، امنیت یا پیشنهاد همکاری سوالی داری، از یکی از مسیرهای زیر ارتباط بگیر. پیام‌ها را واضح و همراه با اسکرین‌شات/متن خطا بفرست تا سریع‌تر قابل بررسی باشد.</p>
            <div class="hero-actions">
                <a class="btn primary large" href="mailto:<?= h(config('contact_email')) ?>">ارسال ایمیل</a>
                <a class="btn ghost large" href="<?= h(config('contact_telegram')) ?>" rel="noopener" target="_blank">پیام در تلگرام</a>
            </div>
        </div>
        <div class="contact-card main-contact-card">
            <img src="<?= h(public_url('assets/img/logo.png')) ?>" alt="گپ‌هوش" loading="lazy">
            <h2>گپ‌هوش</h2>
            <p>هوش مصنوعی فارسی در gaphoosh.ir</p>
        </div>
    </section>

    <section class="section contact-section">
        <div class="contact-grid">
            <article class="contact-card">
                <span class="contact-icon">@</span>
                <h2>ایمیل</h2>
                <p>برای پشتیبانی، گزارش خطا و مکاتبه رسمی.</p>
                <a class="contact-link" href="mailto:<?= h(config('contact_email')) ?>"><?= h(config('contact_email')) ?></a>
            </article>
            <article class="contact-card">
                <span class="contact-icon">↗</span>
                <h2>تلگرام</h2>
                <p>برای پیام سریع و پیگیری کوتاه.</p>
                <a class="contact-link" href="<?= h(config('contact_telegram')) ?>" rel="noopener" target="_blank">t.me/SudoShayanNA</a>
            </article>
            <article class="contact-card">
                <span class="contact-icon">⌘</span>
                <h2>گیت‌هاب</h2>
                <p>برای مشاهده پروژه‌ها و ارتباط فنی.</p>
                <a class="contact-link" href="<?= h(config('contact_github')) ?>" rel="noopener" target="_blank">github.com/roseshayan</a>
            </article>
        </div>
    </section>

    <section class="section split premium-split contact-note">
        <div>
            <p class="eyebrow">قبل از ارسال پیام</p>
            <h2>برای خطاهای فنی، این اطلاعات را بفرست.</h2>
            <p>اگر مشکل مربوط به چت یا مدل‌هاست، خروجی پنل مدیریت بخش عیب‌یابی Dahl، نام مدل، زمان تست و متن دقیق خطا را ضمیمه کن.</p>
        </div>
        <div class="security-card">
            <ul class="clean-list">
                <li>آدرس صفحه‌ای که مشکل دارد</li>
                <li>متن کامل خطا یا اسکرین‌شات</li>
                <li>نام مدل انتخاب‌شده</li>
                <li>زمان تقریبی وقوع مشکل</li>
            </ul>
        </div>
    </section>
</main>

<footer class="site-footer">
    <span>© <?= date('Y') ?> گپ‌هوش</span>
    <span><a href="<?= h(public_url('contact')) ?>">ارتباط با ما</a> · <a href="<?= h(public_url('terms')) ?>">قوانین</a> · <a href="<?= h(public_url('privacy')) ?>">حریم خصوصی</a></span>
</footer>
