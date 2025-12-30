<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>Mailpot Inbox</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs" defer></script>
    <style>
        .resize-handle {
            position: absolute;
            top: 0;
            right: -6px;
            width: 12px;
            height: 100%;
            cursor: ew-resize;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .resize-handle::before {
            content: '';
            width: 4px;
            height: 40px;
            max-height: 50%;
            background: #cbd5e1;
            border-radius: 2px;
            transition: background 0.15s, height 0.15s;
        }
        .resize-handle:hover::before,
        .resize-handle.dragging::before {
            background: #94a3b8;
            height: 60px;
        }
        .unread-dot {
            width: 8px;
            height: 8px;
            background: #3b82f6;
            border-radius: 50%;
            flex-shrink: 0;
        }
    </style>
</head>
<body class="h-full overflow-hidden bg-gray-50 text-gray-800 antialiased" x-data="mailpotInbox()">

<div class="h-full flex flex-col">

    <header class="flex-shrink-0 bg-white shadow-sm border-b px-6 py-2 flex items-center justify-between">
        <h1 class="text-xl md:text-xl font-semibold text-blue-700">Laravel Mailpot</h1>
        <span class="text-sm text-gray-500">
            <span x-show="unreadCount > 0" x-text="`${unreadCount} unread / `"></span>
            <span x-text="`${messages.length} messages`"></span>
        </span>
    </header>

    <div class="flex flex-1 overflow-hidden">

        <aside class="w-full md:w-1/3 lg:w-1/4 bg-white border-r flex flex-col">
            <div class="flex-shrink-0 flex items-center gap-1 px-3 py-2 border-b bg-gray-50">
                <button
                    @click="window.location.reload()"
                    class="p-1.5 rounded hover:bg-gray-200 text-gray-600 hover:text-gray-800 transition-colors"
                    title="Refresh"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
                <button
                    @click="markAllAsRead()"
                    class="p-1.5 rounded hover:bg-gray-200 text-gray-600 hover:text-gray-800 transition-colors"
                    title="Mark all as read"
                    x-show="unreadCount > 0"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
                <button
                    @click="markAllAsUnread()"
                    class="p-1.5 rounded hover:bg-gray-200 text-gray-600 hover:text-gray-800 transition-colors"
                    title="Mark all as unread"
                    x-show="unreadCount === 0 && messages.length > 0"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </button>
            </div>

            <ul class="divide-y divide-gray-100 overflow-y-auto flex-1">
                <template x-for="(message, index) in messages" :key="index">
                    <li
                        class="cursor-pointer hover:bg-gray-50 transition-colors"
                        :class="{ 'bg-gray-100': selected === index }"
                        @click="selectMessage(index)"
                    >
                        <div class="p-4 space-y-1 flex gap-3">
                            <div class="pt-1.5">
                                <div class="unread-dot" x-show="!isRead(message.filename)"></div>
                                <div class="w-2" x-show="isRead(message.filename)"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3
                                    class="text-sm truncate"
                                    :class="isRead(message.filename) ? 'font-normal text-gray-600' : 'font-semibold text-gray-900'"
                                    x-text="message.parsed.subject || '(No Subject)'"
                                ></h3>
                                <p class="text-xs text-gray-500 truncate" x-text="message.parsed.from || '-'"></p>
                                <p class="text-xs text-gray-400" x-text="message.parsed.date || ''"></p>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>
        </aside>

        <main class="flex-1 overflow-hidden p-6 bg-gray-50 flex flex-col">
            <template x-if="selected !== null">
                <div class="flex-1 flex flex-col overflow-hidden">
                    <div class="flex-shrink-0 flex items-center justify-center gap-1 mb-4">
                        <button
                            @click="toggleReadStatus(messages[selected].filename)"
                            class="p-2 rounded border bg-white text-gray-600 hover:bg-gray-100 transition-colors mr-2"
                            :title="isRead(messages[selected].filename) ? 'Mark as unread' : 'Mark as read'"
                        >
                            <svg x-show="isRead(messages[selected].filename)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <svg x-show="!isRead(messages[selected].filename)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
                            </svg>
                        </button>
                        <button
                            @click="setViewport('mobile')"
                            :class="viewport === 'mobile' ? 'bg-blue-100 text-blue-700' : 'bg-white text-gray-600 hover:bg-gray-100'"
                            class="p-2 rounded border transition-colors"
                            title="Mobile (375px)"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </button>
                        <button
                            @click="setViewport('tablet')"
                            :class="viewport === 'tablet' ? 'bg-blue-100 text-blue-700' : 'bg-white text-gray-600 hover:bg-gray-100'"
                            class="p-2 rounded border transition-colors"
                            title="Tablet (768px)"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </button>
                        <button
                            @click="setViewport('desktop')"
                            :class="viewport === 'desktop' ? 'bg-blue-100 text-blue-700' : 'bg-white text-gray-600 hover:bg-gray-100'"
                            class="p-2 rounded border transition-colors"
                            title="Desktop (100%)"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </button>
                        <span class="ml-2 text-xs text-gray-400" x-text="viewportLabel"></span>
                    </div>

                    <div class="flex-1 overflow-auto flex justify-center" x-ref="scrollContainer">
                        <div class="relative h-fit" :style="{ width: containerWidth }">
                            <div
                                x-ref="viewportContainer"
                                class="bg-white shadow-sm border rounded-xl p-6 space-y-4 overflow-y-auto"
                                style="max-height: calc(100vh - 200px);"
                            >
                                <h2 class="text-lg font-semibold text-gray-800" x-text="messages[selected].parsed.subject || '(No Subject)'"></h2>

                                <div class="text-sm space-y-1 text-gray-600">
                                    <p><strong>From:</strong> <span x-text="messages[selected].parsed.from || '-'"></span></p>
                                    <p><strong>To:</strong> <span x-text="(messages[selected].parsed.to || []).join(', ')"></span></p>
                                </div>

                                <div class="prose max-w-none text-sm text-gray-800" x-html="messages[selected].parsed.html || formatText(messages[selected].parsed.text) || '(No content)'"></div>
                            </div>

                            <div
                                class="resize-handle"
                                :class="{ 'dragging': isDragging }"
                                @mousedown.prevent="startResize"
                            ></div>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="selected === null">
                <div class="max-w-xl mx-auto mt-10 space-y-4">
                    <div class="bg-white border rounded-xl p-6 shadow-sm text-sm text-gray-700 text-left">
                        <h2 class="text-base font-semibold text-gray-800 mb-4">Inbox Statistics</h2>

                        @if($stats)
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Total Messages</p>
                                        <p class="font-semibold text-gray-800">{{ $stats['total'] }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="p-2 bg-purple-100 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Total Size</p>
                                        <p class="font-semibold text-gray-800">{{ \Rulr\Mailpot\Support\Statistics::formatBytes($stats['total_size']) }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="p-2 bg-green-100 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Largest Message</p>
                                        <p class="font-semibold text-gray-800">{{ \Rulr\Mailpot\Support\Statistics::formatBytes($stats['largest']) }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="p-2 bg-orange-100 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Smallest Message</p>
                                        <p class="font-semibold text-gray-800">{{ \Rulr\Mailpot\Support\Statistics::formatBytes($stats['smallest']) }}</p>
                                    </div>
                                </div>

                                <div class="col-span-2 flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="p-2 bg-gray-200 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Last Updated</p>
                                        <p class="font-semibold text-gray-800">{{ $stats['last_updated'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-gray-500">No statistics found.</p>
                            </div>
                        @endif

                        <div class="mt-4 pt-4 border-t">
                            <p class="text-xs text-gray-500 mb-2">View stats in your terminal:</p>
                            <div class="flex items-center gap-2" x-data="{ copied: false }">
                                <code class="flex-1 bg-gray-900 text-gray-100 px-3 py-2 rounded-lg text-xs font-mono">php artisan mailpot:stats</code>
                                <button
                                    @click="navigator.clipboard.writeText('php artisan mailpot:stats'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors"
                                    :title="copied ? 'Copied!' : 'Copy to clipboard'"
                                >
                                    <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <svg x-show="copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border rounded-xl p-6 shadow-sm text-sm text-gray-700 text-left">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-gray-800">Clear Inbox</h2>
                                <p class="text-xs text-gray-500 mt-1">Remove all stored messages from the Mailpot inbox. You will be prompted to optionally delete the stats file as well.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2" x-data="{ copied: false }">
                            <code class="flex-1 bg-gray-900 text-gray-100 px-3 py-2 rounded-lg text-xs font-mono">php artisan mailpot:clean</code>
                            <button
                                @click="navigator.clipboard.writeText('php artisan mailpot:clean'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors"
                                :title="copied ? 'Copied!' : 'Copy to clipboard'"
                            >
                                <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <svg x-show="copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>
</div>

<script>
  function mailpotInbox() {
    const STORAGE_KEY = 'mailpot_read_messages';

    return {
      selected: null,
      viewport: 'desktop',
      customWidth: null,
      isDragging: false,
      startX: 0,
      startWidth: 0,
      readMessages: [],
      messages: @json($messages),

      init() {
        this.loadReadMessages();
      },

      loadReadMessages() {
        try {
          const stored = localStorage.getItem(STORAGE_KEY);
          this.readMessages = stored ? JSON.parse(stored) : [];
        } catch (e) {
          this.readMessages = [];
        }
      },

      saveReadMessages() {
        try {
          localStorage.setItem(STORAGE_KEY, JSON.stringify(this.readMessages));
        } catch (e) {}
      },

      isRead(filename) {
        return this.readMessages.includes(filename);
      },

      markAsRead(filename) {
        if (!this.readMessages.includes(filename)) {
          this.readMessages.push(filename);
          this.saveReadMessages();
        }
      },

      markAsUnread(filename) {
        this.readMessages = this.readMessages.filter(f => f !== filename);
        this.saveReadMessages();
      },

      toggleReadStatus(filename) {
        if (this.isRead(filename)) {
          this.markAsUnread(filename);
        } else {
          this.markAsRead(filename);
        }
      },

      markAllAsRead() {
        this.messages.forEach(m => {
          if (!this.readMessages.includes(m.filename)) {
            this.readMessages.push(m.filename);
          }
        });
        this.saveReadMessages();
      },

      markAllAsUnread() {
        const filenames = this.messages.map(m => m.filename);
        this.readMessages = this.readMessages.filter(f => !filenames.includes(f));
        this.saveReadMessages();
      },

      selectMessage(index) {
        this.selected = index;
        this.markAsRead(this.messages[index].filename);
      },

      get unreadCount() {
        return this.messages.filter(m => !this.isRead(m.filename)).length;
      },

      get containerWidth() {
        if (this.viewport === 'custom' && this.customWidth) {
          return `${this.customWidth}px`;
        }
        const widths = {
          mobile: '375px',
          tablet: '768px',
          desktop: '100%'
        };
        return widths[this.viewport];
      },

      get viewportLabel() {
        if (this.viewport === 'custom' && this.customWidth) {
          return `Custom (${this.customWidth}px)`;
        }
        const labels = {
          mobile: 'Mobile (375px)',
          tablet: 'Tablet (768px)',
          desktop: 'Desktop (100%)'
        };
        return labels[this.viewport];
      },

      setViewport(size) {
        this.viewport = size;
        this.customWidth = null;
      },

      startResize(e) {
        this.isDragging = true;
        this.startX = e.clientX;

        const container = this.$refs.viewportContainer;
        this.startWidth = container.offsetWidth;

        document.addEventListener('mousemove', this.onResize.bind(this));
        document.addEventListener('mouseup', this.stopResize.bind(this));
        document.body.style.cursor = 'ew-resize';
        document.body.style.userSelect = 'none';
      },

      onResize(e) {
        if (!this.isDragging) return;

        const diff = e.clientX - this.startX;
        const newWidth = Math.max(280, this.startWidth + diff);
        const maxWidth = this.$refs.scrollContainer.offsetWidth - 48;

        this.customWidth = Math.min(newWidth, maxWidth);
        this.viewport = 'custom';
      },

      stopResize() {
        this.isDragging = false;
        document.removeEventListener('mousemove', this.onResize.bind(this));
        document.removeEventListener('mouseup', this.stopResize.bind(this));
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
      },

      formatText(text) {
        return text?.replace(/\n/g, '<br>') ?? '';
      }
    };
  }
</script>

</body>
</html>
