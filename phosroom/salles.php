<?php
session_start();
require_once 'db.php'; // Make sure this file exists and connects to your database
require_once __DIR__ . '/sidebar.php';
try {
    // Récupérer toutes les salles
    $stmt = $pdo->query("SELECT * FROM salles ORDER BY nom");
    $salles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Erreur de base de données: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salles - PhosRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            transition: all 0.3s ease;
        }

        body.dark-mode {
            background-color: #1a1a1a;
            color: #fff;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 600;
            color: #3A5A78;
        }

        .dark-mode .section-title {
            color: #5B8FB9;
        }

        .section-title i {
            margin-right: 0.5rem;
            color: #D4AF37;
        }

        /* Search Bar */
        .search-container {
            margin-bottom: 2rem;
            position: relative;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1.5rem 0.75rem 3rem;
            border-radius: 50px;
            border: 2px solid #e2e8f0;
            background-color: white;
            color: #333;
            font-family: "Poppins", sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .dark-mode .search-input {
            background-color: #2d3748;
            border-color: #4a5568;
            color: white;
        }

        .search-input:focus {
            outline: none;
            border-color: #3A5A78;
            box-shadow: 0 4px 15px rgba(58, 90, 120, 0.2);
        }

        .dark-mode .search-input:focus {
            border-color: #5B8FB9;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #3A5A78;
            font-size: 1.1rem;
        }

        .dark-mode .search-icon {
            color: #D4AF37;
        }

        /* Table Styles */
        .salles-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .dark-mode .salles-table {
            background-color: #2d3748;
        }

        .salles-table th, 
        .salles-table td {
            padding: 1.2rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .dark-mode .salles-table th,
        .dark-mode .salles-table td {
            border-bottom: 1px solid #4a5568;
        }

        .salles-table th {
            background-color: #3A5A78;
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }

        .dark-mode .salles-table th {
            background-color: #5B8FB9;
        }

        .salles-table tr:last-child td {
            border-bottom: none;
        }

        .salles-table tr:hover {
            background-color: rgba(58, 90, 120, 0.05);
            transition: background-color 0.2s ease;
        }

        .dark-mode .salles-table tr:hover {
            background-color: rgba(91, 143, 185, 0.1);
        }

        /* Status Badges */
        .reservation-status {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
            display: inline-block;
            min-width: 120px;
            text-align: center;
        }

        .status-disponible {
            background-color: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .status-reserve {
            background-color: rgba(245, 158, 11, 0.1);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-en_maintenance {
            background-color: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .dark-mode .status-disponible {
            background-color: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border-color: rgba(34, 197, 94, 0.4);
        }

        .dark-mode .status-reserve {
            background-color: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border-color: rgba(245, 158, 11, 0.4);
        }

        .dark-mode .status-en_maintenance {
            background-color: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.4);
        }

        /* No results message */
        .no-results {
            text-align: center;
            padding: 2rem;
            color: #718096;
            font-style: italic;
        }

        .dark-mode .no-results {
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h1 class="section-title">
                    <i class="fas fa-door-open"></i>
                    Les salles
                </h1>
            </header>

            <!-- Search Bar -->
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="search-input" class="search-input" placeholder="Rechercher une salle...">
            </div>

            <!-- Table -->
            <div class="table-container">
                <table class="salles-table">
                    <thead>
                        <tr>
                            <th>NOM</th>
                            <th>LOCALISATION</th>
                            <th>CAPACITÉ</th>
                            <th>STATUT</th>
                        </tr>
                    </thead>
                    <tbody id="salles-body">
                        <?php if (!empty($salles)): ?>
                            <?php foreach ($salles as $salle): ?>
                            <tr>
                                <td><?= htmlspecialchars($salle['nom']) ?></td>
                                <td><?= htmlspecialchars($salle['localisation']) ?></td>
                                <td><?= htmlspecialchars($salle['capacite']) ?></td>
                                <td>
                                    <span class="reservation-status status-<?= strtolower($salle['statut']) ?>">
                                        <?= $salle['statut'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="no-results">Aucune salle trouvée</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Simple and reliable search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const sallesBody = document.getElementById('salles-body');
            const originalRows = sallesBody.innerHTML;
            
            console.log('Search functionality loaded'); // Debug log

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                console.log('Searching for:', searchTerm); // Debug log
                
                const rows = sallesBody.getElementsByTagName('tr');
                let hasVisibleRows = false;
                
                // Loop through all rows
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.getElementsByTagName('td');
                    let rowMatches = false;
                    
                    // Skip the "no results" row if it exists
                    if (row.classList.contains('no-results-row')) {
                        continue;
                    }
                    
                    // Check each cell in the row
                    for (let j = 0; j < cells.length; j++) {
                        const cellText = cells[j].textContent.toLowerCase() || cells[j].innerText.toLowerCase();
                        if (cellText.includes(searchTerm)) {
                            rowMatches = true;
                            break;
                        }
                    }
                    
                    // Show or hide the row based on search match
                    if (rowMatches) {
                        row.style.display = '';
                        hasVisibleRows = true;
                    } else {
                        row.style.display = 'none';
                    }
                }
                
                // Show message if no results found
                let noResultsRow = sallesBody.querySelector('.no-results-row');
                if (!hasVisibleRows && searchTerm !== '') {
                    if (!noResultsRow) {
                        noResultsRow = document.createElement('tr');
                        noResultsRow.className = 'no-results-row';
                        noResultsRow.innerHTML = '<td colspan="4" class="no-results">Aucune salle trouvée pour "' + searchTerm + '"</td>';
                        sallesBody.appendChild(noResultsRow);
                    }
                    noResultsRow.style.display = '';
                } else if (noResultsRow) {
                    noResultsRow.style.display = 'none';
                }
            });
            
            // Clear search when page loads
            searchInput.value = '';
        });

        
        
        document.body.appendChild(themeToggle);
    </script>
</body>
</html>