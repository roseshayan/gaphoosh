(() => {
  const qs = (selector, root = document) => root.querySelector(selector);
  const qsa = (selector, root = document) => [...root.querySelectorAll(selector)];
  const csrf = qs('meta[name="csrf-token"]')?.content || '';

  const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  const hasRtl = (text) => /[\u0600-\u06FF]/.test(String(text || ''));
  const directionClass = (text) => hasRtl(text) ? 'rtl-text' : 'ltr-text';

  const formatDate = (value) => {
    if (!value) return '';
    const normalized = String(value).replace(' ', 'T');
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat('fa-IR', { dateStyle: 'short', timeStyle: 'short' }).format(date);
  };

  const formatNumber = (value) => Number(value || 0).toLocaleString('fa-IR');

  const safeUrl = (url) => {
    const value = String(url || '').trim();
    return /^https?:\/\//i.test(value) ? value : '';
  };

  const formatInline = (value) => {
    const inlineBlocks = [];
    let html = escapeHtml(value || '');

    html = html.replace(/`([^`\n]+)`/g, (_, code) => {
      const index = inlineBlocks.push(`<code class="inline-code" dir="ltr">${escapeHtml(code)}</code>`) - 1;
      return `@@INLINE_${index}@@`;
    });

    html = html.replace(/\[([^\]\n]+)\]\((https?:\/\/[^\s)]+)\)/g, (_, label, url) => {
      const href = safeUrl(url);
      if (!href) return `${label} (${url})`;
      return `<a href="${escapeHtml(href)}" target="_blank" rel="noopener noreferrer">${label}</a>`;
    });

    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/__([^_]+)__/g, '<strong>$1</strong>');
    html = html.replace(/(^|\s)\*([^*\n]+)\*(?=\s|$)/g, '$1<em>$2</em>');
    html = html.replace(/(^|\s)_([^_\n]+)_(?=\s|$)/g, '$1<em>$2</em>');
    html = html.replace(/@@INLINE_(\d+)@@/g, (_, i) => inlineBlocks[Number(i)] || '');
    return html;
  };

  const splitTableRow = (line) => {
    let text = String(line || '').trim();
    if (text.startsWith('|')) text = text.slice(1);
    if (text.endsWith('|')) text = text.slice(0, -1);
    return text.split('|').map((cell) => cell.trim());
  };

  const isTableSeparator = (line) => {
    const cells = splitTableRow(line);
    return cells.length > 1 && cells.every((cell) => /^:?-{3,}:?$/.test(cell.trim()));
  };

  const isTableStart = (lines, index) => {
    return String(lines[index] || '').includes('|') && isTableSeparator(lines[index + 1] || '');
  };

  const renderTable = (rows) => {
    const header = splitTableRow(rows[0]);
    const bodyRows = rows.slice(2).filter((row) => row.trim() && row.includes('|'));
    const headHtml = header.map((cell) => `<th>${formatInline(cell)}</th>`).join('');
    const bodyHtml = bodyRows.map((row) => {
      const cells = splitTableRow(row);
      return `<tr>${cells.map((cell) => `<td>${formatInline(cell)}</td>`).join('')}</tr>`;
    }).join('');
    return `<div class="table-scroll"><table class="markdown-table"><thead><tr>${headHtml}</tr></thead><tbody>${bodyHtml}</tbody></table></div>`;
  };

  const isBlockStart = (lines, index) => {
    const trimmed = String(lines[index] || '').trim();
    return !trimmed
      || /^@@CODEBLOCK_\d+@@$/.test(trimmed)
      || /^#{1,6}\s+/.test(trimmed)
      || /^-{3,}$/.test(trimmed)
      || /^>\s?/.test(trimmed)
      || /^[-*+]\s+/.test(trimmed)
      || /^\d+\.\s+/.test(trimmed)
      || isTableStart(lines, index);
  };

  const renderCodeBlock = (language, code) => {
    const lang = escapeHtml(String(language || '').trim() || 'code');
    const codeText = escapeHtml(String(code || '').replace(/^\n+|\n+$/g, ''));
    return `
      <div class="code-card" dir="ltr">
        <div class="code-head"><span>${lang}</span><button type="button" class="copy-code">کپی کد</button></div>
        <pre><code>${codeText}</code></pre>
      </div>
    `;
  };

  const formatMessage = (text) => {
    const codeBlocks = [];
    const normalized = String(text || '').replace(/\r\n?/g, '\n').trim();
    if (!normalized) return '';

    const source = normalized.replace(/```([^\n`]*)\n?([\s\S]*?)```/g, (_, language, code) => {
      const index = codeBlocks.push(renderCodeBlock(language, code)) - 1;
      return `\n@@CODEBLOCK_${index}@@\n`;
    });

    const lines = source.split('\n');
    const html = [];
    let i = 0;

    while (i < lines.length) {
      const line = lines[i];
      const trimmed = line.trim();

      if (!trimmed) { i += 1; continue; }

      const codeMatch = trimmed.match(/^@@CODEBLOCK_(\d+)@@$/);
      if (codeMatch) {
        html.push(codeBlocks[Number(codeMatch[1])] || '');
        i += 1;
        continue;
      }

      if (/^-{3,}$/.test(trimmed)) {
        html.push('<hr class="markdown-hr">');
        i += 1;
        continue;
      }

      const heading = trimmed.match(/^(#{1,6})\s+(.+)$/);
      if (heading) {
        const level = Math.min(heading[1].length + 1, 4);
        html.push(`<h${level}>${formatInline(heading[2])}</h${level}>`);
        i += 1;
        continue;
      }

      if (isTableStart(lines, i)) {
        const tableRows = [lines[i], lines[i + 1]];
        i += 2;
        while (i < lines.length && lines[i].trim() && lines[i].includes('|')) {
          tableRows.push(lines[i]);
          i += 1;
        }
        html.push(renderTable(tableRows));
        continue;
      }

      if (/^>\s?/.test(trimmed)) {
        const quote = [];
        while (i < lines.length && /^>\s?/.test(lines[i].trim())) {
          quote.push(lines[i].trim().replace(/^>\s?/, ''));
          i += 1;
        }
        html.push(`<blockquote>${quote.map((item) => `<p>${formatInline(item)}</p>`).join('')}</blockquote>`);
        continue;
      }

      if (/^[-*+]\s+/.test(trimmed)) {
        const items = [];
        while (i < lines.length && /^[-*+]\s+/.test(lines[i].trim())) {
          items.push(lines[i].trim().replace(/^[-*+]\s+/, ''));
          i += 1;
        }
        html.push(`<ul>${items.map((item) => `<li>${formatInline(item)}</li>`).join('')}</ul>`);
        continue;
      }

      if (/^\d+\.\s+/.test(trimmed)) {
        const items = [];
        while (i < lines.length && /^\d+\.\s+/.test(lines[i].trim())) {
          items.push(lines[i].trim().replace(/^\d+\.\s+/, ''));
          i += 1;
        }
        html.push(`<ol>${items.map((item) => `<li>${formatInline(item)}</li>`).join('')}</ol>`);
        continue;
      }

      const paragraph = [trimmed];
      i += 1;
      while (i < lines.length && !isBlockStart(lines, i)) {
        paragraph.push(lines[i].trim());
        i += 1;
      }
      html.push(`<p>${paragraph.filter(Boolean).map(formatInline).join('<br>')}</p>`);
    }

    return html.join('');
  };

  const copyToClipboard = async (button, text, successText = 'کپی شد') => {
    try {
      await navigator.clipboard.writeText(text || '');
      const old = button.textContent;
      button.textContent = successText;
      button.classList.add('copied');
      setTimeout(() => {
        button.textContent = old;
        button.classList.remove('copied');
      }, 1200);
    } catch (_) {
      button.textContent = 'خطا';
    }
  };

  document.addEventListener('click', async (event) => {
    const codeButton = event.target.closest('.copy-code');
    if (codeButton) {
      const code = codeButton.closest('.code-card')?.querySelector('code')?.textContent || '';
      await copyToClipboard(codeButton, code, 'کپی شد');
      return;
    }

    const messageButton = event.target.closest('.copy-message');
    if (messageButton) {
      const message = messageButton.closest('.message')?.dataset.rawContent || '';
      await copyToClipboard(messageButton, message, 'کپی شد');
    }
  });

  const initChat = () => {
    const app = qs('.chat-app');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl.replace(/\/$/, '');
    const conversationList = qs('#conversationList');
    const messages = qs('#messages');
    const emptyState = qs('#emptyState');
    const form = qs('#chatForm');
    const input = qs('#promptInput');
    const sendBtn = qs('#sendBtn');
    const modelSelect = qs('#modelSelect');
    const modelStatus = qs('#modelStatus');
    const quotaText = qs('#quotaText');
    const titleEl = qs('#conversationTitle');
    const newChatBtn = qs('#newChatBtn');
    const sidebar = qs('#sidebar');
    const sidebarToggle = qs('#sidebarToggle');
    const closeSidebar = qs('#closeSidebar');
    const chatSearch = qs('#chatSearch');

    let activeConversationId = null;
    let conversations = [];
    let isSending = false;
    let activeAbort = null;
    let currentStream = null;

    const setSendingState = (sending) => {
      isSending = Boolean(sending);
      sendBtn.disabled = false;
      sendBtn.textContent = isSending ? 'توقف' : 'ارسال';
      sendBtn.classList.toggle('is-stopping', isSending);
      sendBtn.setAttribute('aria-label', isSending ? 'توقف دریافت پاسخ' : 'ارسال پیام');
    };

    const cancelActiveStream = (silent = false) => {
      if (activeAbort) {
        activeAbort.abort();
      }
      if (!silent && currentStream?.item?.isConnected) {
        if (!currentStream.text.trim()) currentStream.text = 'پاسخ متوقف شد.';
        finalizeStreamingBubble(currentStream);
      }
      activeAbort = null;
      currentStream = null;
      setSendingState(false);
    };

    const api = async (path, options = {}) => {
      const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrf,
        ...(options.headers || {})
      };
      const response = await fetch(baseUrl + path, {
        credentials: 'same-origin',
        ...options,
        headers
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(data.error || 'خطای ناشناخته رخ داد.');
      return data;
    };

    const scrollBottom = () => { messages.scrollTop = messages.scrollHeight; };

    const showEmpty = () => {
      const hasMessage = messages.querySelector('.message');
      if (emptyState) emptyState.classList.toggle('is-hidden', Boolean(hasMessage));
    };

    const renderMessage = (role, content, model = '') => {
      const item = document.createElement('article');
      item.className = `message ${role} ${directionClass(content)}`;
      item.dataset.rawContent = String(content || '');
      const label = role === 'user' ? 'شما' : 'هوش مصنوعی';
      const copyButton = role === 'assistant'
        ? '<button type="button" class="copy-message" title="کپی کل پاسخ">کپی پاسخ</button>'
        : '';
      item.innerHTML = `
        <div class="message-meta"><span>${label}${model ? ' · ' + escapeHtml(model) : ''}</span>${copyButton}</div>
        <div class="bubble markdown-content" dir="auto">${formatMessage(content)}</div>
      `;
      messages.appendChild(item);
      showEmpty();
      scrollBottom();
      return item;
    };

    const renderStreamingMessage = (model = '') => {
      const item = renderMessage('assistant', '', model);
      const bubble = item.querySelector('.bubble');
      bubble.innerHTML = '<span class="typing-dots"><span></span><span></span><span></span></span>';
      return { item, bubble, text: '' };
    };

    const updateStreamingBubble = (stream, delta) => {
      stream.text += delta;
      stream.item.dataset.rawContent = stream.text;
      stream.item.classList.remove('rtl-text', 'ltr-text');
      stream.item.classList.add(directionClass(stream.text));
      stream.bubble.innerHTML = formatMessage(stream.text) + '<span class="cursor-live"></span>';
      scrollBottom();
    };

    const finalizeStreamingBubble = (stream) => {
      stream.item.dataset.rawContent = stream.text || 'پاسخی دریافت نشد.';
      stream.bubble.innerHTML = formatMessage(stream.text || 'پاسخی دریافت نشد.');
      stream.item.classList.remove('rtl-text', 'ltr-text');
      stream.item.classList.add(directionClass(stream.text));
      scrollBottom();
    };

    const updateQuota = (used, max, balance = undefined) => {
      const usedNumber = Number(used || 0);
      const maxNumber = Number(max || 0);
      const remaining = Math.max(0, maxNumber - usedNumber);
      const balanceText = balance === null || balance === undefined ? '' : ` · موجودی کلید: ${formatNumber(balance)} توکن`;
      quotaText.textContent = `سقف روزانه: ${formatNumber(remaining)} پیام باقی‌مانده از ${formatNumber(maxNumber)}${balanceText}`;
    };

    const upsertConversation = (item) => {
      if (!item?.id) return;
      const existing = conversations.findIndex((conversation) => Number(conversation.id) === Number(item.id));
      const normalized = {
        id: Number(item.id),
        title: item.title || 'گفتگوی جدید',
        model: item.model || modelSelect.value,
        updated_at: item.updated_at || new Date().toISOString(),
        created_at: item.created_at || item.updated_at || new Date().toISOString()
      };
      if (existing >= 0) {
        conversations[existing] = { ...conversations[existing], ...normalized };
      } else {
        conversations.unshift(normalized);
      }
      conversations.sort((a, b) => Number(new Date(String(b.updated_at).replace(' ', 'T'))) - Number(new Date(String(a.updated_at).replace(' ', 'T'))));
      renderConversations();
    };

    const renderConversations = () => {
      const keyword = (chatSearch?.value || '').trim().toLowerCase();
      const filtered = keyword
        ? conversations.filter((item) => String(item.title || '').toLowerCase().includes(keyword) || String(item.model || '').toLowerCase().includes(keyword))
        : conversations;

      conversationList.innerHTML = '';
      if (!filtered.length) {
        const empty = document.createElement('div');
        empty.className = 'message-meta sidebar-empty';
        empty.textContent = keyword ? 'نتیجه‌ای پیدا نشد.' : 'هنوز گفتگویی نداری.';
        conversationList.appendChild(empty);
        return;
      }

      for (const conversation of filtered) {
        const row = document.createElement('button');
        row.type = 'button';
        row.className = `conversation-item ${Number(conversation.id) === Number(activeConversationId) ? 'active' : ''}`;
        row.innerHTML = `
          <span class="conversation-title">
            <strong>${escapeHtml(conversation.title || 'گفتگوی جدید')}</strong>
            <small>${escapeHtml(conversation.model || '')} · ${escapeHtml(formatDate(conversation.updated_at))}</small>
          </span>
          <span class="delete-conversation" data-id="${conversation.id}" title="حذف">×</span>
        `;
        row.addEventListener('click', (event) => {
          if (event.target.classList.contains('delete-conversation')) return;
          loadMessages(conversation.id);
          sidebar.classList.remove('open');
        });
        row.querySelector('.delete-conversation').addEventListener('click', async (event) => {
          event.stopPropagation();
          const ok = confirm('این گفتگو حذف شود؟');
          if (!ok) return;
          await api('/api/conversations/delete', {
            method: 'POST',
            body: JSON.stringify({ conversation_id: conversation.id })
          });
          if (Number(activeConversationId) === Number(conversation.id)) resetChat();
          await loadConversations();
        });
        conversationList.appendChild(row);
      }
    };

    const loadConversations = async () => {
      const data = await api('/api/conversations');
      conversations = data.conversations || [];
      renderConversations();
    };

    const loadMessages = async (conversationId) => {
      if (activeAbort) cancelActiveStream(true);
      const data = await api(`/api/messages?conversation_id=${encodeURIComponent(conversationId)}`);
      activeConversationId = Number(conversationId);
      messages.querySelectorAll('.message').forEach((el) => el.remove());
      const conversation = conversations.find((item) => Number(item.id) === Number(conversationId));
      titleEl.textContent = conversation?.title || 'گفتگو';
      if (conversation?.model && [...modelSelect.options].some((option) => option.value === conversation.model)) {
        modelSelect.value = conversation.model;
      }
      for (const message of data.messages || []) {
        renderMessage(message.role, message.content, message.model || '');
      }
      showEmpty();
      renderConversations();
    };

    const resetChat = () => {
      activeConversationId = null;
      messages.querySelectorAll('.message').forEach((el) => el.remove());
      titleEl.textContent = 'گفتگوی جدید';
      showEmpty();
      renderConversations();
      input.focus();
    };

    const modelPersianName = (id) => {
      if (id.includes('MiniMax')) return 'MiniMax M2.7';
      if (id.includes('Kimi')) return 'Kimi K2.6';
      if (id.includes('GLM')) return 'GLM 5.2 FP8';
      return id;
    };

    const loadBootstrap = async () => {
      const data = await api('/api/bootstrap');
      modelSelect.innerHTML = '';
      const models = data.models || [];
      for (const model of models) {
        if (!model.id) continue;
        const option = document.createElement('option');
        option.value = model.id;
        const status = model.operational === true ? 'فعال' : model.operational === false ? 'نامطمئن' : 'نامشخص';
        const uptime = model.uptime === null || model.uptime === undefined ? '' : ` · آپ‌تایم ${model.uptime}%`;
        option.textContent = `${modelPersianName(model.id)} — ${model.id} (${status}${uptime})`;
        modelSelect.appendChild(option);
      }
      const defaultModel = data.default_model || modelSelect.options[0]?.value;
      if (defaultModel && [...modelSelect.options].some((option) => option.value === defaultModel)) {
        modelSelect.value = defaultModel;
      }
      conversations = data.conversations || [];
      renderConversations();
      updateQuota(data.used_today, data.max_daily_messages, data.balance);
      modelStatus.textContent = models.length > 1
        ? `${formatNumber(models.length)} مدل از Dahl آماده انتخاب است. اگر خطای ۴۰۳ دیدی، مدل دیگر یا کلید جدید Dahl را تست کن.`
        : 'فقط مدل fallback نمایش داده شد؛ اتصال سرور به endpoint مدل‌ها را بررسی کن.';
    };

    const parseSseChunk = (state, chunk, handlers) => {
      state.buffer += chunk;
      const events = state.buffer.split('\n\n');
      state.buffer = events.pop() || '';

      for (const rawEvent of events) {
        const lines = rawEvent.split('\n');
        let eventName = 'message';
        const dataLines = [];
        for (const line of lines) {
          if (line.startsWith('event:')) eventName = line.slice(6).trim();
          if (line.startsWith('data:')) dataLines.push(line.slice(5).trimStart());
        }
        if (!dataLines.length) continue;
        const rawData = dataLines.join('\n');
        let payload = rawData;
        try { payload = JSON.parse(rawData); } catch (_) {}
        if (handlers[eventName]) handlers[eventName](payload);
      }
    };

    const sendMessage = async () => {
      const text = input.value.trim();
      if (!text || isSending) return;

      setSendingState(true);
      input.value = '';
      autoGrow();
      renderMessage('user', text, modelSelect.value);
      const stream = renderStreamingMessage(modelSelect.value);
      currentStream = stream;
      activeAbort = new AbortController();

      try {
        const response = await fetch(baseUrl + '/api/chat/stream', {
          method: 'POST',
          credentials: 'same-origin',
          signal: activeAbort.signal,
          headers: {
            Accept: 'text/event-stream',
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrf
          },
          body: JSON.stringify({
            conversation_id: activeConversationId,
            model: modelSelect.value,
            message: text
          })
        });

        if (!response.ok || !response.body) {
          const errorData = await response.json().catch(() => ({}));
          throw new Error(errorData.error || 'خطا در شروع استریم.');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder('utf-8');
        const state = { buffer: '' };
        let sawDone = false;

        const handlers = {
          meta: (payload) => {
            if (payload.conversation_id) activeConversationId = Number(payload.conversation_id);
            if (payload.title) titleEl.textContent = payload.title;
            upsertConversation({
              id: payload.conversation_id,
              title: payload.title || text.slice(0, 55),
              model: payload.model || modelSelect.value,
              updated_at: payload.updated_at || new Date().toISOString()
            });
            updateQuota(payload.used_today, payload.max_daily_messages);
          },
          delta: (payload) => updateStreamingBubble(stream, payload.text || ''),
          usage: () => {},
          done: async (payload) => {
            sawDone = true;
            finalizeStreamingBubble(stream);
            updateQuota(payload.used_today, payload.max_daily_messages);
            if (payload.fallback_mode === 'non_stream_after_403') {
              modelStatus.textContent = 'استریم مستقیم Dahl برای این کلید ۴۰۳ داد؛ پاسخ با fallback امن نمایش داده شد. برای استریم واقعی، کلید یا مدل را عوض کن.';
            }
            await loadConversations();
            const current = conversations.find((item) => Number(item.id) === Number(activeConversationId));
            if (current) titleEl.textContent = current.title;
          },
          error: (payload) => {
            throw new Error(payload.message || 'خطا در دریافت پاسخ.');
          }
        };

        while (true) {
          const { value, done } = await reader.read();
          if (done) break;
          parseSseChunk(state, decoder.decode(value, { stream: true }), handlers);
        }
        parseSseChunk(state, decoder.decode(), handlers);
        if (!sawDone) finalizeStreamingBubble(stream);
      } catch (error) {
        if (error.name === 'AbortError') {
          if (stream.item.isConnected) {
            if (!stream.text.trim()) stream.text = 'پاسخ متوقف شد.';
            finalizeStreamingBubble(stream);
          }
        } else {
          stream.text = 'خطا: ' + error.message;
          finalizeStreamingBubble(stream);
        }
      } finally {
        activeAbort = null;
        currentStream = null;
        setSendingState(false);
        input.focus();
      }
    };

    const autoGrow = () => {
      input.style.height = 'auto';
      input.style.height = `${Math.min(input.scrollHeight, 190)}px`;
    };

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      if (isSending) {
        cancelActiveStream(false);
        return;
      }
      sendMessage();
    });

    input.addEventListener('input', autoGrow);
    input.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
      }
    });

    newChatBtn.addEventListener('click', () => {
      if (activeAbort) cancelActiveStream(true);
      resetChat();
      sidebar.classList.remove('open');
    });

    window.addEventListener('beforeunload', () => {
      if (activeAbort) activeAbort.abort();
    });

    qsa('[data-prompt]').forEach((button) => {
      button.addEventListener('click', () => {
        input.value = button.dataset.prompt || '';
        autoGrow();
        input.focus();
      });
    });

    sidebarToggle?.addEventListener('click', () => sidebar.classList.add('open'));
    closeSidebar?.addEventListener('click', () => sidebar.classList.remove('open'));
    chatSearch?.addEventListener('input', renderConversations);

    loadBootstrap().catch((error) => {
      modelStatus.textContent = 'خطا: ' + error.message;
    });
  };

  const initAdmin = () => {
    const app = qs('.admin-app');
    if (!app) return;
    const baseUrl = app.dataset.baseUrl.replace(/\/$/, '');
    const statGrid = qs('#statGrid');
    const usersBody = qs('#usersTable tbody');
    const logsBody = qs('#logsTable tbody');
    const conversationsBody = qs('#conversationsTable tbody');
    const status = qs('#adminStatus');
    const refresh = qs('#refreshAdmin');
    const runDahlDiagnostic = qs('#runDahlDiagnostic');
    const dahlDiagnosticBox = qs('#dahlDiagnosticBox');

    const api = async (path, options = {}) => {
      const headers = { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, ...(options.headers || {}) };
      const response = await fetch(baseUrl + path, { credentials: 'same-origin', ...options, headers });
      const data = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(data.error || 'خطای مدیریت.');
      return data;
    };

    const statLabel = {
      users_total: 'کل کاربران', users_active: 'کاربر فعال', users_blocked: 'کاربر مسدود', admins: 'ادمین‌ها',
      conversations_total: 'گفتگوها', messages_total: 'پیام‌ها', tokens_total: 'توکن مصرفی', messages_today: 'پیام امروز', api_errors_24h: 'خطای ۲۴ ساعت'
    };

    const render = (data) => {
      statGrid.innerHTML = Object.entries(data.stats || {}).map(([key, value]) => `
        <article><span>${escapeHtml(statLabel[key] || key)}</span><strong>${formatNumber(value)}</strong></article>
      `).join('');

      usersBody.innerHTML = (data.users || []).map((user) => `
        <tr>
          <td><strong>${escapeHtml(user.name)}</strong><small>${escapeHtml(user.email || 'بدون ایمیل')}</small></td>
          <td dir="ltr">${escapeHtml(user.mobile)}</td>
          <td><span class="badge ${user.status === 'active' ? 'ok' : 'bad'}">${user.status === 'active' ? 'فعال' : 'مسدود'}</span></td>
          <td>${Number(user.is_admin) === 1 ? '<span class="badge ok">ادمین</span>' : '<span class="badge">عادی</span>'}</td>
          <td>${formatNumber(user.conversations_count)}</td>
          <td>${formatNumber(user.messages_count)}</td>
          <td class="table-actions">
            <button data-action="status" data-id="${user.id}" data-status="${user.status === 'active' ? 'blocked' : 'active'}">${user.status === 'active' ? 'مسدود' : 'فعال'}</button>
            <button data-action="admin" data-id="${user.id}" data-admin="${Number(user.is_admin) === 1 ? '0' : '1'}">${Number(user.is_admin) === 1 ? 'حذف ادمین' : 'ادمین کن'}</button>
          </td>
        </tr>
      `).join('');

      conversationsBody.innerHTML = (data.conversations || []).map((conversation) => `
        <tr>
          <td><strong>${escapeHtml(conversation.title)}</strong><small>${escapeHtml(formatDate(conversation.updated_at))}</small></td>
          <td>${escapeHtml(conversation.name)}<small dir="ltr">${escapeHtml(conversation.mobile)}</small></td>
          <td>${escapeHtml(conversation.model)}</td>
          <td>${formatNumber(conversation.messages_count)}</td>
          <td><button data-action="delete-conversation" data-id="${conversation.id}">حذف</button></td>
        </tr>
      `).join('');

      logsBody.innerHTML = (data.logs || []).map((log) => `
        <tr>
          <td>${escapeHtml(formatDate(log.created_at))}</td>
          <td>${escapeHtml(log.name)}<small dir="ltr">${escapeHtml(log.mobile)}</small></td>
          <td>${escapeHtml(log.model)}</td>
          <td><span class="badge ${Number(log.success) === 1 ? 'ok' : 'bad'}">${Number(log.success) === 1 ? 'موفق' : 'خطا'} · ${escapeHtml(log.status_code)}</span></td>
          <td>${escapeHtml(log.error_message || '-')}</td>
        </tr>
      `).join('');
    };

    const load = async () => {
      status.textContent = 'در حال بارگذاری اطلاعات…';
      const data = await api('/api/admin/dashboard');
      render(data);
      status.textContent = 'آخرین به‌روزرسانی: ' + new Intl.DateTimeFormat('fa-IR', { dateStyle: 'short', timeStyle: 'medium' }).format(new Date());
    };

    app.addEventListener('click', async (event) => {
      const button = event.target.closest('button[data-action]');
      if (!button) return;
      const action = button.dataset.action;
      try {
        if (action === 'status') {
          await api('/api/admin/users/status', { method: 'POST', body: JSON.stringify({ user_id: button.dataset.id, status: button.dataset.status }) });
        }
        if (action === 'admin') {
          await api('/api/admin/users/admin', { method: 'POST', body: JSON.stringify({ user_id: button.dataset.id, is_admin: button.dataset.admin === '1' }) });
        }
        if (action === 'delete-conversation') {
          if (!confirm('این گفتگو حذف شود؟')) return;
          await api('/api/admin/conversations/delete', { method: 'POST', body: JSON.stringify({ conversation_id: button.dataset.id }) });
        }
        await load();
      } catch (error) {
        alert(error.message);
      }
    });

    runDahlDiagnostic?.addEventListener('click', async () => {
      try {
        dahlDiagnosticBox.textContent = 'در حال بررسی اتصال Dahl…';
        const data = await api('/api/admin/dahl-diagnostics');
        dahlDiagnosticBox.textContent = JSON.stringify(data, null, 2);
      } catch (error) {
        dahlDiagnosticBox.textContent = 'خطا: ' + error.message;
      }
    });

    refresh.addEventListener('click', load);
    load().catch((error) => { status.textContent = 'خطا: ' + error.message; });
  };

  initChat();
  initAdmin();
})();
