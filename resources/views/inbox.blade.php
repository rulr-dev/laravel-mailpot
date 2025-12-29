<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>Mailpot Inbox</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs" defer></script>
</head>
<body class="h-full overflow-hidden bg-gray-50 text-gray-800 antialiased" x-data="mailpotInbox()">

<div class="h-full flex flex-col">

    <header class="flex-shrink-0 bg-white shadow-sm border-b px-6 py-2 flex items-center justify-between">
        <h1 class="text-xl md:text-xl font-semibold text-blue-700">Laravel Mailpot</h1>
        <span class="text-sm text-gray-500">Total Messages: {{ count($messages) }}</span>
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
            </div>

            <ul class="divide-y divide-gray-100 overflow-y-auto flex-1">
                <template x-for="(message, index) in messages" :key="index">
                    <li
                        class="cursor-pointer hover:bg-gray-50 transition-colors"
                        :class="{ 'bg-gray-100': selected === index }"
                        @click="selected = index"
                    >
                        <div class="p-4 space-y-1">
                            <h3 class="text-sm font-medium truncate" x-text="message.parsed.subject || '(No Subject)'"></h3>
                            <p class="text-xs text-gray-500 truncate" x-text="message.parsed.from || '-'"></p>
                            <p class="text-xs text-gray-400" x-text="message.parsed.date || ''"></p>
                        </div>
                    </li>
                </template>
            </ul>
        </aside>

        <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <template x-if="selected !== null">
                <div class="max-w-4xl mx-auto bg-white shadow-sm border rounded-xl p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-800" x-text="messages[selected].parsed.subject || '(No Subject)'"></h2>

                    <div class="text-sm space-y-1 text-gray-600">
                        <p><strong>From:</strong> <span x-text="messages[selected].parsed.from || '-'"></span></p>
                        <p><strong>To:</strong> <span x-text="(messages[selected].parsed.to || []).join(', ')"></span></p>
                    </div>

                    <div class="prose max-w-none text-sm text-gray-800" x-html="messages[selected].parsed.html || formatText(messages[selected].parsed.text) || '(No content)'"></div>
                </div>
            </template>

            <template x-if="selected === null">
                <div class="max-w-md mx-auto mt-10 bg-white border rounded-xl p-6 shadow-sm text-sm text-gray-700 space-y-3 text-left">
                    <h2 class="text-base font-semibold text-gray-800 mb-2">Inbox Statistics</h2>

                    @if($stats)
                        <ul class="space-y-1">
                            <li><strong>Total:</strong> {{ $stats['total'] }}</li>
                            <li><strong>Total Size:</strong> {{ \Rulr\Mailpot\Support\Statistics::formatBytes($stats['total_size']) }}</li>
                            <li><strong>Largest:</strong> {{ \Rulr\Mailpot\Support\Statistics::formatBytes($stats['largest']) }}</li>
                            <li><strong>Smallest:</strong> {{ \Rulr\Mailpot\Support\Statistics::formatBytes($stats['smallest']) }}</li>
                            <li><strong>Last Updated:</strong> {{ $stats['last_updated'] }}</li>
                        </ul>
                    @else
                        <p>No statistics found.</p>
                    @endif
                </div>
            </template>
        </main>
    </div>
</div>

<script>
  function mailpotInbox() {
    return {
      selected: null,
      messages: @json($messages),
      formatText(text) {
        return text?.replace(/\n/g, '<br>') ?? '';
      }
    };
  }
</script>

</body>
</html>
