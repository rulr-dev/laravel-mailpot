<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>📪 Mailpot Inbox</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs" defer></script>
</head>
<body class="bg-gray-50 text-gray-800 antialiased" x-data="mailpotInbox()">

<!-- Page Wrapper -->
<div class="min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white shadow-sm border-b px-6 py-2 flex items-center justify-between">
        <h1 class="text-xl md:text-xl font-semibold text-blue-700">Laravel Mailpot</h1>
        <span class="text-sm text-gray-500">Total Messages: {{ count($messages) }}</span>
    </header>

    <!-- Main Content -->
    <div class="flex flex-1 min-h-0 overflow-hidden">

        <!-- Sidebar -->
        <aside class="w-full md:w-1/3 lg:w-1/4 bg-white border-r overflow-y-auto h-full">
            <ul class="divide-y divide-gray-100">
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

        <!-- Preview Pane -->
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Selected Message View -->
            <template x-if="selected !== null">
                <div class="max-w-4xl mx-auto bg-white shadow-sm border rounded-xl p-6 space-y-4 transition-all">
                    <h2 class="text-lg font-semibold text-gray-800" x-text="messages[selected].parsed.subject || '(No Subject)'"></h2>

                    <div class="text-sm space-y-1 text-gray-600">
                        <p><strong>From:</strong> <span x-text="messages[selected].parsed.from || '-'"></span></p>
                        <p><strong>To:</strong> <span x-text="(messages[selected].parsed.to || []).join(', ')"></span></p>
                    </div>

                    <div class="prose max-w-none text-sm text-gray-800" x-html="messages[selected].parsed.html || formatText(messages[selected].parsed.text) || '(No content)'"></div>
                </div>
            </template>

            <!-- Stats View -->
            <template x-if="selected === null">
                <div class="max-w-md mx-auto mt-10 bg-white border rounded-xl p-6 shadow-sm text-sm text-gray-700 space-y-3 text-left">
                    <h2 class="text-base font-semibold text-gray-800 mb-2">📊 Inbox Statistics</h2>
                    @php
                        $statsPath = storage_path('framework/mailpot/stats.json');
                        $stats = file_exists($statsPath) ? json_decode(file_get_contents($statsPath), true) : null;
                    @endphp

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

<!-- AlpineJS -->
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
