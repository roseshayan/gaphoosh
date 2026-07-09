# Prompt optimized for Claude: Landing page redesign for GapHoosh

You are a senior Persian UI/UX designer and front-end engineer. Redesign the landing page of a Persian AI chat website named **گپ‌هوش** with domain **gaphoosh.ir**.

## Project context

The product is a Persian, RTL, ChatGPT-like AI chat website. It uses a PHP backend, MySQL database, and Dahl's OpenAI-compatible inference API. Users register/login, choose among multiple AI models, chat with streaming responses, and their conversations/messages are saved so they can reopen and continue old chats.

The brand name is **گپ‌هوش**. The domain is **gaphoosh.ir**. The visual identity should feel modern, trustworthy, Iranian/Persian-friendly, lightweight, and suitable for a free AI assistant.

## Important product features to communicate

- Persian-first AI chat experience
- Fully RTL interface
- Free to start / رایگان برای شروع
- Real-time streaming responses like ChatGPT
- Multiple AI models from Dahl
- Conversation history and chat management
- Code answers rendered in beautiful LTR code blocks
- Persian text rendered RTL
- Secure backend proxy; API key is never exposed to the browser
- Mobile number required for signup, email optional
- Admin panel for managing users, chats, API logs, and abuse
- Fast, lightweight, responsive, no CDN

## Design requirements

Design a premium landing page, not a developer documentation page. It should look like a polished SaaS product landing page.

Language: Persian only, except technical model names and domain.
Direction: RTL.
Font: Vazir/Vazirmatn.
Color palette: deep blue, cyan, violet, white, soft gray. Avoid harsh colors.
Style: clean, modern, airy, rounded cards, subtle gradients, soft shadows.
Mood: smart, helpful, fast, friendly, credible.
Responsiveness: must work perfectly on desktop, tablet, and mobile.
Performance: lightweight HTML/CSS/JS, no CDN, no heavy animations.
SEO: semantic HTML, one clear H1, meaningful headings, strong meta-friendly copy.

## Required sections

1. Sticky header
   - Logo/brand: گپ‌هوش
   - Navigation: کاربردها، امکانات، امنیت، ورود
   - CTA button: شروع رایگان

2. Hero section
   - H1: «با گپ‌هوش، با هوش مصنوعی فارسی گفتگو کن»
   - Short subtitle explaining: chat, coding, content, learning, saved conversations
   - CTA buttons: «شروع گفتگو رایگان» and «دیدن نمونه محیط»
   - Visual mockup of a chat window with a Persian prompt and an AI answer that includes a code block

3. Use cases section
   - کدنویسی و دیباگ
   - تولید محتوا
   - یادگیری و آموزش
   - ایده‌پردازی

4. Features section
   - استریم لحظه‌ای پاسخ‌ها
   - ذخیره و ادامه گفتگوها
   - چند مدل هوش مصنوعی
   - نمایش حرفه‌ای کد
   - رابط فارسی و راست‌چین
   - امنیت کلید API در بک‌اند

5. Code rendering preview
   - Show a beautiful LTR code block inside an RTL Persian explanation.
   - Include a copy button visual.

6. Security/trust section
   - Explain that the Dahl API key stays on the server.
   - Mention CSRF, sessions, prepared statements, and daily usage limits in user-friendly language.

7. CTA section
   - Encourage user to create a free account.
   - Keep it short and strong.

8. Footer
   - Brand name, domain, short SEO phrase: «هوش مصنوعی فارسی در gaphoosh.ir»

## UX details

- The hero should immediately communicate that this is an AI chat app, not a blog or documentation site.
- The chat mockup should be attractive and realistic.
- The CTA should be visible above the fold.
- Avoid long paragraphs.
- Use concise Persian copy.
- Avoid fake claims like «قوی‌ترین هوش مصنوعی ایران».
- Use «رایگان برای شروع» rather than promising unlimited free service.
- Make mobile layout excellent: hero stacks, cards become single-column, header stays usable.

## Technical output format

Return production-ready HTML and CSS for the landing page only. Do not use CDN. Do not use Tailwind CDN. Do not use external image URLs. Assume local assets are available:

- `/assets/img/logo.png`
- `/assets/app.css`
- local Vazir/Vazirmatn font files under `/assets/fonts/`

The final code should be easy to integrate into a PHP view file.
