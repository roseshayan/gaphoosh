<div class="chat-app" data-base-url="<?= h(public_url('')) ?>">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-head">
            <a class="brand" href="<?= h(public_url('')) ?>">
                <img class="brand-logo" src="<?= h(public_url('assets/img/logo.png')) ?>" alt="لوگوی گپ‌هوش">
                <span>گپ‌هوش</span>
            </a>
            <button class="icon-btn mobile-only" id="closeSidebar" type="button" aria-label="بستن منو">×</button>
        </div>

        <button class="btn primary full" id="newChatBtn" type="button">+ گفتگوی جدید</button>
        <div class="search-box">
            <input id="chatSearch" placeholder="جستجو در گفتگوها…" autocomplete="off">
        </div>
        <div class="chat-list" id="conversationList" aria-label="فهرست گفتگوها"></div>

        <div class="sidebar-links">
            <?php if ((int) ($user['is_admin'] ?? 0) === 1): ?>
                <a class="btn ghost full" href="<?= h(public_url('admin')) ?>">پنل مدیریت</a>
            <?php endif; ?>
        </div>
        <form method="post" action="<?= h(public_url('logout')) ?>" class="logout-form">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <button class="btn ghost full" type="submit">خروج</button>
        </form>
    </aside>

    <main class="chat-main">
        <header class="chat-topbar">
            <button class="icon-btn mobile-only" id="sidebarToggle" type="button" aria-label="نمایش گفتگوها">☰</button>
            <div>
                <h1 id="conversationTitle">گفتگوی جدید</h1>
                <p id="quotaText">در حال دریافت اطلاعات حساب…</p>
            </div>
            <div class="topbar-actions">
                <label class="model-label">مدل هوش مصنوعی
                    <select id="modelSelect" aria-label="انتخاب مدل هوش مصنوعی"></select>
                </label>
            </div>
        </header>

        <section class="status-strip" id="modelStatus">مدل‌ها از Dahl خوانده می‌شوند…</section>

        <section class="messages" id="messages" aria-live="polite">
            <div class="empty-state" id="emptyState">
                <img class="empty-logo-img" src="<?= h(public_url('assets/img/logo.png')) ?>" alt="گپ‌هوش">
                <h2>از گپ‌هوش بپرس</h2>
                <p>چند نمونه برای شروع:</p>
                <div class="prompt-grid">
                    <button type="button" data-prompt="برای وب‌سایت گپ‌هوش یک متن معرفی کوتاه و سئو شده بنویس.">متن معرفی سایت</button>
                    <button type="button" data-prompt="این کد PHP را از نظر امنیت بررسی کن و نسخه بهترش را داخل بلاک کد بده.">بررسی امنیت کد</button>
                    <button type="button" data-prompt="برای یادگیری Flutter یک مسیر ۳۰ روزه ساده و عملی پیشنهاد بده.">برنامه یادگیری</button>
                    <button type="button" data-prompt="یک تابع JavaScript برای اعتبارسنجی فرم ثبت‌نام بنویس و توضیح بده.">کدنویسی فرانت‌اند</button>
                </div>
            </div>
        </section>

        <footer class="composer-wrap">
            <form id="chatForm" class="composer">
                <textarea id="promptInput" rows="1" maxlength="12000" placeholder="پیامت را بنویس… Enter برای ارسال، Shift+Enter برای خط جدید"></textarea>
                <button id="sendBtn" class="send-btn" type="submit" aria-label="ارسال پیام">ارسال</button>
            </form>
            <p class="composer-hint">پاسخ‌ها لحظه‌ای نمایش داده می‌شوند؛ متن فارسی راست‌چین و کدها چپ‌چین رندر می‌شوند.</p>
        </footer>
    </main>
</div>
