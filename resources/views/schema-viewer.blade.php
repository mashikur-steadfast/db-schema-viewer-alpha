<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Schema Viewer</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Interact.js CDN for drag-and-drop -->
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <style>
        /* Custom styles for relationship lines */
        .relationship-line {
            stroke: #3b82f6; /* Blue color */
            stroke-width: 2;
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <h1 class="text-3xl font-bold mb-8">Database Schema Viewer</h1>

    <!-- Schema Container -->
    <div id="schema-container" class="relative w-full h-screen">
        <!-- SVG for Relationship Lines -->
        <svg id="relationship-lines" class="absolute top-0 left-0 w-full h-full pointer-events-none">
            <!-- Lines will be dynamically added here -->
        </svg>

        <!-- Tables -->
        @foreach ($schema as $tableName => $tableData)
            <div id="table-{{ $tableName }}" class="absolute bg-white shadow-lg rounded-lg p-4 w-64 cursor-move">
                <h2 class="font-bold text-lg mb-2">{{ $tableName }}</h2>
                <div class="space-y-1">
                    @foreach ($tableData['columns'] as $column)
                        <div class="text-sm">
                            <span class="font-medium">{{ $column['name'] }}</span>:
                            <span class="text-gray-600">{{ $column['type'] }}</span>
                        </div>
                    @endforeach
                </div>
                @if (count($tableData['relationships']) > 0)
                    <div class="mt-3">
                        <h3 class="font-semibold text-sm">Relationships</h3>
                        <ul class="text-xs text-blue-600">
                            @foreach ($tableData['relationships'] as $relationship)
                                <li>
                                    {{ $relationship['type'] }}:
                                    {{ $relationship['foreign_key'] }} → {{ $relationship['related_table'] }}.{{ $relationship['references'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Drag-and-Drop and Relationship Lines Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const schemaContainer = document.getElementById('schema-container');
            const svg = document.getElementById('relationship-lines');

            // Make tables draggable
            interact('.absolute').draggable({
                listeners: {
                    move(event) {
                        const target = event.target;
                        const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                        const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

                        // Move the table
                        target.style.transform = `translate(${x}px, ${y}px)`;
                        target.setAttribute('data-x', x);
                        target.setAttribute('data-y', y);

                        // Update relationship lines
                        updateRelationshipLines();
                    }
                }
            });

            // Function to update relationship lines
            function updateRelationshipLines() {
                // Clear existing lines
                svg.innerHTML = '';

                // Draw lines for each relationship
                @foreach ($schema as $tableName => $tableData)
                    @foreach ($tableData['relationships'] as $relationship)
                        {
                            const table1 = document.getElementById('table-{{ $tableName }}');
                            const table2 = document.getElementById('table-{{ $relationship['related_table'] }}');

                            if (table1 && table2) {
                                const rect1 = table1.getBoundingClientRect();
                                const rect2 = table2.getBoundingClientRect();

                                const x1 = rect1.left + rect1.width / 2;
                                const y1 = rect1.top + rect1.height / 2;
                                const x2 = rect2.left + rect2.width / 2;
                                const y2 = rect2.top + rect2.height / 2;

                                // Draw a line between the two tables
                                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                                line.setAttribute('x1', x1);
                                line.setAttribute('y1', y1);
                                line.setAttribute('x2', x2);
                                line.setAttribute('y2', y2);
                                line.setAttribute('class', 'relationship-line');
                                svg.appendChild(line);
                            }
                        }
                    @endforeach
                @endforeach
            }

            // Initial draw of relationship lines
            updateRelationshipLines();
        });
    </script>
</body>
</html>
